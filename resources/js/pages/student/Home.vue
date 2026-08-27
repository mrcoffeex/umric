<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Bell,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    FileWarning,
    Megaphone,
    Pin,
    School,
    ScrollText,
    UserRoundPlus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import StudentResearchProgress from '@/components/student/StudentResearchProgress.vue';
import {
    workflowFocusStepKey,
    workflowProgressPercent,
} from '@/lib/research-workflow-ui';
import classesRoutes from '@/routes/classes';
import student from '@/routes/student';

interface Announcement {
    id: string;
    title: string;
    content: string;
    type: 'info' | 'success' | 'warning' | 'danger';
    is_pinned: boolean;
    published_at?: string | null;
}

interface ClassInfo {
    id: string;
    name: string;
    section?: string | null;
    class_code?: string | null;
    subjects?: Array<{ id: string; name: string; code?: string }>;
}

interface Paper {
    id: string;
    title: string;
    tracking_id: string;
    current_step: string;
    step_label?: string;
    step_ric_review?: string | null;
    step_outline_defense?: string | null;
    step_data_gathering?: string | null;
    step_rating?: string | null;
    step_final_manuscript?: string | null;
    step_final_defense?: string | null;
    step_hard_bound?: string | null;
    is_returned?: boolean;
    last_update?: {
        step: string;
        action: string;
        status: string;
        notes?: string | null;
        at?: string | null;
    } | null;
    upcoming_defense?: { type: string; at: string } | null;
}

interface AttentionNotification {
    id: string;
    category: string;
    title: string;
    body: string;
    url: string | null;
    created_at: string | null;
}

interface Props {
    announcements: Announcement[];
    classes: ClassInfo[];
    paper: Paper | null;
    stepLabels: Record<string, string>;
    steps: string[];
    hasClass: boolean;
    attention: {
        unread_notifications: AttentionNotification[];
        unread_count: number;
    };
}

const props = defineProps<Props>();
const page = usePage();
const joinCode = ref('');

const userName = computed(() => {
    return String(
        (page.props as { auth?: { user?: { name?: string } } }).auth?.user
            ?.name ?? 'Student',
    );
});

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const focusStepKey = computed(() =>
    props.paper ? workflowFocusStepKey(props.paper) : '',
);

const progressPercent = computed(() =>
    props.paper ? workflowProgressPercent(props.paper) : 0,
);

type NextAction = {
    tone: 'urgent' | 'action' | 'waiting' | 'done';
    title: string;
    description: string;
    ctaLabel?: string;
    ctaHref?: string;
    showJoin?: boolean;
};

const nextAction = computed<NextAction>(() => {
    if (!props.hasClass) {
        return {
            tone: 'urgent',
            title: 'Join a class to get started',
            description:
                'You need a class code before you can submit a title proposal.',
            showJoin: true,
        };
    }

    if (!props.paper) {
        return {
            tone: 'action',
            title: 'Submit your title proposal',
            description:
                'Start your research workflow by submitting a title for evaluation.',
            ctaLabel: 'Submit Title Proposal',
            ctaHref: student.research.create.url(),
        };
    }

    if (props.paper.is_returned || props.paper.current_step === 'title_proposal') {
        return {
            tone: 'urgent',
            title: props.paper.is_returned
                ? 'Your paper was returned for revision'
                : 'Continue your title proposal',
            description: props.paper.is_returned
                ? 'Review the feedback and resubmit when ready.'
                : 'Finish and submit your title so RIC review can begin.',
            ctaLabel: props.paper.is_returned ? 'Revise paper' : 'Continue editing',
            ctaHref: student.research.edit.url(props.paper.id),
        };
    }

    if (props.paper.current_step === 'completed') {
        return {
            tone: 'done',
            title: 'Research workflow complete',
            description: 'Your paper has finished the official pipeline.',
            ctaLabel: 'View paper',
            ctaHref: student.research.show.url(props.paper.id),
        };
    }

    const stage =
        props.stepLabels[focusStepKey.value] ?? focusStepKey.value;

    return {
        tone: 'waiting',
        title: `Waiting on ${stage}`,
        description: `You're ${progressPercent.value}% through the workflow. Open your paper for details and updates.`,
        ctaLabel: 'Open paper',
        ctaHref: student.research.show.url(props.paper.id),
    };
});

