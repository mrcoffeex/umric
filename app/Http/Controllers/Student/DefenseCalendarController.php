<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Support\DefenseCalendarEvents;
use App\Support\JsonContains;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DefenseCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = max(1, min(12, (int) $request->get('month', now()->month)));
        $year = max(2000, min(2100, (int) $request->get('year', now()->year)));

        [$start, $end] = DefenseCalendarEvents::monthStartEndInAppTimezone($year, $month);

        $userId = $request->user()->id;

        $papers = ResearchPaper::with(['schoolClass', 'panelDefenses', 'workflowVersion.steps'])
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId);
                JsonContains::whereArrayObjectContains(
                    $q,
                    'proponents',
                    ['id' => (string) $userId],
                    or: true,
                );
            })
            ->where(function ($q) use ($start, $end) {
                DefenseCalendarEvents::addScheduleInMonthConstraint($q, $start, $end);
            })
            ->get();

        $events = DefenseCalendarEvents::build($papers, $start, $end, false);

        return Inertia::render('student/DefenseCalendar/Index', [
            'events' => $events,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
