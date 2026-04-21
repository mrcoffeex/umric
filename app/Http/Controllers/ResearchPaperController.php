<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResearchPaperRequest;
use App\Http\Requests\UpdateResearchPaperRequest;
use App\Models\Agenda;
use App\Models\Category;
use App\Models\ResearchPaper;
use App\Models\Sdg;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ResearchPaperController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $profile = $user?->profile;
        $role = $profile?->role ?? 'student';
        $papersQuery = ResearchPaper::with(['category', 'authors'])->latest();

        if ($profile?->role !== 'admin' && $profile?->role !== 'staff') {
            $papersQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereRaw('proponents::jsonb @> ?', [json_encode([['id' => (string) $user->id]])])
                    ->orWhereRaw('proponents::jsonb @> ?', [json_encode([['id' => $user->id]])]);
            });
        }

        return Inertia::render('Research/Index', [
            'papers' => $papersQuery->get(),
            'categories' => Category::all(),
            'sdgs' => Sdg::query()->get(),
            'agendas' => Agenda::query()->get(),
            'role' => $role,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return Inertia::render('Research/Create', [
            'categories' => Category::all(),
            'sdgs' => Sdg::all(),
            'agendas' => Agenda::all(),
            'auth_user' => ['id' => $request->user()->id, 'name' => $request->user()->name],
        ]);
    }

    public function searchProponents(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim();

        $users = User::query()
            ->where(function ($q) {
                $q->whereDoesntHave('profile')
                    ->orWhereHas('profile', fn ($q2) => $q2->where('role', 'student'));
            })
            ->whereNull('blocked_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                    ->orWhere('email', 'ilike', "%{$query}%");
            })
            ->where('id', '!=', $request->user()->id)
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResearchPaperRequest $request)
    {
        $validated = $request->validated();

        // Validate first proponent is the authenticated user
        $proponents = $request->input('proponents', []);
        if (empty($proponents) || (string) ($proponents[0]['id'] ?? '') !== (string) $request->user()->id) {
            return back()->withErrors(['proponents' => 'You must be the first proponent.']);
        }

        $tracking_id = 'RP-'.strtoupper(Str::random(8));
        $user = Auth::user();
        $profile = $user?->profile;

        $paper = ResearchPaper::create([
            'title' => $validated['title'],
            'abstract' => $validated['abstract'],
            'category_id' => $validated['category_id'] ?? null,
            'sdg_ids' => array_map('intval', $validated['sdg_ids'] ?? []),
            'agenda_ids' => array_map('intval', $validated['agenda_ids'] ?? []),
            'proponents' => $request->proponents,
            'status' => $profile?->role === 'student' ? 'submitted' : ($request->status ?? 'submitted'),
            'tracking_id' => $tracking_id,
            'user_id' => Auth::id(),
            'keywords' => $validated['keywords'] ?? null,
        ]);

        // Add authors
        if ($request->has('authors')) {
            foreach ($request->authors as $index => $author) {
                if (! empty($author)) {
                    $authorUser = User::firstOrCreate(
                        ['email' => strtolower(str_replace(' ', '.', $author)).'@research.local'],
                        ['name' => $author, 'password' => bcrypt(Str::random())]
                    );
                    $paper->authors()->attach($authorUser->id, ['author_order' => $index + 1]);
                }
            }
        }

        // Store file if uploaded
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            $path = $file->store('research-papers', $disk);

            $paper->files()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_category' => 'paper',
                'disk' => $disk,
            ]);
        }

        // Create initial tracking record
        $paper->trackingRecords()->create([
            'status' => 'submitted',
            'notes' => 'Paper submitted',
            'updated_by' => Auth::id(),
            'status_changed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paper submitted successfully.']);

        return redirect()->route('papers.show', $paper);
    }

    /**
     * Display the specified resource.
     */
    public function show(ResearchPaper $paper)
    {
        Gate::authorize('view', $paper);

        $paper->load([
            'category',
            'authors',
            'files',
            'citations',
            'publication',
            'trackingRecords' => function ($query) {
                $query->latest();
            },
        ]);

        return Inertia::render('Research/Show', [
            'paper' => $paper,
            'steps' => ResearchPaper::STEPS,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'sdgs' => Sdg::all(),
            'agendas' => Agenda::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ResearchPaper $paper)
    {
        Gate::authorize('update', $paper);

        return Inertia::render('Research/Edit', [
            'paper' => $paper->load('category', 'authors', 'files'),
            'categories' => Category::all(),
            'sdgs' => Sdg::all(),
            'agendas' => Agenda::all(),
            'auth_user' => ['id' => $request->user()->id, 'name' => $request->user()->name],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResearchPaperRequest $request, ResearchPaper $paper)
    {
        $validated = $request->validated();

        $data = Arr::only($validated, [
            'title',
            'abstract',
            'category_id',
            'sdg_ids',
            'agenda_ids',
            'status',
            'keywords',
            'proponents',
        ]);

        if (isset($data['sdg_ids'])) {
            $data['sdg_ids'] = array_map('intval', $data['sdg_ids'] ?? []);
        }

        if (isset($data['agenda_ids'])) {
            $data['agenda_ids'] = array_map('intval', $data['agenda_ids'] ?? []);
        }

        $paper->update($data);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            $path = $file->store('research-papers', $disk);

            $paper->files()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_category' => 'paper',
                'disk' => $disk,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paper updated successfully.']);

        return redirect()->route('papers.show', $paper);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResearchPaper $paper)
    {
        Gate::authorize('delete', $paper);
        $paper->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paper deleted successfully.']);

        return redirect()->route('papers.index');
    }

    /**
     * Display public tracking for a paper
     */
    public function publicTracking($trackingId)
    {
        $paper = ResearchPaper::where('tracking_id', $trackingId)
            ->with([
                'category',
                'authors',
                'citations',
                'publication',
                'schoolClass',
                'trackingRecords' => function ($query) {
                    $query->latest();
                },
            ])
            ->firstOrFail();

        return Inertia::render('Research/PublicTracking', [
            'paper' => $paper,
            'steps' => ResearchPaper::STEPS,
            'stepLabels' => ResearchPaper::STEP_LABELS,
        ]);
    }

    /**
     * Generate QR code for paper
     */
    public function generateQR(ResearchPaper $paper)
    {
        return response()->json([
            'qr_url' => route('papers.publicTracking', $paper->tracking_id),
        ]);
    }

    /**
     * Store file for paper
     */
    public function storeFile(ResearchPaper $paper)
    {
        Gate::authorize('update', $paper);

        // File storage logic here
        return response()->json(['success' => true]);
    }
}
