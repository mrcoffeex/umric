<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmDocumentTransmissionReceiptRequest;
use App\Http\Requests\StoreDocumentForwardRequest;
use App\Http\Requests\StoreDocumentTransmissionRequest;
use App\Models\DocumentTransmission;
use App\Models\DocumentTransmissionHistory;
use App\Models\DocumentTransmissionItem;
use App\Models\DocumentTransmissionItemActivity;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentTransmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $direction = $request->string('direction')->toString();
        if (! in_array($direction, ['incoming', 'outgoing'], true)) {
            $direction = 'incoming';
        }

        $status = $request->string('status')->toString();
        if (! in_array($status, ['all', 'pending', 'completed'], true)) {
            $status = 'all';
        }

        $q = $request->string('q')->trim()->limit(200)->toString();
        $perPage = min(max((int) $request->input('per_page', 15), 10), 50);

        $query = $direction === 'incoming'
            ? DocumentTransmission::query()
                ->with(['sender:id,name,email', 'items'])
                ->where('receiver_id', $user->id)
            : DocumentTransmission::query()
                ->with(['receiver:id,name,email', 'items'])
                ->where('sender_id', $user->id);

        if ($q !== '') {
            $needle = '%'.$q.'%';
            if ($direction === 'incoming') {
                $query->where(function ($w) use ($needle) {
                    $w->where('purpose', 'ilike', $needle)
                        ->orWhereHas('sender', function ($s) use ($needle) {
                            $s->where('name', 'ilike', $needle)
                                ->orWhere('email', 'ilike', $needle);
                        });
                });
            } else {
                $query->where(function ($w) use ($needle) {
                    $w->where('purpose', 'ilike', $needle)
                        ->orWhereHas('receiver', function ($s) use ($needle) {
                            $s->where('name', 'ilike', $needle)
                                ->orWhere('email', 'ilike', $needle);
                        });
                });
            }
        }

        if ($status === 'pending') {
            $query->where('status', DocumentTransmission::STATUS_PENDING);
        } elseif ($status === 'completed') {
            $query->where('status', DocumentTransmission::STATUS_COMPLETED);
        }

        $handoffs = $query->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (DocumentTransmission $t) => $this->serializeListRow($t, $direction));

        return Inertia::render('DocumentTransmissions/Index', [
            'direction' => $direction,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'counts' => [
                'incoming' => $this->transmissionTabCounts($user->id, 'incoming'),
                'outgoing' => $this->transmissionTabCounts($user->id, 'outgoing'),
            ],
            'handoffs' => $handoffs,
        ]);
    }

    public function createForward(Request $request, DocumentTransmission $transmission): Response
    {
        Gate::authorize('forward', $transmission);

        $transmission->load([
            'sender:id,name,email',
            'receiver:id,name,email',
            'items' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return Inertia::render('DocumentTransmissions/Forward', [
            'source' => $this->serializeForwardSource($transmission),
        ]);
    }

    public function storeForward(StoreDocumentForwardRequest $request, DocumentTransmission $transmission): RedirectResponse
    {
        Gate::authorize('forward', $transmission);

        $transmission->load(['items' => fn ($q) => $q->orderBy('sort_order')]);
        if ($transmission->items->isEmpty()) {
            throw ValidationException::withMessages([
                'purpose' => 'This handoff has no documents to forward.',
            ]);
        }

        $purpose = $request->purposeNormalized();
        if ($purpose === '') {
            throw ValidationException::withMessages([
                'purpose' => 'Please describe the purpose of this handoff.',
            ]);
        }

        $receiverId = $request->validated('receiver_id');
        $selectedItemIds = collect($request->validated('item_ids'))->unique()->values();
        $sourceItemsOrdered = $transmission->items
            ->filter(fn (DocumentTransmissionItem $item) => $selectedItemIds->contains($item->id))
            ->sortBy('sort_order')
            ->values();
        if ($sourceItemsOrdered->isEmpty()) {
            throw ValidationException::withMessages([
                'item_ids' => 'Select at least one document to forward.',
            ]);
        }
        if ($sourceItemsOrdered->count() !== $selectedItemIds->count()) {
            throw ValidationException::withMessages([
                'item_ids' => 'One or more selected documents are not part of this handoff.',
            ]);
        }

        $row = DB::transaction(function () use ($request, $transmission, $purpose, $receiverId, $sourceItemsOrdered) {
            $userId = $request->user()->id;

            $row = DocumentTransmission::create([
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'forwarded_from_id' => $transmission->id,
                'purpose' => $purpose,
                'share_token' => Str::random(48),
                'status' => DocumentTransmission::STATUS_PENDING,
            ]);

            $row->load('receiver:id,name,email');
            $toReceiverName = $row->receiver?->name;
            $toReceiverEmail = $row->receiver?->email;

            $forwardMetaBase = [
                'to_receiver_id' => $receiverId,
                'to_receiver_name' => $toReceiverName,
                'to_receiver_email' => $toReceiverEmail,
            ];

            foreach ($sourceItemsOrdered as $i => $sourceItem) {
                $item = DocumentTransmissionItem::create([
                    'document_transmission_id' => $row->id,
                    'source_item_id' => $sourceItem->id,
                    'label' => $sourceItem->label,
                    'sort_order' => $i,
                ]);

                if ($sourceItem->hasAttachment() && $sourceItem->disk && $sourceItem->file_path) {
                    $this->copyItemAttachmentTo($sourceItem, $row->id, $item);
                }

                $item->logActivity(DocumentTransmissionItemActivity::EVENT_FORWARDED_IN, $userId, array_merge($forwardMetaBase, [
                    'from_transmission_id' => $transmission->id,
                    'from_item_id' => $sourceItem->id,
                    'from_label' => $sourceItem->label,
                ]));

                $sourceItem->logActivity(DocumentTransmissionItemActivity::EVENT_FORWARDED_OUT, $userId, array_merge($forwardMetaBase, [
                    'to_transmission_id' => $row->id,
                ]));
            }

            $row->load(['sender:id,name,email', 'receiver:id,name,email', 'items']);

            DocumentTransmissionHistory::create([
                'document_transmission_id' => $row->id,
                'user_id' => $userId,
                'event' => DocumentTransmissionHistory::EVENT_HANDOFF_CREATED,
                'meta' => [
                    'receiver_name' => $row->receiver?->name,
                    'item_count' => $row->items->count(),
                    'forwarded_from_id' => $transmission->id,
                    'forwarded_from_purpose' => Str::limit($transmission->purpose, 200),
                    'documents' => $row->items->map(fn (DocumentTransmissionItem $item) => [
                        'label' => $item->label,
                        'has_attachment' => $item->hasAttachment(),
                        'file_name' => $item->hasAttachment() ? $item->file_name : null,
                    ])->values()->all(),
                ],
            ]);

            return $row;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Handoff forwarded. Share the new link with the recipient.',
        ]);

        return redirect()->route('document-transmissions.show', $row);
    }

    /**
     * @return array{total: int, pending: int, completed: int}
     */
    private function transmissionTabCounts(string $userId, string $direction): array
    {
        $base = function () use ($userId, $direction) {
            return DocumentTransmission::query()
                ->when($direction === 'incoming',
                    fn ($query) => $query->where('receiver_id', $userId),
                    fn ($query) => $query->where('sender_id', $userId),
                );
        };

        return [
            'total' => $base()->count(),
            'pending' => $base()->where('status', DocumentTransmission::STATUS_PENDING)->count(),
            'completed' => $base()->where('status', DocumentTransmission::STATUS_COMPLETED)->count(),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('DocumentTransmissions/Create');
    }

    public function store(StoreDocumentTransmissionRequest $request): RedirectResponse
    {
        $data = $request->normalized();

        if ($data['purpose'] === '') {
            throw ValidationException::withMessages([
                'purpose' => 'Please describe the purpose of this handoff.',
            ]);
        }

        if (count($data['items']) < 1) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one document to the list.',
            ]);
        }

        $transmission = DB::transaction(function () use ($request, $data) {
            $row = DocumentTransmission::create([
                'sender_id' => $request->user()->id,
                'receiver_id' => $data['receiver_id'],
                'purpose' => $data['purpose'],
                'share_token' => Str::random(48),
                'status' => DocumentTransmission::STATUS_PENDING,
            ]);

            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

            foreach ($data['items'] as $i => $entry) {
                $item = DocumentTransmissionItem::create([
                    'document_transmission_id' => $row->id,
                    'label' => $entry['label'],
                    'sort_order' => $i,
                ]);

                $uploadedFile = $entry['file'] ?? null;
                if ($uploadedFile !== null && $uploadedFile->isValid()) {
                    $path = $uploadedFile->store('document-handoffs/'.$row->id, $disk);
                    $item->forceFill([
                        'file_path' => $path,
                        'file_name' => $uploadedFile->getClientOriginalName(),
                        'file_size' => $uploadedFile->getSize(),
                        'disk' => $disk,
                    ])->save();
                }

                $item->logActivity(DocumentTransmissionItemActivity::EVENT_ADDED, $request->user()->id, [
                    'label' => $item->label,
                    'has_attachment' => $item->hasAttachment(),
                ]);
            }

            $row->load(['sender:id,name,email', 'receiver:id,name,email', 'items']);

            DocumentTransmissionHistory::create([
                'document_transmission_id' => $row->id,
                'user_id' => $request->user()->id,
                'event' => DocumentTransmissionHistory::EVENT_HANDOFF_CREATED,
                'meta' => [
                    'receiver_name' => $row->receiver?->name,
                    'item_count' => $row->items->count(),
                    'documents' => $row->items->map(fn (DocumentTransmissionItem $item) => [
                        'label' => $item->label,
                        'has_attachment' => $item->hasAttachment(),
                        'file_name' => $item->hasAttachment() ? $item->file_name : null,
                    ])->values()->all(),
                ],
            ]);

            return $row;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Document handoff created. Share the link or QR with the recipient.',
        ]);

        return redirect()->route('document-transmissions.show', $transmission);
    }

    public function downloadItemFile(
        Request $request,
        DocumentTransmission $transmission,
        DocumentTransmissionItem $item,
    ): StreamedResponse {
        Gate::authorize('view', $transmission);

        if ($item->document_transmission_id !== $transmission->id) {
            abort(404);
        }

        if (! $item->hasAttachment() || ! $item->disk || ! $item->file_path) {
            abort(404);
        }

        $filesystem = Storage::disk($item->disk);

        if (! $filesystem instanceof FilesystemAdapter) {
            abort(500);
        }

        if (! $filesystem->exists($item->file_path)) {
            abort(404);
        }

        $name = $item->file_name ?? basename($item->file_path);

        if ($request->boolean('preview')) {
            return $filesystem->response($item->file_path, $name);
        }

        return $filesystem->download($item->file_path, $name);
    }

    public function show(Request $request, DocumentTransmission $transmission): Response
    {
        Gate::authorize('view', $transmission);

        $transmission->load([
            'sender:id,name,email',
            'receiver:id,name,email',
            'forwardedFrom.sender:id,name,email',
            'forwardedFrom.receiver:id,name,email',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'items.activities.user:id,name',
            'histories.user:id,name',
        ]);

        $claimUrl = route('document-transmissions.claim', ['token' => $transmission->share_token]);

        return Inertia::render('DocumentTransmissions/Show', [
            'transmission' => $this->serializeDetail($transmission),
            'handoffHistory' => $this->serializeHandoffHistory($transmission),
            'claimUrl' => $claimUrl,
            'isSender' => $request->user()->id === $transmission->sender_id,
            'isReceiver' => $request->user()->id === $transmission->receiver_id,
            'canForward' => Gate::allows('forward', $transmission),
        ]);
    }

    public function claim(Request $request, string $token): RedirectResponse
    {
        $transmission = DocumentTransmission::where('share_token', $token)->firstOrFail();

        Gate::authorize('view', $transmission);

        return redirect()->route('document-transmissions.show', $transmission);
    }

    public function receive(
        ConfirmDocumentTransmissionReceiptRequest $request,
        DocumentTransmission $transmission,
    ): RedirectResponse {
        $ids = collect($request->validated('item_ids'))
            ->unique()
            ->values();

        DB::transaction(function () use ($request, $transmission, $ids) {
            $transmission->load('items');
            $userId = $request->user()->id;

            foreach ($transmission->items as $item) {
                $before = $item->received_at?->toIso8601String();
                if ($ids->contains($item->id)) {
                    $item->forceFill(['received_at' => now()])->save();
                } else {
                    $item->forceFill(['received_at' => null])->save();
                }
                $item->refresh();
                $after = $item->received_at?->toIso8601String();
                if ($before !== $after) {
                    $item->logActivity(DocumentTransmissionItemActivity::EVENT_RECEIPT_CHANGED, $userId, [
                        'marked_received' => $item->received_at !== null,
                    ]);
                }
            }

            $transmission->refreshCompletionState();
            $transmission->load('items');

            $received = $transmission->items->filter(fn (DocumentTransmissionItem $item) => $item->received_at !== null);
            $pending = $transmission->items->filter(fn (DocumentTransmissionItem $item) => $item->received_at === null);

            DocumentTransmissionHistory::create([
                'document_transmission_id' => $transmission->id,
                'user_id' => $request->user()->id,
                'event' => DocumentTransmissionHistory::EVENT_RECEIPT_CONFIRMED,
                'meta' => [
                    'received_document_labels' => $received->pluck('label')->values()->all(),
                    'pending_document_labels' => $pending->pluck('label')->values()->all(),
                    'received_count' => $received->count(),
                    'total_count' => $transmission->items->count(),
                    'status' => $transmission->status,
                    'completed_at' => $transmission->completed_at?->toIso8601String(),
                ],
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Receipt confirmed.',
        ]);

        return back();
    }

    public function searchRecipients(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim();

        if ($query->length() < 2) {
            return response()->json([]);
        }

        $qStr = (string) $query;

        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->whereNull('blocked_at')
            ->whereNotNull('email_verified_at')
            ->where(function ($q) use ($qStr) {
                $q->where('name', 'ilike', "%{$qStr}%")
                    ->orWhere('email', 'ilike', "%{$qStr}%");
            })
            ->orderBy('name')
            ->select('id', 'name', 'email')
            ->limit(25)
            ->get();

        return response()->json($users);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeListRow(DocumentTransmission $t, string $direction): array
    {
        $t->loadMissing('items');
        $total = $t->items->count();
        $checked = $t->items->filter(fn (DocumentTransmissionItem $i) => $i->received_at !== null)->count();

        $other = $direction === 'incoming' ? $t->sender : $t->receiver;

        return [
            'id' => $t->id,
            'purpose' => $t->purpose,
            'status' => $t->status,
            'completed_at' => $t->completed_at?->toIso8601String(),
            'created_at' => $t->created_at->toIso8601String(),
            'checklist' => ['checked' => $checked, 'total' => $total],
            'other_party' => $other
                ? ['id' => $other->id, 'name' => $other->name, 'email' => $other->email]
                : null,
            'is_forward' => $t->forwarded_from_id !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetail(DocumentTransmission $t): array
    {
        $forwardedFrom = null;
        if ($t->relationLoaded('forwardedFrom') && $t->forwardedFrom !== null) {
            $src = $t->forwardedFrom;
            $forwardedFrom = [
                'id' => $src->id,
                'purpose' => $src->purpose,
                'created_at' => $src->created_at?->toIso8601String(),
                'sender' => $src->sender
                    ? ['id' => $src->sender->id, 'name' => $src->sender->name]
                    : null,
            ];
        }

        return [
            'id' => $t->id,
            'purpose' => $t->purpose,
            'status' => $t->status,
            'share_token' => $t->share_token,
            'completed_at' => $t->completed_at?->toIso8601String(),
            'created_at' => $t->created_at->toIso8601String(),
            'forwarded_from' => $forwardedFrom,
            'sender' => $t->sender
                ? ['id' => $t->sender->id, 'name' => $t->sender->name, 'email' => $t->sender->email]
                : null,
            'receiver' => $t->receiver
                ? ['id' => $t->receiver->id, 'name' => $t->receiver->name, 'email' => $t->receiver->email]
                : null,
            'items' => $t->items->map(fn (DocumentTransmissionItem $i) => [
                'id' => $i->id,
                'label' => $i->label,
                'sort_order' => $i->sort_order,
                'received_at' => $i->received_at?->toIso8601String(),
                'attachment' => $i->hasAttachment()
                    ? [
                        'file_name' => $i->file_name,
                        'file_size' => $i->file_size,
                        'preview_url' => route('document-transmissions.items.file', [$t, $i, 'preview' => true]),
                        'download_url' => route('document-transmissions.items.file', [$t, $i]),
                    ]
                    : null,
                'activities' => $i->relationLoaded('activities')
                    ? $i->activities->map(fn (DocumentTransmissionItemActivity $a) => [
                        'id' => $a->id,
                        'event' => $a->event,
                        'meta' => $a->meta ?? [],
                        'created_at' => $a->created_at->toIso8601String(),
                        'actor' => $a->user
                            ? ['id' => $a->user->id, 'name' => $a->user->name]
                            : null,
                    ])->values()->all()
                    : [],
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeForwardSource(DocumentTransmission $t): array
    {
        return [
            'id' => $t->id,
            'purpose' => $t->purpose,
            'status' => $t->status,
            'created_at' => $t->created_at->toIso8601String(),
            'sender' => $t->sender
                ? ['id' => $t->sender->id, 'name' => $t->sender->name, 'email' => $t->sender->email]
                : null,
            'receiver' => $t->receiver
                ? ['id' => $t->receiver->id, 'name' => $t->receiver->name, 'email' => $t->receiver->email]
                : null,
            'items' => $t->items->map(fn (DocumentTransmissionItem $i) => [
                'id' => $i->id,
                'label' => $i->label,
                'has_attachment' => $i->hasAttachment(),
                'file_name' => $i->hasAttachment() ? $i->file_name : null,
            ])->values()->all(),
        ];
    }

    private function copyItemAttachmentTo(
        DocumentTransmissionItem $sourceItem,
        string $newTransmissionId,
        DocumentTransmissionItem $targetItem,
    ): void {
        if (! $sourceItem->disk || ! $sourceItem->file_path) {
            return;
        }

        $disk = $sourceItem->disk;
        $filesystem = Storage::disk($disk);
        if (! $filesystem->exists($sourceItem->file_path)) {
            return;
        }

        $ext = pathinfo($sourceItem->file_name ?? 'file.pdf', PATHINFO_EXTENSION);
        $ext = is_string($ext) && $ext !== '' ? $ext : 'pdf';
        $newPath = 'document-handoffs/'.$newTransmissionId.'/'.$targetItem->id.'.'.$ext;

        if (! $filesystem->copy($sourceItem->file_path, $newPath)) {
            return;
        }

        if (! $filesystem->exists($newPath)) {
            return;
        }

        $targetItem->forceFill([
            'file_path' => $newPath,
            'file_name' => $sourceItem->file_name,
            'file_size' => $sourceItem->file_size,
            'disk' => $disk,
        ])->save();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializeHandoffHistory(DocumentTransmission $transmission): array
    {
        return $transmission->histories
            ->map(fn (DocumentTransmissionHistory $row) => [
                'id' => $row->id,
                'event' => $row->event,
                'meta' => $row->meta ?? [],
                'created_at' => $row->created_at->toIso8601String(),
                'actor' => $row->user
                    ? ['id' => $row->user->id, 'name' => $row->user->name]
                    : null,
            ])
            ->values()
            ->all();
    }
}
