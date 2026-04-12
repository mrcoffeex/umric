<?php

namespace App\Http\Controllers;

use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Log out blocked users
        if ($user->isBlocked()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->with(
                'status',
                'Your account has been blocked. Please contact an administrator.'
            );
        }

        // Log out unapproved faculty and redirect to login with a notice
        if ($user->isFaculty() && ! $user->isApproved()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->with(
                'status',
                'Your faculty account is pending admin approval. An administrator will review and approve your registration shortly.'
            );
        }

        $role = $user->role();

        if (in_array($role, ['staff', 'admin'])) {
            return $this->staffDashboard($user);
        }

        if ($role === 'faculty') {
            return $this->facultyDashboard($user);
        }

        return $this->studentDashboard($user);
    }

    private function studentDashboard(User $user): Response
    {
        return Inertia::render('Dashboard', [
            'role' => 'student',
            'profileComplete' => (bool) ($user->profile?->department_id),
            'stats' => [
                'totalPapers' => ResearchPaper::where('user_id', $user->id)->count(),
                'published' => ResearchPaper::where('user_id', $user->id)->where('status', 'published')->count(),
                'underReview' => ResearchPaper::where('user_id', $user->id)->where('status', 'under_review')->count(),
                'totalViews' => (int) ResearchPaper::where('user_id', $user->id)->sum('views'),
            ],
            'recentPapers' => ResearchPaper::where('user_id', $user->id)
                ->with('category')
                ->latest()
                ->take(5)
                ->get(),
            'statusCounts' => ResearchPaper::where('user_id', $user->id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ]);
    }

    private function facultyDashboard(User $user): Response
    {
        $ownIds = ResearchPaper::where('user_id', $user->id)->pluck('id');
        $authoredIds = $user->authoredPapers()->pluck('research_papers.id');
        $allIds = $ownIds->merge($authoredIds)->unique()->values();

        return Inertia::render('Dashboard', [
            'role' => 'faculty',
            'stats' => [
                'totalPapers' => $allIds->count(),
                'published' => ResearchPaper::whereIn('id', $allIds)->where('status', 'published')->count(),
                'underReview' => ResearchPaper::whereIn('id', $allIds)->where('status', 'under_review')->count(),
                'totalViews' => (int) ResearchPaper::whereIn('id', $allIds)->sum('views'),
                'authoredPapers' => $authoredIds->count(),
            ],
            'recentPapers' => ResearchPaper::whereIn('id', $allIds)
                ->with('category', 'user')
                ->latest()
                ->take(5)
                ->get(),
            'statusCounts' => ResearchPaper::whereIn('id', $allIds)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ]);
    }

    private function staffDashboard(User $user): Response
    {
        return Inertia::render('Dashboard', [
            'role' => $user->role(),
            'stats' => [
                'totalPapers' => ResearchPaper::count(),
                'published' => ResearchPaper::where('status', 'published')->count(),
                'underReview' => ResearchPaper::where('status', 'under_review')->count(),
                'totalViews' => (int) ResearchPaper::sum('views'),
                'pendingReview' => ResearchPaper::where('status', 'submitted')->count(),
                'totalUsers' => User::count(),
            ],
            'recentPapers' => ResearchPaper::with('category', 'user')
                ->latest()
                ->take(5)
                ->get(),
            'statusCounts' => ResearchPaper::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ]);
    }
}
