<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $event = trim((string) $request->query('event', ''));
        $causer = trim((string) $request->query('causer', ''));
        $risk = trim((string) $request->query('risk', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $highRiskEvents = ['blocked', 'rejected', 'deleted'];

        $baseQuery = Activity::query()
            ->with('causer', 'subject')
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($causer !== '', fn ($query) => $query->where('causer_id', (int) $causer))
            ->when($risk === 'high', fn ($query) => $query->whereIn('event', $highRiskEvents))
            ->when($risk === 'normal', fn ($query) => $query->whereNotIn('event', $highRiskEvents))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $likeValue = "%{$search}%";

                    $innerQuery
                        ->where('description', 'ILIKE', $likeValue)
                        ->orWhere('event', 'ILIKE', $likeValue)
                        ->orWhere('subject_type', 'ILIKE', $likeValue)
                        ->orWhereRaw('properties::text ILIKE ?', [$likeValue])
                        ->orWhereHas('causer', function ($causerQuery) use ($likeValue) {
                            $causerQuery->where('name', 'ILIKE', $likeValue)
                                ->orWhere('email', 'ILIKE', $likeValue);
                        });
                });
            });

        $logs = (clone $baseQuery)
            ->latest()
            ->paginate(50)
            ->withQueryString()
            ->through(function (Activity $activity) {
                $properties = $this->normalizeProperties($activity->properties);
                $requestMeta = is_array($properties['request'] ?? null) ? $properties['request'] : [];

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'causer' => $activity->causer?->email,
                    'causer_name' => $activity->causer?->name,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => $activity->subject_id,
                    'subject_name' => $this->getSubjectName($activity),
                    'properties' => $properties,
                    'ip_address' => (string) ($requestMeta['ip_address'] ?? 'Unknown'),
                    'browser' => (string) ($requestMeta['browser'] ?? 'Unknown'),
                    'device' => (string) ($requestMeta['device'] ?? 'Unknown'),
                    'location' => (string) ($requestMeta['location'] ?? 'Unknown'),
                    'action_details' => $this->buildActionDetails($properties, $requestMeta),
                    'created_at' => $activity->created_at,
                    'created_at_formatted' => $activity->created_at->format('M d, Y H:i:s'),
                ];
            });

        $statsQuery = clone $baseQuery;
        $totalLogs = (clone $statsQuery)->count();
        $todayLogs = (clone $statsQuery)->whereDate('created_at', now()->toDateString())->count();
        $highRiskLogs = (clone $statsQuery)->whereIn('event', $highRiskEvents)->count();
        $activeAdmins = (clone $statsQuery)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id');

        $events = Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->filter()
            ->values();

        $causers = Activity::query()
            ->with('causer:id,name,email')
            ->whereNotNull('causer_id')
            ->distinct('causer_id')
            ->get()
            ->map(fn (Activity $activity) => $activity->causer)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('admin/ActivityLog/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'event' => $event,
                'causer' => $causer,
                'risk' => $risk,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'stats' => [
                'total_logs' => $totalLogs,
                'today_logs' => $todayLogs,
                'high_risk_logs' => $highRiskLogs,
                'active_admins' => $activeAdmins,
            ],
            'options' => [
                'events' => $events,
                'causers' => $causers,
            ],
        ]);
    }

    private function normalizeProperties(mixed $properties): array
    {
        if (is_array($properties)) {
            return $properties;
        }

        if ($properties instanceof Collection) {
            return $properties->toArray();
        }

        return [];
    }

    private function buildActionDetails(array $properties, array $requestMeta): string
    {
        $details = [];

        if (isset($requestMeta['route_name'])) {
            $details[] = 'Route: '.$requestMeta['route_name'];
        }

        if (isset($requestMeta['method'])) {
            $details[] = 'Method: '.$requestMeta['method'];
        }

        if (isset($requestMeta['url'])) {
            $details[] = 'URL: '.$requestMeta['url'];
        }

        $customProperties = $properties;
        unset($customProperties['request']);

        if ($customProperties !== []) {
            $details[] = 'Payload: '.json_encode($customProperties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return implode(' | ', $details);
    }

    private function getSubjectName(Activity $activity): ?string
    {
        if (! $activity->subject) {
            return null;
        }

        return match ($activity->subject_type) {
            'App\Models\User' => $activity->subject->email ?? 'User #'.$activity->subject_id,
            'App\Models\Department' => $activity->subject->code ?? $activity->subject->name,
            'App\Models\Subject' => $activity->subject->code ?? $activity->subject->name,
            'App\Models\ResearchPaper' => $activity->subject->tracking_id ?? 'Paper #'.$activity->subject_id,
            default => $activity->subject->name ?? $activity->subject->id,
        };
    }
}
