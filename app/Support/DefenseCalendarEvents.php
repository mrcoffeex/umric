<?php

namespace App\Support;

use App\Models\PanelDefense;
use App\Models\ResearchPaper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Calendar grid uses `panel_defenses.schedule` and/or legacy paper columns
 * so all scheduled defenses (including title) appear when in range.
 */
final class DefenseCalendarEvents
{
    /**
     * First/last instants of the given calendar month in the app timezone (stable for SQL + CarbonImmutable).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function monthStartEndInAppTimezone(int $year, int $month): array
    {
        $tz = config('app.timezone');
        $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    /**
     * @param  Builder<ResearchPaper>  $query
     */
    public static function addScheduleInMonthConstraint(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->where(function (Builder $q) use ($start, $end) {
            $q->whereBetween('outline_defense_schedule', [$start, $end])
                ->orWhereBetween('final_defense_schedule', [$start, $end])
                ->orWhereHas('panelDefenses', function (Builder $pd) use ($start, $end) {
                    $pd->whereNotNull('schedule')
                        ->whereBetween('schedule', [$start, $end]);
                });
        });
    }

    /**
     * @param  Collection<int, ResearchPaper>  $papers
     * @return list<array<string, mixed>>
     */
    public static function build(
        $papers,
        Carbon $start,
        Carbon $end,
        bool $includeStudent = true,
    ): array {
        $events = [];
        $coveredByPanel = [];

        foreach ($papers as $paper) {
            if (! $paper->relationLoaded('panelDefenses')) {
                $paper->load('panelDefenses');
            }

            foreach ($paper->panelDefenses as $pd) {
                if (! $pd->schedule || ! self::isWithinViewedMonth($pd->schedule, $start, $end)) {
                    continue;
                }
                $event = self::eventFromPanelDefense($paper, $pd, $includeStudent);
                if ($event === null) {
                    continue;
                }
                $key = $event['dedupe_key'];
                $coveredByPanel[$key] = true;
                unset($event['dedupe_key']);
                $events[] = $event;
            }

            if (
                $paper->outline_defense_schedule
                && self::isWithinViewedMonth($paper->outline_defense_schedule, $start, $end)
            ) {
                $key = self::dedupeKey($paper->id, 'outline_defense', $paper->outline_defense_schedule);
                if (! isset($coveredByPanel[$key])) {
                    $events[] = self::eventFromPaper($paper, 'outline_defense', $paper->outline_defense_schedule, $includeStudent);
                }
            }

            if (
                $paper->final_defense_schedule
                && self::isWithinViewedMonth($paper->final_defense_schedule, $start, $end)
            ) {
                $key = self::dedupeKey($paper->id, 'final_defense', $paper->final_defense_schedule);
                if (! isset($coveredByPanel[$key])) {
                    $events[] = self::eventFromPaper($paper, 'final_defense', $paper->final_defense_schedule, $includeStudent);
                }
            }
        }

        usort($events, fn (array $a, array $b) => strcmp($a['schedule'], $b['schedule']));

        return $events;
    }

    /**
     * Compare in app timezone so CarbonImmutable/UTC-stored values still match the viewed month.
     */
    private static function isWithinViewedMonth(
        CarbonInterface $schedule,
        Carbon $rangeStart,
        Carbon $rangeEnd,
    ): bool {
        $tz = config('app.timezone');
        $s = $schedule->copy()->timezone($tz);
        $a = $rangeStart->copy()->timezone($tz);
        $b = $rangeEnd->copy()->timezone($tz);

        return $s->between($a, $b, true);
    }

    private static function dedupeKey(string $paperId, string $type, CarbonInterface $schedule): string
    {
        $tz = config('app.timezone');

        return $paperId.'|'.$type.'|'.$schedule->copy()->timezone($tz)->format('Y-m-d H:i:s');
    }

    private static function eventFromPanelDefense(ResearchPaper $paper, PanelDefense $pd, bool $includeStudent): ?array
    {
        $schedule = $pd->schedule;
        if (! $schedule instanceof CarbonInterface) {
            return null;
        }

        $type = match ($pd->defense_type) {
            'outline' => 'outline_defense',
            'final' => 'final_defense',
            'title' => 'title_defense',
            default => null,
        };

        if ($type === null) {
            return null;
        }

        $step = match ($type) {
            'outline_defense' => $paper->step_outline_defense,
            'final_defense' => $paper->step_final_defense,
            default => null,
        };

        $event = [
            'id' => $paper->id.'-pd-'.$pd->id,
            'paper_id' => $paper->id,
            'tracking_id' => $paper->tracking_id,
            'title' => $paper->title,
            'type' => $type,
            'schedule' => $schedule->toIso8601String(),
            'step_status' => $step,
            'school_class' => $paper->schoolClass ? [
                'name' => $paper->schoolClass->name,
                'section' => $paper->schoolClass->section,
            ] : null,
            'panel_members' => is_array($pd->panel_members) ? array_values($pd->panel_members) : [],
            'proponents' => self::extractProponentNames($paper->proponents),
            'dedupe_key' => self::dedupeKey($paper->id, $type, $schedule),
        ];

        if ($includeStudent) {
            $event['student'] = $paper->user
                ? ['name' => $paper->user->name, 'email' => $paper->user->email]
                : null;
        }

        return $event;
    }

    private static function eventFromPaper(
        ResearchPaper $paper,
        string $type,
        CarbonInterface $schedule,
        bool $includeStudent,
    ): array {
        $suffix = $type === 'outline_defense' ? 'outline' : 'final';
        $defenseType = $type === 'outline_defense' ? 'outline' : 'final';
        $matched = $paper->panelDefenses->where('defense_type', $defenseType)->first();
        $panelMembers = is_array($matched?->panel_members)
            ? array_values($matched->panel_members)
            : [];

        $event = [
            'id' => $paper->id.'-'.$suffix,
            'paper_id' => $paper->id,
            'tracking_id' => $paper->tracking_id,
            'title' => $paper->title,
            'type' => $type,
            'schedule' => $schedule->toIso8601String(),
            'step_status' => $type === 'outline_defense' ? $paper->step_outline_defense : $paper->step_final_defense,
            'school_class' => $paper->schoolClass ? [
                'name' => $paper->schoolClass->name,
                'section' => $paper->schoolClass->section,
            ] : null,
            'panel_members' => $panelMembers,
            'proponents' => self::extractProponentNames($paper->proponents),
        ];

        if ($includeStudent) {
            $event['student'] = $paper->user
                ? ['name' => $paper->user->name, 'email' => $paper->user->email]
                : null;
        }

        return $event;
    }

    private static function extractProponentNames(mixed $proponents): array
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
