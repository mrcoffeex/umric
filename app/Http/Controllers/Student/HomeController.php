<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $request->user()->isStudent()) {
            abort(403);
        }

        $announcements = Announcement::active()
            ->forRole('student')
            ->orderByDesc('is_pinned')
            ->take(10)
            ->get();

        $classes = SchoolClass::query()
            ->whereHas('members', fn ($query) => $query->where('student_id', $request->user()->id))
            ->with('subjects.program')
            ->get();

        $paper = ResearchPaper::query()
            ->where('user_id', $request->user()->id)
            ->first();

        return Inertia::render('student/Home', [
            'announcements' => $announcements,
            'classes' => $classes,
            'paper' => $paper,
            'stepLabels' => ResearchPaper::STEP_LABELS,
            'steps' => ResearchPaper::STEPS,
        ]);
    }
}
