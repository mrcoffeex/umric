<?php

namespace App\Services;

use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardInsightsBuilder
{
    public function __construct(
        private WorkflowCatalog $workflows,
    ) {}

    private const STUCK_DAYS = 14;

    private const MAX_ACTIONS = 5;

    /**
     * @return array{
     *     actions: list<array{
     *         id: string,
     *         tone: string,
     *         title: string,
     *         description: string,
     *         cta_label: ?string,
     *         cta_href: ?string,
     *         count: ?int,
     *         show_join: bool
     *     }>,
     *     health: list<array{id: string, label: string, value: string|int, hint: ?string}>
     * }
     */
    public function forUser(User $user): array
    {
        $role = $user->profile?->role ?? 'student';

        return match ($role) {
            'admin', 'staff' => $this->forAdminOffice($user, $role),
            'faculty' => $this->forFaculty($user),
            default => $this->forStudent($user),
        };
    }

    /**
     * @return array{
     *     actions: list<array{
     *         id: string,
     *         tone: string,
     *         title: string,
     *         description: string,
     *         cta_label: ?string,
     *         cta_href: ?string,
     *         count: ?int,
     *         show_join: bool
     *     }>,
     *     health: list<array{id: string, label: string, value: string|int, hint: ?string}>
     * }
     */
    public function forStudent(
        User $user,
        bool $hasClass = false,
        ?ResearchPaper $paper = null,
    ): array {
        $actions = [];

        if (! $hasClass) {
            $actions[] = $this->action(
                id: 'join-class',
                tone: 'urgent',
                title: 'Join a class to get started',
                description: 'You need a class code before you can submit a title proposal.',
                showJoin: true,
            );
        } elseif ($paper === null) {
            $actions[] = $this->action(
                id: 'submit-title',
                tone: 'urgent',
                title: 'Submit your title proposal',
                description: 'Start your research workflow by submitting a title for evaluation.',
                ctaLabel: 'Submit Title Proposal',
                ctaHref: route('student.research.create'),
            );
        } elseif ($paper->isRicReviewReturned() || $paper->current_step === 'title_proposal') {
            $returned = $paper->isRicReviewReturned();
            $actions[] = $this->action(
                id: 'revise-or-continue',
                tone: 'urgent',
                title: $returned ? 'Your paper was returned for revision' : 'Continue your title proposal',
                description: $returned
                    ? 'Review the feedback and resubmit when ready.'
                    : 'Finish and submit your title so RIC review can begin.',
                ctaLabel: $returned ? 'Revise paper' : 'Continue editing',
                ctaHref: route('student.research.edit', $paper),
            );
        }

        if ($paper?->isRicReviewReturned() && ! collect($actions)->contains(fn (array $a) => $a['id'] === 'revise-or-continue')) {
            $actions[] = $this->action(
                id: 'returned',
                tone: 'urgent',
                title: 'Paper returned',
                description: 'RIC asked for revisions before review can continue.',
                ctaLabel: 'Revise paper',
                ctaHref: route('student.research.edit', $paper),
            );
        }

        $upcoming = $paper ? $this->upcomingDefense($paper) : null;
        if ($upcoming !== null) {
            $label = $upcoming['type'] === 'final' ? 'Final defense' : 'Outline defense';
            $actions[] = $this->action(
                id: 'upcoming-defense',
                tone: 'info',
                title: "{$label} upcoming",
                description: $upcoming['at']->timezone((string) config('app.timezone'))->format('M j, Y g:i A'),
                ctaLabel: 'View paper',
                ctaHref: route('student.research.show', $paper),
            );
        }

        $this->pushNewAnnouncementInsight($actions, $user);

        return [
            'actions' => array_slice($actions, 0, self::MAX_ACTIONS),
            // Progress / last update live in StudentResearchProgress — avoid duplicating them here.
            'health' => [],
        ];
    }

    /**
     * @return array{
     *     actions: list<array{
     *         id: string,
     *         tone: string,
     *         title: string,
     *         description: string,
     *         cta_label: ?string,
     *         cta_href: ?string,
     *         count: ?int,
     *         show_join: bool
     *     }>,
     *     health: list<array{id: string, label: string, value: string|int, hint: ?string}>
     * }
     */
    private function forAdminOffice(User $user, string $role): array
    {
        $pendingReview = ResearchPaper::pendingReview()->count();
        $pendingApproval = User::query()
            ->whereHas('profile', fn ($q) => $q->where('role', 'faculty')->whereNull('approved_at'))
            ->count();
        $returned = ResearchPaper::query()
            ->whereRaw('LOWER(COALESCE(step_ric_review, "")) = ?', ['returned'])
            ->count();
        $upcomingDefenses = $this->countUpcomingDefenses(ResearchPaper::query());
        $stuck = $this->stuckPapersQuery(ResearchPaper::query())->count();
        $total = ResearchPaper::query()->count();
        $completed = ResearchPaper::query()->where('current_step', 'completed')->count();
        $completionRate = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        $bottleneck = $this->bottleneckStep(ResearchPaper::query());

        $actions = [];

        if ($pendingReview > 0) {
            $actions[] = $this->action(
                id: 'ric-pending',
                tone: 'urgent',
                title: 'Papers awaiting RIC review',
                description: $pendingReview === 1
                    ? '1 paper is waiting for office review.'
                    : "{$pendingReview} papers are waiting for office review.",
                ctaLabel: 'Review papers',
                ctaHref: route('admin.research.index', ['step' => 'ric_review']),
                count: $pendingReview,
            );
        }

        if ($pendingApproval > 0) {
            $actions[] = $this->action(
                id: 'faculty-approval',
                tone: 'urgent',
                title: 'Faculty awaiting approval',
                description: $pendingApproval === 1
                    ? '1 faculty registration still needs approval.'
                    : "{$pendingApproval} faculty registrations still need approval.",
                ctaLabel: 'Review users',
                ctaHref: $role === 'admin'
                    ? route('admin.users.index', ['role' => 'faculty'])
                    : route('admin.research.index'),
                count: $pendingApproval,
            );
        }

        if ($returned > 0) {
            $actions[] = $this->action(
                id: 'returned-papers',
                tone: 'info',
                title: 'Papers returned for revision',
                description: $returned === 1
                    ? '1 paper was sent back to the student.'
                    : "{$returned} papers were sent back to students.",
                ctaLabel: 'Open research',
                ctaHref: route('admin.research.index'),
                count: $returned,
            );
        }

        if ($upcomingDefenses > 0) {
            $actions[] = $this->action(
                id: 'upcoming-defenses',
                tone: 'info',
                title: 'Defenses in the next 7 days',
                description: $upcomingDefenses === 1
                    ? '1 defense is scheduled this week.'
                    : "{$upcomingDefenses} defenses are scheduled this week.",
                ctaLabel: 'Open calendar',
                ctaHref: route('admin.defense-calendar.index'),
                count: $upcomingDefenses,
            );
        }

        if ($stuck > 0) {
            $actions[] = $this->action(
                id: 'stuck-papers',
                tone: 'waiting',
                title: 'Papers with no recent activity',
                description: $stuck === 1
                    ? '1 paper has had no update for '.self::STUCK_DAYS.'+ days.'
                    : "{$stuck} papers have had no update for ".self::STUCK_DAYS.'+ days.',
                ctaLabel: 'Review research',
                ctaHref: route('admin.research.index'),
                count: $stuck,
            );
        }

        $this->pushNewAnnouncementInsight($actions, $user);

        if ($actions === []) {
            $actions[] = $this->action(
                id: 'all-clear',
                tone: 'done',
                title: 'No urgent office actions',
                description: 'The research pipeline looks clear. Check charts below for trends.',
                ctaLabel: 'Browse research',
                ctaHref: route('admin.research.index'),
            );
        }

        $health = [
            $this->health('completion', 'Completion', $completionRate.'%', 'Finished pipeline'),
            $this->health('pending-ric', 'Pending RIC', $pendingReview, 'Awaiting office review'),
            $this->health('stuck', 'Stalled', $stuck, self::STUCK_DAYS.'+ days quiet'),
        ];

        if ($bottleneck !== null) {
            $health[] = $this->health(
                'bottleneck',
                'Bottleneck',
                $this->workflows->allKnownLabels()[$bottleneck['step']] ?? $bottleneck['step'],
                $bottleneck['count'].' papers',
            );
        }

        return [
            'actions' => array_slice($actions, 0, self::MAX_ACTIONS),
            'health' => array_slice($health, 0, 4),
        ];
    }

    /**
     * @return array{
     *     actions: list<array{
     *         id: string,
     *         tone: string,
     *         title: string,
     *         description: string,
     *         cta_label: ?string,
     *         cta_href: ?string,
     *         count: ?int,
     *         show_join: bool
     *     }>,
     *     health: list<array{id: string, label: string, value: string|int, hint: ?string}>
     * }
     */
    private function forFaculty(User $user): array
    {
        $classIds = SchoolClass::query()->where('faculty_id', $user->id)->pluck('id');
        $studentIds = DB::table('school_class_members')
            ->whereIn('school_class_id', $classIds)
            ->pluck('student_id')
            ->unique();

        $papers = ResearchPaper::query()->where(function (Builder $q) use ($studentIds, $user) {
            $q->whereIn('user_id', $studentIds)
                ->orWhere('adviser_id', $user->id);
        });

        $pendingActions = (clone $papers)->whereIn('current_step', ['outline_defense', 'rating', 'final_defense'])->count();
        $adviseeActive = (clone $papers)->where('adviser_id', $user->id)->where('current_step', '!=', 'completed')->count();
        $upcomingDefenses = $this->countUpcomingDefenses(clone $papers);
        $stuck = $this->stuckPapersQuery(clone $papers)->count();
        $total = (clone $papers)->count();
        $completed = (clone $papers)->where('current_step', 'completed')->count();
        $completionRate = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        $classCount = $classIds->count();
        $studentsWithPapers = (clone $papers)->select('user_id')->distinct()->count('user_id');

        $actions = [];

        if ($pendingActions > 0) {
            $actions[] = $this->action(
                id: 'faculty-pending',
                tone: 'urgent',
                title: 'Papers needing your attention',
                description: $pendingActions === 1
                    ? '1 advisee/class paper is at a defense or rating stage.'
                    : "{$pendingActions} advisee/class papers are at defense or rating stages.",
                ctaLabel: 'Open research',
                ctaHref: route('faculty.research.index'),
                count: $pendingActions,
            );
        }

        if ($adviseeActive > 0) {
            $actions[] = $this->action(
                id: 'advisees-active',
                tone: 'info',
                title: 'Active advisee papers',
                description: $adviseeActive === 1
                    ? 'You are advising 1 paper still in the pipeline.'
                    : "You are advising {$adviseeActive} papers still in the pipeline.",
                ctaLabel: 'View advisees',
                ctaHref: route('faculty.research.index'),
                count: $adviseeActive,
            );
        }

        if ($upcomingDefenses > 0) {
            $actions[] = $this->action(
                id: 'faculty-defenses',
                tone: 'info',
                title: 'Upcoming defenses (7 days)',
                description: $upcomingDefenses === 1
                    ? '1 defense is scheduled for your papers this week.'
                    : "{$upcomingDefenses} defenses are scheduled for your papers this week.",
                ctaLabel: 'Open calendar',
                ctaHref: route('faculty.defense-calendar.index'),
                count: $upcomingDefenses,
            );
        }

        $unreadCount = $user->unreadNotifications()
            ->where('type', '!=', 'announcement')
            ->count();
        if ($unreadCount > 0) {
            $actions[] = $this->action(
                id: 'faculty-unread',
                tone: 'info',
                title: 'Unread notifications',
                description: $unreadCount === 1
                    ? 'You have 1 unread notification.'
                    : "You have {$unreadCount} unread notifications.",
                ctaLabel: 'View inbox',
                ctaHref: route('notifications.index'),
                count: $unreadCount,
            );
        }

        if ($stuck > 0) {
            $actions[] = $this->action(
                id: 'faculty-stuck',
                tone: 'waiting',
                title: 'Quiet papers',
                description: $stuck === 1
                    ? '1 paper has had no update for '.self::STUCK_DAYS.'+ days.'
                    : "{$stuck} papers have had no update for ".self::STUCK_DAYS.'+ days.',
                ctaLabel: 'Follow up',
                ctaHref: route('faculty.research.index'),
                count: $stuck,
            );
        }

        $this->pushNewAnnouncementInsight($actions, $user);

        if ($actions === []) {
            $actions[] = $this->action(
                id: 'faculty-clear',
                tone: 'done',
                title: 'Nothing urgent right now',
                description: 'Your advisee and class pipelines look steady.',
                ctaLabel: 'Browse research',
                ctaHref: route('faculty.research.index'),
            );
        }

        return [
            'actions' => array_slice($actions, 0, self::MAX_ACTIONS),
            'health' => [
                $this->health('completion', 'Completion', $completionRate.'%', 'Advisee / class papers'),
                $this->health('stuck', 'Stalled', $stuck, self::STUCK_DAYS.'+ days quiet'),
                $this->health('classes', 'Classes', $classCount, 'Active sections'),
                $this->health('with-papers', 'With papers', $studentsWithPapers, 'Students in pipeline'),
            ],
        ];
    }

    private function stuckPapersQuery(Builder $query): Builder
    {
        return $query
            ->where('current_step', '!=', 'completed')
            ->where('updated_at', '<', now()->subDays(self::STUCK_DAYS));
    }

    private function countUpcomingDefenses(Builder $query): int
    {
        $from = now();
        $to = now()->addDays(7);

        return (clone $query)
            ->where(function (Builder $q) use ($from, $to) {
                $q->whereBetween('outline_defense_schedule', [$from, $to])
                    ->orWhereBetween('final_defense_schedule', [$from, $to]);
            })
            ->count();
    }

    /**
     * @return array{step: string, count: int}|null
     */
    private function bottleneckStep(Builder $query): ?array
    {
        $counts = [];
        foreach ($this->workflows->allKnownStepKeys() as $step) {
            if ($step === 'completed') {
                continue;
            }
            $counts[$step] = (clone $query)->where('current_step', $step)->count();
        }

        arsort($counts);
        $step = array_key_first($counts);
        if ($step === null || ($counts[$step] ?? 0) < 1) {
            return null;
        }

        return ['step' => $step, 'count' => $counts[$step]];
    }

    /**
     * @return array{type: string, at: CarbonInterface}|null
     */
    private function upcomingDefense(ResearchPaper $paper): ?array
    {
        $candidates = collect([
            ['type' => 'outline', 'at' => $paper->outline_defense_schedule],
            ['type' => 'final', 'at' => $paper->final_defense_schedule],
        ])
            ->filter(fn (array $item) => $item['at'] instanceof CarbonInterface && $item['at']->isFuture())
            ->sortBy('at')
            ->first();

        return $candidates ?: null;
    }

    /**
     * @param  list<array{
     *     id: string,
     *     tone: string,
     *     title: string,
     *     description: string,
     *     cta_label: ?string,
     *     cta_href: ?string,
     *     count: ?int,
     *     show_join: bool
     * }>  $actions
     */
    private function pushNewAnnouncementInsight(array &$actions, User $user): void
    {
        $unread = $user->unreadNotifications()
            ->where('type', 'announcement')
            ->latest()
            ->get();

        if ($unread->isEmpty()) {
            return;
        }

        $latest = $unread->first();
        $count = $unread->count();
        $title = (string) ($latest->data['title'] ?? 'New announcement');

        $actions[] = $this->action(
            id: 'new-announcement',
            tone: 'info',
            title: $count === 1 ? 'New announcement' : "{$count} new announcements",
            description: $count === 1
                ? $title
                : "Latest: {$title}",
            ctaLabel: 'Read',
            ctaHref: route('announcements.index'),
            count: $count,
        );
    }

    private function focusStepKey(ResearchPaper $paper): string
    {
        foreach ($this->workflows->allKnownStepKeys() as $step) {
            if (! $this->isStepSatisfied($paper, $step)) {
                return $step;
            }
        }

        return 'completed';
    }

    private function progressPercent(ResearchPaper $paper): int
    {
        $steps = $this->workflows->allKnownStepKeys();
        $n = count($steps);
        if ($n <= 1) {
            return 0;
        }

        if ($this->isStepSatisfied($paper, 'completed')) {
            return 100;
        }

        $index = 0;
        foreach ($steps as $i => $step) {
            if (! $this->isStepSatisfied($paper, $step)) {
                $index = $i;
                break;
            }
            $index = $i;
        }

        return (int) round(($index / ($n - 1)) * 100);
    }

    private function isStepSatisfied(ResearchPaper $paper, string $step): bool
    {
        return match ($step) {
            'title_proposal' => $paper->current_step !== 'title_proposal',
            'ric_review' => $paper->step_ric_review === 'approved',
            'outline_defense' => $paper->step_outline_defense === 'passed',
            'data_gathering' => $paper->step_data_gathering === 'completed',
            'rating' => $paper->step_rating === 'rated',
            'final_manuscript' => $paper->step_final_manuscript === 'submitted',
            'final_defense' => $paper->step_final_defense === 'passed',
            'hard_bound' => $paper->step_hard_bound === 'submitted',
            'completed' => $paper->current_step === 'completed',
            default => false,
        };
    }

    /**
     * @return array{
     *     id: string,
     *     tone: string,
     *     title: string,
     *     description: string,
     *     cta_label: ?string,
     *     cta_href: ?string,
     *     count: ?int,
     *     show_join: bool
     * }
     */
    private function action(
        string $id,
        string $tone,
        string $title,
        string $description,
        ?string $ctaLabel = null,
        ?string $ctaHref = null,
        ?int $count = null,
        bool $showJoin = false,
    ): array {
        return [
            'id' => $id,
            'tone' => $tone,
            'title' => $title,
            'description' => $description,
            'cta_label' => $ctaLabel,
            'cta_href' => $ctaHref,
            'count' => $count,
            'show_join' => $showJoin,
        ];
    }

    /**
     * @return array{id: string, label: string, value: string|int, hint: ?string}
     */
    private function health(string $id, string $label, string|int $value, ?string $hint = null): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'value' => $value,
            'hint' => $hint,
        ];
    }
}
