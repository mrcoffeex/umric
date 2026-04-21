<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Bell,
    BookOpen,
    GraduationCap,
    Pin,
    School,
    ScrollText,
    UserRoundPlus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { getStepStatusClass } from '@/lib/step-colors';
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
}

interface Props {
    announcements: Announcement[];
    classes: ClassInfo[];
    paper: Paper | null;
    stepLabels: Record<string, string>;
    steps: string[];
    hasClass: boolean;
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

const announcementTypeStyles: Record<Announcement['type'], string> = {
    info: 'border-l-blue-500',
    success: 'border-l-green-500',
    warning: 'border-l-amber-500',
    danger: 'border-l-red-500',
};

const announcementDotStyles: Record<Announcement['type'], string> = {
    info: 'bg-blue-500',
    success: 'bg-green-500',
    warning: 'bg-amber-500',
    danger: 'bg-red-500',
};

const currentStepIndex = computed(() => {
    if (!props.paper) {
        return -1;
    }

    return props.steps.indexOf(props.paper.current_step);
});

const progressPercent = computed(() => {
    if (currentStepIndex.value < 0 || props.steps.length <= 1) {
        return 0;
    }

    return Math.round(
        (currentStepIndex.value / (props.steps.length - 1)) * 100,
    );
});

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

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

function isCompletedStep(index: number): boolean {
    return currentStepIndex.value > index;
}

function isCurrentStep(index: number): boolean {
    return currentStepIndex.value === index;
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Hero Welcome -->
        <section
            class="overflow-hidden rounded-2xl border border-border bg-card"
        >
            <div
                class="h-1 bg-gradient-to-r from-orange-500 via-amber-500 to-teal-500"
            />
            <div
                class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        {{ greeting }}, {{ userName }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Here's an overview of your academic progress.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="student.research.index.url()"
                        class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600"
                    >
                        <ScrollText class="h-4 w-4" />
                        My Research
                    </Link>
                    <Link
                        :href="student.classes.index.url()"
                        class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                    >
                        <GraduationCap class="h-4 w-4" />
                        My Classes
                    </Link>
                </div>
            </div>
        </section>

        <!-- Quick Stats -->
        <div class="grid gap-3 sm:grid-cols-3">
            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950/30"
                >
                    <ScrollText class="h-5 w-5 text-orange-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Research
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ paper ? `${progressPercent}%` : 'None' }}
                    </p>
                </div>
            </div>
            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-950/30"
                >
                    <School class="h-5 w-5 text-teal-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Classes
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ classes.length }}
                    </p>
                </div>
            </div>
            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950/30"
                >
                    <Bell class="h-5 w-5 text-blue-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Announcements
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ announcements.length }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Research Progress -->
        <section class="rounded-2xl border border-border bg-card">
            <div
                class="flex items-center justify-between border-b border-border p-5"
            >
                <div class="flex items-center gap-2">
                    <ScrollText class="h-4 w-4 text-orange-500" />
                    <h2 class="text-base font-bold text-foreground">
                        Research Progress
                    </h2>
                </div>
                <Link
                    :href="student.research.index.url()"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-orange-500 hover:text-orange-600"
                >
                    View all
                    <ArrowRight class="h-3 w-3" />
                </Link>
            </div>

            <div class="p-5">
                <div
                    v-if="!paper"
                    class="flex flex-col items-center rounded-xl border border-dashed border-border py-8"
                >
                    <div
                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <BookOpen class="h-6 w-6 text-orange-400" />
                    </div>
                    <p class="text-sm font-semibold text-foreground">
                        No research paper yet
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Submit your title proposal to start.
                    </p>

                    <!-- No class: show disabled button with instruction -->
                    <div
                        v-if="!hasClass"
                        class="mt-4 flex flex-col items-center gap-2"
                    >
                        <div
                            class="flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:bg-amber-950/30 dark:text-amber-400"
                        >
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            Join a class first to submit a title proposal.
                        </div>
                        <button
                            disabled
                            class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg bg-orange-200 px-4 py-2 text-sm font-semibold text-white dark:bg-orange-900/40"
                        >
                            Submit Title Proposal
                        </button>
                    </div>

                    <Link
                        v-else
                        :href="student.research.create.url()"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600"
                    >
                        Submit Title Proposal
                    </Link>
                </div>

                <div v-else>
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="student.research.show.url(paper.id)"
                                class="text-sm font-bold text-foreground hover:text-orange-600 dark:hover:text-orange-400"
                            >
                                {{ paper.title }}
                            </Link>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                <code
                                    class="rounded bg-orange-50 px-1.5 py-0.5 font-mono text-[11px] text-orange-600 dark:bg-orange-950/40 dark:text-orange-400"
                                    >{{ paper.tracking_id }}</code
                                >
                            </p>
                        </div>
                        <span
                            class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-950/40 dark:text-orange-300"
                        >
                            {{
                                paper.step_label ??
                                stepLabel(paper.current_step)
                            }}
                        </span>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-3 flex items-center gap-3">
                        <div
                            class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-orange-500 to-teal-500 transition-all"
                                :style="{ width: progressPercent + '%' }"
                            />
                        </div>
                        <span class="text-xs font-bold text-muted-foreground"
                            >{{ progressPercent }}%</span
                        >
                    </div>

                    <!-- Step grid -->
                    <div
                        class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8"
                    >
                        <div
                            v-for="(step, idx) in steps"
                            :key="step"
                            :aria-current="
                                isCurrentStep(idx) ? 'step' : undefined
                            "
                            :data-completed="isCompletedStep(idx) || undefined"
                            :class="[
                                'rounded-xl border px-3 py-2 text-center text-sm font-semibold',
                                getStepStatusClass(
                                    step,
                                    paper.current_step,
                                    steps,
                                ),
                            ]"
                        >
                            {{ stepLabel(step) }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Two-column layout for Classes and Announcements -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Classes -->
            <section
                :class="[
                    'rounded-2xl border bg-card',
                    !hasClass
                        ? 'border-teal-400 ring-2 ring-teal-400/40 dark:border-teal-500 dark:ring-teal-500/30'
                        : 'border-border',
                ]"
            >
                <!-- Attention banner when no class -->
                <div
                    v-if="!hasClass"
                    class="flex items-center gap-2 rounded-t-2xl bg-teal-500 px-5 py-2.5 text-sm font-semibold text-white"
                >
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    Join a class to unlock title proposal submission.
                </div>
                <div
                    class="flex items-center justify-between border-b border-border p-5"
                >
                    <div class="flex items-center gap-2">
                        <School class="h-4 w-4 text-teal-500" />
                        <h2 class="text-base font-bold text-foreground">
                            Classes
                        </h2>
                    </div>
                    <Link
                        :href="student.classes.index.url()"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-teal-500 hover:text-teal-600"
                    >
                        View all
                        <ArrowRight class="h-3 w-3" />
                    </Link>
                </div>

                <div class="p-5">
                    <div
                        v-if="classes.length === 0"
                        class="flex flex-col items-center rounded-xl border border-dashed border-border py-6"
                    >
                        <div
                            class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 dark:bg-teal-950/30"
                        >
                            <GraduationCap class="h-6 w-6 text-teal-400" />
                        </div>
                        <p class="text-sm font-semibold text-foreground">
                            No classes yet
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Enter a class code to join.
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <input
                                v-model="joinCode"
                                type="text"
                                placeholder="Enter class code"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30 sm:max-w-[180px]"
                            />
                            <Link
                                :href="joinClassUrl()"
                                class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600"
                            >
                                <UserRoundPlus class="h-3.5 w-3.5" />
                                Join
                            </Link>
                        </div>
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="classItem in classes"
                            :key="classItem.id"
                            class="rounded-xl border border-border p-4 transition hover:border-teal-300 dark:hover:border-teal-800"
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
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="subject in classItem.subjects ?? []"
                                    :key="subject.id"
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        subject.code
                                            ? 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                            : 'bg-muted text-muted-foreground',
                                    ]"
                                >
                                    {{ subject.code ?? subject.name }}
                                </span>
                                <span
                                    v-if="!(classItem.subjects ?? []).length"
                                    class="text-xs text-muted-foreground"
                                    >No subjects listed</span
                                >
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Announcements -->
            <section class="rounded-2xl border border-border bg-card">
                <div
                    class="flex items-center justify-between border-b border-border p-5"
                >
                    <div class="flex items-center gap-2">
                        <Bell class="h-4 w-4 text-blue-500" />
                        <h2 class="text-base font-bold text-foreground">
                            Announcements
                        </h2>
                    </div>
                    <span
                        v-if="announcements.length > 0"
                        class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:bg-blue-950/30 dark:text-blue-400"
                    >
                        {{ announcements.length }} new
                    </span>
                </div>

                <div class="p-5">
                    <div
                        v-if="announcements.length === 0"
                        class="flex flex-col items-center rounded-xl border border-dashed border-border py-6"
                    >
                        <div
                            class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-950/30"
                        >
                            <Bell class="h-6 w-6 text-blue-400" />
                        </div>
                        <p class="text-sm font-semibold text-foreground">
                            No announcements
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            You're all caught up!
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="item in announcements"
                            :key="item.id"
                            :class="[
                                'rounded-xl border border-l-4 border-border p-4 transition',
                                announcementTypeStyles[item.type],
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'mt-1 h-2 w-2 shrink-0 rounded-full',
                                            announcementDotStyles[item.type],
                                        ]"
                                    />
                                    <h3
                                        class="text-sm font-bold text-foreground"
                                    >
                                        {{ item.title }}
                                    </h3>
                                </div>
                                <Pin
                                    v-if="item.is_pinned"
                                    class="h-3.5 w-3.5 shrink-0 text-orange-500"
                                />
                            </div>
                            <p
                                class="mt-1.5 line-clamp-2 pl-4 text-sm text-muted-foreground"
                            >
                                {{ item.content }}
                            </p>
                            <p
                                class="mt-2 pl-4 text-[11px] text-muted-foreground/70"
                            >
                                {{ formatDate(item.published_at) }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
