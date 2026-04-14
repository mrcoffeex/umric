<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $role = $user->profile?->role ?? 'student';

        $announcements = Announcement::active()
            ->forRole($role)
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->take(5)
            ->get(['id', 'title', 'content', 'type', 'is_pinned', 'published_at']);

        if ($role === 'student') {
            return redirect()->to(route('student.home'));
        }

        $data = match ($role) {
            'admin', 'staff' => $this->adminData($user),
            default => $this->facultyData($user),
        };

        return Inertia::render('Dashboard', array_merge($data, [
            'role' => $role,
            'announcements' => $announcements,
            'stepLabels' => ResearchPaper::STEP_LABELS,
        ]));
    }

    private function adminData(User $user): array
    {
        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = ResearchPaper::where('current_step', $step)->count();
        }

        return [
            'stats' => [
                'totalPapers' => ResearchPaper::count(),
                'pendingReview' => ResearchPaper::pendingReview()->count(),
                'completed' => ResearchPaper::where('current_step', 'completed')->count(),
                'totalUsers' => User::count(),
                'pendingApproval' => User::whereHas('profile', fn ($q) => $q->whereNull('approved_at'))->count(),
            ],
            'stepCounts' => $stepCounts,
            'statusCounts' => ResearchPaper::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'recentPapers' => ResearchPaper::with(['user', 'schoolClass'])
                ->latest()
                ->take(8)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'tracking_id' => $p->tracking_id,
                    'current_step' => $p->current_step,
                    'step_label' => $p->step_label,
                    'student_name' => $p->user->name,
                    'class_name' => $p->schoolClass?->name,
                    'created_at' => $p->created_at->toISOString(),
                ]),
        ];
    }

    private function facultyData(User $user): array
    {
        $classes = SchoolClass::where('faculty_id', $user->id)
            ->withCount(['members', 'researchPapers'])
            ->get();

        $myPapers = ResearchPaper::whereHas('schoolClass', fn ($q) => $q->where('faculty_id', $user->id))
            ->orWhere('adviser_id', $user->id);

        $stepCounts = [];
        foreach (ResearchPaper::STEPS as $step) {
            $stepCounts[$step] = (clone $myPapers)->where('current_step', $step)->count();
        }

        return [
            'stats' => [
                'totalClasses' => $classes->count(),
                'totalStudents' => $classes->sum('members_count'),
                'totalPapers' => (clone $myPapers)->count(),
                'pendingActions' => (clone $myPapers)->whereIn('current_step', ['outline_defense', 'rating', 'final_defense'])->count(),
            ],
            'stepCounts' => $stepCounts,
            'statusCounts' => (clone $myPapers)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'classes' => $classes->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'members_count' => $c->members_count,
                'research_papers_count' => $c->research_papers_count,
            ]),
            'recentPapers' => (clone $myPapers)->with('user')->latest()->take(5)->get()->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'tracking_id' => $p->tracking_id,
                'current_step' => $p->current_step,
                'step_label' => $p->step_label,
                'student_name' => $p->user->name,
                'created_at' => $p->created_at->toISOString(),
            ]),
        ];
    }
}
