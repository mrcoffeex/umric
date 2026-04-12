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
        $role = $user?->role() ?? 'student';
        $papersQuery = ResearchPaper::with(['category', 'authors'])->latest();

        if (! $user?->isAdmin() && ! $user?->isStaff()) {
            $papersQuery->where('user_id', Auth::id());
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
            ->whereHas('profile', fn ($q) => $q->where('role', 'student'))
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
        if (empty($proponents) || ($proponents[0]['id'] ?? null) !== $request->user()->id) {
            return back()->withErrors(['proponents' => 'You must be the first proponent.']);
        }

        $tracking_id = 'RP-'.strtoupper(Str::random(8));
        $user = Auth::user();

        $paper = ResearchPaper::create([
            'title' => $validated['title'],
            'abstract' => $validated['abstract'],
            'category_id' => $validated['category_id'] ?? null,
            'sdg_id' => $validated['sdg_id'] ?? null,
            'agenda_id' => $validated['agenda_id'] ?? null,
            'proponents' => $request->proponents,
            'status' => $user?->isStudent() ? 'submitted' : ($request->status ?? 'submitted'),
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
            $path = $file->store('research-papers', 'public');

            $paper->files()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_category' => 'paper',
                'disk' => 'public',
            ]);
        }

        // Create initial tracking record
        $paper->trackingRecords()->create([
            'status' => 'submitted',
            'notes' => 'Paper submitted',
            'updated_by' => Auth::id(),
            'status_changed_at' => now(),
        ]);

        return redirect()->route('papers.show', $paper)->with('success', 'Paper submitted successfully');
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
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResearchPaper $paper)
    {
        Gate::authorize('update', $paper);

        return Inertia::render('Research/Edit', [
            'paper' => $paper->load('category', 'authors', 'sdg', 'agenda'),
            'categories' => Category::all(),
            'sdgs' => Sdg::all(),
            'agendas' => Agenda::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResearchPaperRequest $request, ResearchPaper $paper)
    {
        $validated = $request->validated();

        $paper->update(Arr::only($validated, [
            'title',
            'abstract',
            'category_id',
            'sdg_id',
            'agenda_id',
            'status',
            'keywords',
            'proponents',
        ]));

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('research-papers', 'public');

            $paper->files()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_category' => 'paper',
                'disk' => 'public',
            ]);
        }

        return redirect()->route('papers.show', $paper)->with('success', 'Paper updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResearchPaper $paper)
    {
        Gate::authorize('delete', $paper);
        $paper->delete();

        return redirect()->route('papers.index')->with('success', 'Paper deleted successfully');
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
                'files',
                'citations',
                'publication',
                'trackingRecords' => function ($query) {
                    $query->latest();
                },
            ])
            ->firstOrFail();

        return Inertia::render('Research/PublicTracking', [
            'paper' => $paper,
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
