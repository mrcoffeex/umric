<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResearchPaperRequest;
use App\Http\Requests\UpdateResearchPaperRequest;
use App\Models\Category;
use App\Models\ResearchPaper;
use App\Models\User;
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
        return Inertia::render('Research/Index', [
            'papers' => ResearchPaper::with(['category', 'authors'])->latest()->get(),
            'categories' => Category::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Research/Create', [
            'categories' => Category::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResearchPaperRequest $request)
    {
        $tracking_id = 'RP-'.strtoupper(Str::random(8));

        $paper = ResearchPaper::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'status' => $request->status ?? 'submitted',
            'tracking_id' => $tracking_id,
            'user_id' => Auth::id(),
            'keywords' => $request->keywords,
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
        return Inertia::render('Research/Edit', [
            'paper' => $paper->load('category', 'authors'),
            'categories' => Category::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResearchPaperRequest $request, ResearchPaper $paper)
    {
        $paper->update($request->validated());

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
