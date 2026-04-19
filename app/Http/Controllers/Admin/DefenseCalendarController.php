<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DefenseCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = max(1, min(12, (int) $request->get('month', now()->month)));
        $year = max(2000, min(2100, (int) $request->get('year', now()->year)));

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $papers = ResearchPaper::with(['user', 'schoolClass'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('outline_defense_schedule', [$start, $end])
                    ->orWhereBetween('final_defense_schedule', [$start, $end]);
            })
            ->get();

        $events = $this->buildEvents($papers, $start, $end);

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

    private function buildEvents($papers, Carbon $start, Carbon $end): array
    {
        $events = [];

        foreach ($papers as $paper) {
            if (
                $paper->outline_defense_schedule
                && $paper->outline_defense_schedule->between($start, $end)
            ) {
                $events[] = [
                    'id' => $paper->id.'-outline',
                    'paper_id' => $paper->id,
                    'tracking_id' => $paper->tracking_id,
                    'title' => $paper->title,
                    'type' => 'outline_defense',
                    'schedule' => $paper->outline_defense_schedule->toISOString(),
                    'step_status' => $paper->step_outline_defense,
                    'student' => $paper->user ? ['name' => $paper->user->name, 'email' => $paper->user->email] : null,
                    'school_class' => $paper->schoolClass ? ['name' => $paper->schoolClass->name, 'section' => $paper->schoolClass->section] : null,
                ];
            }

            if (
                $paper->final_defense_schedule
                && $paper->final_defense_schedule->between($start, $end)
            ) {
                $events[] = [
                    'id' => $paper->id.'-final',
                    'paper_id' => $paper->id,
                    'tracking_id' => $paper->tracking_id,
                    'title' => $paper->title,
                    'type' => 'final_defense',
                    'schedule' => $paper->final_defense_schedule->toISOString(),
                    'step_status' => $paper->step_final_defense,
                    'student' => $paper->user ? ['name' => $paper->user->name, 'email' => $paper->user->email] : null,
                    'school_class' => $paper->schoolClass ? ['name' => $paper->schoolClass->name, 'section' => $paper->schoolClass->section] : null,
                ];
            }
        }

        usort($events, fn ($a, $b) => strcmp($a['schedule'], $b['schedule']));

        return $events;
    }
}
