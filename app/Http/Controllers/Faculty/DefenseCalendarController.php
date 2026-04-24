<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Support\DefenseCalendarEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DefenseCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = max(1, min(12, (int) $request->get('month', now()->month)));
        $year = max(2000, min(2100, (int) $request->get('year', now()->year)));

        [$start, $end] = DefenseCalendarEvents::monthStartEndInAppTimezone($year, $month);

        $classIds = SchoolClass::where('faculty_id', $request->user()->id)->pluck('id');

        $studentIds = DB::table('school_class_members')
            ->whereIn('school_class_id', $classIds)
            ->pluck('student_id')
            ->unique();

        $facultyUserId = $request->user()->id;
        $facultyName = $request->user()->name;

        $papers = ResearchPaper::with(['user', 'schoolClass', 'panelDefenses'])
            ->where(function ($q) use ($studentIds, $facultyUserId, $facultyName) {
                $q->whereIn('user_id', $studentIds)
                    ->orWhere('adviser_id', $facultyUserId)
                    ->orWhere('statistician_id', $facultyUserId)
                    ->orWhereHas('panelDefenses', fn ($pq) => $pq->whereRaw(
                        'panel_members::jsonb @> ?::jsonb',
                        [json_encode([$facultyName])]
                    ));
            })
            ->where(function ($q) use ($start, $end) {
                DefenseCalendarEvents::addScheduleInMonthConstraint($q, $start, $end);
            })
            ->get();

        $events = DefenseCalendarEvents::build($papers, $start, $end, true);

        $classes = SchoolClass::where('faculty_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'section']);

        return Inertia::render('faculty/DefenseCalendar/Index', [
            'events' => $events,
            'month' => $month,
            'year' => $year,
            'classes' => $classes,
        ]);
    }
}
