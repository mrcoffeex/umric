<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
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

        $userId = $request->user()->id;

        $papers = ResearchPaper::with(['schoolClass', 'panelDefenses'])
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereRaw('"proponents"::jsonb @> ?::jsonb', [json_encode([['id' => (string) $userId]])]);
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('outline_defense_schedule', [$start, $end])
                    ->orWhereBetween('final_defense_schedule', [$start, $end]);
            })
            ->get();

        $events = $this->buildEvents($papers, $start, $end);

        return Inertia::render('student/DefenseCalendar/Index', [
            'events' => $events,
            'month' => $month,
            'year' => $year,
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
                    'school_class' => $paper->schoolClass ? ['name' => $paper->schoolClass->name, 'section' => $paper->schoolClass->section] : null,
                    'panel_members' => $this->uniquePanelMembers(
                        $paper->panelDefenses
                            ->where('defense_type', 'outline')
                            ->first()?->panel_members,
                    ),
                    'proponents' => $this->extractProponentNames($paper->proponents),
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
                    'school_class' => $paper->schoolClass ? ['name' => $paper->schoolClass->name, 'section' => $paper->schoolClass->section] : null,
                    'panel_members' => $this->uniquePanelMembers(
                        $paper->panelDefenses
                            ->where('defense_type', 'final')
                            ->first()?->panel_members,
                    ),
                    'proponents' => $this->extractProponentNames($paper->proponents),
                ];
            }
        }

        usort($events, fn ($a, $b) => strcmp($a['schedule'], $b['schedule']));

        return $events;
    }

    private function uniquePanelMembers(mixed $panelMembers): array
    {
        if (! is_array($panelMembers)) {
            return [];
        }

        $normalized = array_map(
            static fn (mixed $member): string => trim((string) $member),
            $panelMembers,
        );

        return array_values(array_unique(array_filter($normalized, static fn (string $member): bool => $member !== '')));
    }

    private function extractProponentNames(mixed $proponents): array
    {
        if (! is_array($proponents)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function (mixed $p): string {
                if (is_array($p) && isset($p['name'])) {
                    return trim((string) $p['name']);
                }

                return trim((string) $p);
            },
            $proponents,
        ), static fn (string $n): bool => $n !== ''));
    }
}