const attentionItems = computed(() => {
    const items: Array<{
        id: string;
        icon: 'warning' | 'bell' | 'calendar' | 'megaphone';
        title: string;
        body: string;
        href?: string;
    }> = [];

    if (props.paper?.is_returned) {
        items.push({
            id: 'returned',
            icon: 'warning',
            title: 'Paper returned',
            body: 'RIC asked for revisions before review can continue.',
            href: student.research.edit.url(props.paper.id),
        });
    }

    if (props.paper?.upcoming_defense) {
        const label =
            props.paper.upcoming_defense.type === 'final'
                ? 'Final defense'
                : 'Outline defense';
        items.push({
            id: 'defense',
            icon: 'calendar',
            title: `${label} upcoming`,
            body: formatDateTime(props.paper.upcoming_defense.at),
            href: student.research.show.url(props.paper.id),
        });
    }

    for (const note of props.attention.unread_notifications.slice(0, 3)) {
        items.push({
            id: note.id,
            icon: note.category === 'research' ? 'warning' : 'bell',
            title: note.title,
            body: note.body,
            href: note.url ?? '/notifications',
        });
    }

    for (const announcement of props.announcements.filter((a) => a.is_pinned).slice(0, 2)) {
        if (items.some((item) => item.title === announcement.title)) {
            continue;
        }

        items.push({
            id: `announcement-${announcement.id}`,
            icon: 'megaphone',
            title: announcement.title,
            body: announcement.content,
        });
    }

    return items.slice(0, 5);
});

const announcementTypeStyles: Record<Announcement['type'], string> = {
    info: 'border-l-blue-500',
    success: 'border-l-green-500',
    warning: 'border-l-amber-500',
    danger: 'border-l-red-500',
};

function formatDate(value?: string | null): string {
    if (!value) {
        return 'Recently posted';
    }

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function joinClassUrl(): string {
    if (!joinCode.value.trim()) {
        return '#';
    }

    return classesRoutes.join.show.url({ code: joinCode.value.trim() });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Student Home', href: student.home() }],
    },
});
</script>

