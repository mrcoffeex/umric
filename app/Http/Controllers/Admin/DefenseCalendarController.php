<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Support\DefenseCalendarEvents;
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

        $papersQuery = ResearchPaper::query()
            ->with(['user', 'schoolClass', 'panelDefenses']);

        DefenseCalendarEvents::addScheduleInMonthConstraint(
            $papersQuery,
            $start,
            $end,
        );

        $papers = $papersQuery->get();

        $events = DefenseCalendarEvents::build($papers, $start, $end, true);

        $classes = SchoolClass::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'section']);

        return Inertia::render('admin/DefenseCalendar/Index', [
            'events' => $events,
            'month' => $month,
            'year' => $year,
            'classes' => $classes,
        ]);
    }
}
