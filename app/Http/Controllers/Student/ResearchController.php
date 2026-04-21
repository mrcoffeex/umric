<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
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
        $isProponent = collect($paper->proponents ?? [])->contains('id', (string) $userId);

        if ($paper->user_id !== $userId && ! $isProponent) {
            abort(403);
        }

        $paper->load(['schoolClass', 'trackingRecords.updatedBy', 'adviser', 'statistician', 'panelDefenses.createdBy', 'comments.user', 'files']);

        return Inertia::render('student/Research/Show', [
            'paper' => $paper,
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
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:'.config('uploads.max_size_kb')],
        ]);

        $result = (new DocumentExtractorService)->extract($request->file('file'));

        return response()->json($result);
    }

    public function create(Request $request): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        return Inertia::render('student/Research/Create', [
            'sdgs' => Sdg::select('id', 'name', 'number')->orderBy('number')->get(),
            'agendas' => Agenda::select('id', 'name')->orderBy('name')->get(),
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

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'proponents' => ['nullable', 'array'],
            'sdg_ids' => ['nullable', 'array'],
            'sdg_ids.*' => ['string', 'exists:sdgs,id'],
            'agenda_ids' => ['nullable', 'array'],
            'agenda_ids.*' => ['string', 'exists:agendas,id'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:'.config('uploads.max_size_kb')],
        ]);

        $schoolClass = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $request->user()->id))
            ->first();

        if (! $schoolClass) {
            return back()->withErrors(['class' => 'Join a class before submitting a research paper.']);
        }

        $paper = ResearchPaper::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'abstract' => $validated['abstract'] ?? null,
            'proponents' => $validated['proponents'] ?? null,
            'sdg_ids' => $validated['sdg_ids'] ?? null,
            'agenda_ids' => $validated['agenda_ids'] ?? null,
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
            'sdgs' => Sdg::select('id', 'name', 'number')->orderBy('number')->get(),
            'agendas' => Agenda::select('id', 'name')->orderBy('name')->get(),
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

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string'],
            'proponents' => ['nullable', 'array'],
            'sdg_ids' => ['nullable', 'array'],
            'sdg_ids.*' => ['string', 'exists:sdgs,id'],
            'agenda_ids' => ['nullable', 'array'],
            'agenda_ids.*' => ['string', 'exists:agendas,id'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:'.config('uploads.max_size_kb')],
        ]);

        $paper->update([
            'title' => $validated['title'],
            'abstract' => $validated['abstract'] ?? $paper->abstract,
            'proponents' => $validated['proponents'] ?? $paper->proponents,
            'sdg_ids' => array_key_exists('sdg_ids', $validated) ? $validated['sdg_ids'] : $paper->sdg_ids,
            'agenda_ids' => array_key_exists('agenda_ids', $validated) ? $validated['agenda_ids'] : $paper->agenda_ids,
            'keywords' => array_key_exists('keywords', $validated) ? $validated['keywords'] : $paper->keywords,
        ]);

        if ($request->hasFile('file')) {
            $paper->files()->each(function ($file) {
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
}