<template>
    <Head title="Student Home" />

    <div class="flex h-full flex-1 flex-col gap-5 p-4 md:gap-6 md:p-6">
        <!-- Compact greeting -->
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold text-foreground">
                    {{ greeting }}, {{ userName }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Here’s what needs your attention today.
                </p>
            </div>
            <Link
                :href="student.research.index.url()"
                class="inline-flex min-h-9 items-center gap-1 text-xs font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400"
            >
                <ScrollText class="h-3.5 w-3.5" />
                My Research
            </Link>
        </div>

        <!-- Next action banner -->
        <section
            :class="[
                'overflow-hidden rounded-2xl border',
                nextAction.tone === 'urgent'
                    ? 'border-amber-300 bg-amber-50/80 dark:border-amber-800 dark:bg-amber-950/30'
                    : nextAction.tone === 'done'
                      ? 'border-teal-300 bg-teal-50/70 dark:border-teal-800 dark:bg-teal-950/30'
                      : nextAction.tone === 'waiting'
                        ? 'border-border bg-card'
                        : 'border-orange-300 bg-orange-50/70 dark:border-orange-800 dark:bg-orange-950/30',
            ]"
        >
            <div
                class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <span
                        :class="[
                            'mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl',
                            nextAction.tone === 'urgent'
                                ? 'bg-amber-500 text-white'
                                : nextAction.tone === 'done'
                                  ? 'bg-teal-500 text-white'
                                  : nextAction.tone === 'waiting'
                                    ? 'bg-muted text-muted-foreground'
                                    : 'bg-orange-500 text-white',
                        ]"
                    >
                        <AlertCircle
                            v-if="nextAction.tone === 'urgent'"
                            class="h-5 w-5"
                        />
                        <CheckCircle2
                            v-else-if="nextAction.tone === 'done'"
                            class="h-5 w-5"
                        />
                        <ClipboardList
                            v-else-if="nextAction.tone === 'waiting'"
                            class="h-5 w-5"
                        />
                        <ScrollText v-else class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-bold tracking-[0.12em] text-muted-foreground uppercase"
                        >
                            Next action
                        </p>
                        <h2 class="mt-0.5 text-lg font-bold text-foreground">
                            {{ nextAction.title }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ nextAction.description }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="nextAction.showJoin"
                    class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[18rem] sm:flex-row sm:items-center"
                >
                    <input
                        v-model="joinCode"
                        type="text"
                        placeholder="Enter class code"
                        class="h-11 w-full rounded-xl border border-input bg-background px-3 text-sm outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-400/30"
                    />
                    <Link
                        :href="joinClassUrl()"
                        class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-teal-500 px-4 text-sm font-semibold text-white transition hover:bg-teal-600 active:scale-[0.98]"
                    >
                        <UserRoundPlus class="h-4 w-4" />
                        Join class
                    </Link>
                </div>

                <Link
                    v-else-if="nextAction.ctaHref"
                    :href="nextAction.ctaHref"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white transition hover:bg-orange-600 active:scale-[0.98]"
                >
                    {{ nextAction.ctaLabel }}
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </section>

        <!-- Research + Needs attention -->
        <div class="grid gap-5 lg:grid-cols-5 lg:gap-6">
            <div class="lg:col-span-3">
                <StudentResearchProgress
                    v-if="paper"
                    :paper="paper"
                    :step-labels="stepLabels"
                    :steps="steps"
                />

                <section
                    v-else
                    class="flex h-full flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-card px-5 py-12 text-center"
                >
                    <div
                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <ScrollText class="h-6 w-6 text-orange-400" />
                    </div>
                    <p class="text-sm font-semibold text-foreground">
                        No research paper yet
                    </p>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                        Your progress tracker will appear here after you submit
                        a title proposal.
                    </p>
                </section>
            </div>

            <aside class="lg:col-span-2">
                <section
                    class="flex h-full flex-col overflow-hidden rounded-2xl border border-border bg-card"
                >
                    <div
                        class="flex items-center justify-between border-b border-border px-5 py-4"
                    >
                        <div class="flex items-center gap-2">
                            <Bell class="h-4 w-4 text-orange-500" />
                            <h2 class="text-base font-bold text-foreground">
                                Needs attention
                            </h2>
                        </div>
                        <Link
                            href="/notifications"
                            class="text-xs font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400"
                        >
                            {{
                                attention.unread_count > 0
                                    ? `${attention.unread_count} unread`
                                    : 'All clear'
                            }}
                        </Link>
                    </div>

                    <div v-if="attentionItems.length === 0" class="flex flex-1 flex-col items-center justify-center px-5 py-10 text-center">
                        <CheckCircle2 class="mb-2 h-8 w-8 text-teal-500" />
                        <p class="text-sm font-semibold text-foreground">
                            Nothing urgent
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            New announcements and paper updates will show up
                            here.
                        </p>
                    </div>

                    <ul v-else class="divide-y divide-border">
                        <li v-for="item in attentionItems" :key="item.id">
                            <component
                                :is="item.href ? Link : 'div'"
                                :href="item.href"
                                class="flex items-start gap-3 px-5 py-3.5 transition"
                                :class="
                                    item.href
                                        ? 'hover:bg-muted/50'
                                        : ''
                                "
                            >
                                <span
                                    :class="[
                                        'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg',
                                        item.icon === 'warning'
                                            ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400'
                                            : item.icon === 'calendar'
                                              ? 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400'
                                              : item.icon === 'megaphone'
                                                ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400'
                                                : 'bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400',
                                    ]"
                                >
                                    <FileWarning
                                        v-if="item.icon === 'warning'"
                                        class="h-3.5 w-3.5"
                                    />
                                    <CalendarDays
                                        v-else-if="item.icon === 'calendar'"
                                        class="h-3.5 w-3.5"
                                    />
                                    <Megaphone
                                        v-else-if="item.icon === 'megaphone'"
                                        class="h-3.5 w-3.5"
                                    />
                                    <Bell v-else class="h-3.5 w-3.5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block text-sm font-semibold text-foreground"
                                    >
                                        {{ item.title }}
                                    </span>
                                    <span
                                        class="mt-0.5 line-clamp-2 block text-xs text-muted-foreground"
                                    >
                                        {{ item.body }}
                                    </span>
                                </span>
                            </component>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>

        <!-- Classes + Announcements -->
        <div class="grid gap-5 lg:grid-cols-2 lg:gap-6">
            <section class="rounded-2xl border border-border bg-card">
                <div
                    class="flex items-center justify-between border-b border-border px-5 py-4"
                >
                    <div class="flex items-center gap-2">
                        <School class="h-4 w-4 text-teal-500" />
                        <h2 class="text-base font-bold text-foreground">
                            My class
                        </h2>
                    </div>
                    <Link
                        :href="student.classes.index.url()"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-teal-600 hover:text-teal-700"
                    >
                        View all
                        <ArrowRight class="h-3 w-3" />
                    </Link>
                </div>

                <div class="p-5">
                    <div
                        v-if="classes.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-6 text-center"
                    >
                        <p class="text-sm font-semibold text-foreground">
                            No class joined yet
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Use the join code in Next action above.
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="classItem in classes.slice(0, 2)"
                            :key="classItem.id"
                            class="rounded-xl border border-border px-4 py-3"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-bold text-foreground">
                                    {{ classItem.name }}
                                </h3>
                                <span
                                    v-if="classItem.section"
                                    class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                >
                                    {{ classItem.section }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    (classItem.subjects ?? []).length
                                        ? `${(classItem.subjects ?? []).length} subject${(classItem.subjects ?? []).length === 1 ? '' : 's'}`
                                        : 'No subjects listed'
                                }}
                                <span v-if="(classItem.subjects ?? []).length">
                                    ·
                                    {{
                                        (classItem.subjects ?? [])
                                            .map((s) => s.code ?? s.name)
                                            .slice(0, 3)
                                            .join(', ')
                                    }}
                                </span>
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-border bg-card">
                <div
                    class="flex items-center justify-between border-b border-border px-5 py-4"
                >
                    <div class="flex items-center gap-2">
                        <Megaphone class="h-4 w-4 text-blue-500" />
                        <h2 class="text-base font-bold text-foreground">
                            Announcements
                        </h2>
                    </div>
                    <span
                        v-if="announcements.length > 0"
                        class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:bg-blue-950/30 dark:text-blue-400"
                    >
                        {{ announcements.length }}
                    </span>
                </div>

                <div class="p-5">
                    <div
                        v-if="announcements.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-6 text-center"
                    >
                        <p class="text-sm font-semibold text-foreground">
                            No announcements
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            You're all caught up.
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="item in announcements"
                            :key="item.id"
                            :class="[
                                'rounded-xl border border-l-4 border-border p-3.5',
                                announcementTypeStyles[item.type],
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-bold text-foreground">
                                    {{ item.title }}
                                </h3>
                                <Pin
                                    v-if="item.is_pinned"
                                    class="h-3.5 w-3.5 shrink-0 text-orange-500"
                                />
                            </div>
                            <p
                                class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ item.content }}
                            </p>
                            <p class="mt-2 text-[11px] text-muted-foreground/70">
                                {{ formatDate(item.published_at) }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
