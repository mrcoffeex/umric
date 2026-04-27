<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Mail\ResearchStatusUpdated;
use App\Models\Agenda;
use App\Models\PaperFile;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Sdg;
use App\Models\TrackingRecord;
use App\Services\DocumentExtractorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

class ResearchController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $userId = $request->user()->id;

        $papers = ResearchPaper::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereRaw('"proponents"::jsonb @> ?::jsonb', [json_encode([['id' => (string) $userId]])]);
            })
            ->with(['schoolClass', 'user', 'adviser'])
            ->latest()
            ->get();

        return Inertia::render('student/Research/Index', [
            'papers' => $papers,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'steps' => ResearchPaper::STEPS,
        ]);
    }

    public function show(Request $request, ResearchPaper $paper): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $userId = $request->user()->id;

        if (! $paper->isOwnerOrProponent($userId)) {
            abort(403);
        }

        $paper->load([
            'schoolClass',
            'trackingRecords.updatedBy',
            'adviser',
            'statistician',
            'panelDefenses.createdBy',
            'comments.user',
            'files' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $defenseMayManage = $paper->isOwnerOrProponent($userId);

        return Inertia::render('student/Research/Show', [
            'paper' => $paper,
            'trackingPublicUrl' => url('/track/'.$paper->tracking_id),
            'defenseDocumentUpload' => [
                'mayManage' => $defenseMayManage,
                'ricReturned' => $paper->isRicReviewReturned(),
                'outline' => $defenseMayManage && $paper->mayStudentUploadDefenseDocument('outline'),
                'final' => $defenseMayManage && $paper->mayStudentUploadDefenseDocument('final'),
                'outlineUploaded' => $paper->hasStudentDefenseDocument('outline'),
                'finalUploaded' => $paper->hasStudentDefenseDocument('final'),
            ],
            'trackingLog' => $paper->trackingRecords,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'steps' => ResearchPaper::STEPS,
            'sdgs' => Sdg::select('id', 'name', 'number')->orderBy('number')->get(),
            'agendas' => Agenda::select('id', 'name')->orderBy('name')->get(),
            'panelDefenses' => $paper->panelDefenses->map(fn ($pd) => [
                'id' => $pd->id,
                'defense_type' => $pd->defense_type,
                'defense_type_label' => $pd->defense_type_label,
                'panel_members' => $pd->panel_members,
                'schedule' => $pd->schedule?->toDateTimeString(),
                'notes' => $pd->notes,
                'created_by' => $pd->createdBy ? ['id' => $pd->createdBy->id, 'name' => $pd->createdBy->name] : null,
                'created_at' => $pd->created_at->toISOString(),
            ])->values(),
            'comments' => $paper->comments->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
                'created_at' => $c->created_at->toISOString(),
            ])->values(),
        ]);
    }

    public function extractMetadata(Request $request): JsonResponse
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $request->validate([
            'file' => [
                'required',
                'max:'.config('uploads.max_size_kb'),
                File::types(['pdf', 'docx']),
            ],
        ]);

        $result = (new DocumentExtractorService)->extract($request->file('file'));

        return response()->json($result);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $hasClass = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $request->user()->id))
            ->exists();

        if (! $hasClass) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'You must join a class before creating a title proposal.']);

            return to_route('student.classes.index');
        }

        return Inertia::render('student/Research/Create', [
            'auth_user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $validated = $request->validate(
            [
                'title' => ['required', 'string', 'max:255'],
                'abstract' => ['nullable', 'string'],
                'proponents' => ['nullable', 'array'],
                'keywords' => ['nullable', 'string', 'max:500'],
                'file' => ['nullable', 'file', 'mimes:pdf', 'max:'.config('uploads.max_size_kb')],
            ],
            [],
            ['abstract' => 'rationale'],
        );

        $schoolClass = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $request->user()->id))
            ->first();

        if (! $schoolClass) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => 'You must join a class before submitting a title proposal.']);

            return to_route('student.classes.index');
        }

        $paper = ResearchPaper::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'abstract' => $validated['abstract'] ?? null,
            'proponents' => $validated['proponents'] ?? null,
            'keywords' => $validated['keywords'] ?? null,
            'school_class_id' => $schoolClass->id,
            'tracking_id' => 'RP-'.strtoupper(Str::random(8)),
            'current_step' => 'title_proposal',
            'submission_date' => now(),
            'status' => 'submitted',
        ]);

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            $paper->files()->create([
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_path' => $uploadedFile->store('papers', $disk),
                'file_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'file_category' => PaperFile::CATEGORY_TITLE,
                'disk' => $disk,
            ]);
        }

        TrackingRecord::log(
            $paper->id,
            'title_proposal',
            'Title proposal submitted',
            'submitted',
            null,
            $request->user()->id,
        );

        ResearchStatusUpdated::dispatch(
            $paper->fresh(),
            'title_proposal',
            ResearchPaper::STEP_LABELS['title_proposal'] ?? 'Title Evaluation',
            'submitted',
            null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Research paper submitted.']);

        return redirect()->route('student.research.show', $paper);
    }

    public function edit(Request $request, ResearchPaper $paper): Response
    {
        if (! $request->user()->isStudent() || $paper->user_id !== $request->user()->id) {
            abort(403);
        }

        // Only allow editing if it's still a title proposal
        if ($paper->current_step !== 'title_proposal') {
            abort(403);
        }

        $paper->load('files');

        return Inertia::render('student/Research/Edit', [
            'paper' => $paper,
            'auth_user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
            ],
        ]);
    }

    public function update(Request $request, ResearchPaper $paper): RedirectResponse
    {
        if (! $request->user()->isStudent() || $paper->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($paper->current_step !== 'title_proposal') {
            return back()->withErrors(['step' => 'Only title proposals can be edited.']);
        }

        $validated = $request->validate(
            [
                'title' => ['required', 'string', 'max:255'],
                'abstract' => ['nullable', 'string'],
                'proponents' => ['nullable', 'array'],
                'keywords' => ['nullable', 'string', 'max:500'],
                'file' => ['nullable', 'file', 'mimes:pdf', 'max:'.config('uploads.max_size_kb')],
            ],
            [],
            ['abstract' => 'rationale'],
        );

        $resubmitAfterRicReturn = $paper->step_ric_review === 'returned';

        $paper->update([
            'title' => $validated['title'],
            'abstract' => $validated['abstract'] ?? $paper->abstract,
            'proponents' => $validated['proponents'] ?? $paper->proponents,
            'keywords' => array_key_exists('keywords', $validated) ? $validated['keywords'] : $paper->keywords,
        ]);

        if ($resubmitAfterRicReturn) {
            $paper->update([
                'step_ric_review' => 'pending',
                'current_step' => 'ric_review',
            ]);

            TrackingRecord::log(
                $paper->id,
                'title_proposal',
                'Title proposal resubmitted after RIC return',
                'submitted',
                'returned',
                $request->user()->id,
            );

            $paper = $paper->fresh();
            ResearchStatusUpdated::dispatch(
                $paper,
                'ric_review',
                ResearchPaper::STEP_LABELS['ric_review'] ?? 'RIC/Admin Review',
                (string) ($paper->step_ric_review ?? 'pending'),
                null,
            );
        }

        if ($request->hasFile('file')) {
            $paper->files()->where('file_category', PaperFile::CATEGORY_TITLE)->get()->each(function ($file) {
                Storage::disk($file->disk)->delete($file->file_path);
                $file->delete();
            });

            $uploadedFile = $request->file('file');
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            $paper->files()->create([
                'file_name' => $uploadedFile->getClientOriginalName(),
                'file_path' => $uploadedFile->store('papers', $disk),
                'file_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'file_category' => PaperFile::CATEGORY_TITLE,
                'disk' => $disk,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Research paper updated.']);

        return back();
    }

    public function destroy(Request $request, ResearchPaper $paper): RedirectResponse
    {
        if (! $request->user()->isStudent() || $paper->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($paper->current_step !== 'title_proposal') {
            return back()->withErrors(['step' => 'Only title proposals can be deleted.']);
        }

        $paper->trackingRecords()->delete();
        $paper->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Research paper deleted.']);

        return redirect()->route('student.research.index');
    }

    public function uploadDefenseDocument(Request $request, ResearchPaper $paper): RedirectResponse
    {
        $userId = $request->user()->id;

        if (! $request->user()->isStudent() || ! $paper->isOwnerOrProponent($userId)) {
            abort(403);
        }

        $validated = $request->validate([
            'defense' => ['required', Rule::in(['outline', 'final'])],
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.config('uploads.max_size_kb')],
        ]);

        $defense = $validated['defense'];

        if (! $paper->mayStudentUploadDefenseDocument($defense)) {
            abort(403);
        }

        $expectedStep = $defense === 'outline' ? 'outline_defense' : 'final_defense';

        $category = $defense === 'outline'
            ? PaperFile::CATEGORY_OUTLINE_DEFENSE
            : PaperFile::CATEGORY_FINAL_DEFENSE;

        $uploadedFile = $request->file('file');
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        $paper->files()->create([
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $uploadedFile->store('papers', $disk),
            'file_type' => $uploadedFile->getMimeType(),
            'file_size' => $uploadedFile->getSize(),
            'file_category' => $category,
            'disk' => $disk,
        ]);

        $stepLabel = $defense === 'outline' ? 'Outline defense' : 'Final defense';

        TrackingRecord::log(
            $paper->id,
            $expectedStep,
            $stepLabel.' document uploaded',
            'document_uploaded',
            null,
            $request->user()->id,
            $uploadedFile->getClientOriginalName(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => $stepLabel.' document added to your research files.']);

        return back();
    }
}
