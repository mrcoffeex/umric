<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    BookOpen,
    BookOpenCheck,
    CheckCircle2,
    ClipboardList,
    GraduationCap,
    Megaphone,
    School,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { getStepBadgeClass, getStepBarClass } from '@/lib/step-colors';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { index as facultyClassesIndex } from '@/routes/faculty/classes';
import {
    create as papersCreate,
    index as papersIndex,
    show as papersShow,
} from '@/routes/papers';

interface Announcement {
    id: number;
    title: string;
    content: string;
    type: 'info' | 'success' | 'warning' | 'danger';
    is_pinned: boolean;
    published_at: string | null;
}

interface DashboardPaper {
    id: number;
    title: string;
    tracking_id: string;
    current_step?: string | null;
    step_label?: string | null;
    student_name?: string;
    class_name?: string | null;
    created_at: string;
}

interface ClassSummary {
    id: number;
    name: string;
    members_count?: number;
    research_papers_count?: number;
    section?: string;
    subjects?: Array<{ code: string; name: string }>;
}

interface StudentPaper {
    id: number;
    title: string;
    tracking_id: string;
    current_step: string;
    step_label: string;
    created_at: string;
}

interface Props {
    role: 'admin' | 'staff' | 'faculty' | 'student';
    announcements: Announcement[];
    stepLabels: Record<string, string>;
    stats: Record<string, number | string | boolean | null>;
    stepCounts?: Record<string, number>;
    recentPapers?: DashboardPaper[];
    classes?: ClassSummary[];
    paper?: StudentPaper | null;
}

const props = defineProps<Props>();

const announcementColors: Record<Announcement['type'], string> = {
    info: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-300',
    success:
        'border-green-200 bg-green-50 text-green-700 dark:border-green-900/40 dark:bg-green-950/20 dark:text-green-300',
    warning:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300',
    danger: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300',
};

const orderedStepEntries = computed(() => Object.entries(props.stepLabels ?? {}));
const visibleRecentPapers = computed(() => props.recentPapers ?? []);
const visibleClasses = computed(() => props.classes ?? []);

const maxStepCount = computed(() => {
    const counts = Object.values(props.stepCounts ?? {});

    return counts.length > 0 ? Math.max(1, ...counts) : 1;
});

const adminStaffStatCards = computed(() => [
    {
        label: 'Total Papers',
        value: Number(props.stats.totalPapers ?? 0),
        border: 'border-orange-300',
        icon: BookOpen,
        iconClass: 'text-orange-500',
    },
    {
        label: 'Pending Review',
        value: Number(props.stats.pendingReview ?? 0),
        border: 'border-teal-300',
        icon: ClipboardList,
        iconClass: 'text-teal-500',
    },
    {
        label: 'Completed',
        value: Number(props.stats.completed ?? 0),
        border: 'border-green-300',
        icon: CheckCircle2,
        iconClass: 'text-green-500',
    },
    {
        label: 'Total Users',
        value: Number(props.stats.totalUsers ?? 0),
        border: 'border-indigo-300',
        icon: Users,
        iconClass: 'text-indigo-500',
    },
    {
        label: 'Pending Approval',
        value: Number(props.stats.pendingApproval ?? 0),
        border: 'border-amber-300',
        icon: AlertCircle,
        iconClass: 'text-amber-500',
    },
]);

const facultyStatCards = computed(() => [
    {
        label: 'Classes',
        value: Number(props.stats.totalClasses ?? 0),
        border: 'border-orange-300',
        icon: School,
        iconClass: 'text-orange-500',
    },
    {
        label: 'Students',
        value: Number(props.stats.totalStudents ?? 0),
        border: 'border-teal-300',
        icon: GraduationCap,
        iconClass: 'text-teal-500',
    },
    {
        label: 'Papers',
        value: Number(props.stats.totalPapers ?? 0),
        border: 'border-blue-300',
        icon: BookOpen,
        iconClass: 'text-blue-500',
    },
    {
        label: 'Pending Actions',
        value: Number(props.stats.pendingActions ?? 0),
        border: 'border-amber-300',
        icon: ClipboardList,
        iconClass: 'text-amber-500',
    },
]);

const studentCards = computed(() => [
    {
        label: 'Class Enrolled',
        value: Boolean(props.stats.hasClass) ? 'Yes' : 'No',
        border: 'border-orange-300',
        icon: School,
        iconClass: 'text-orange-500',
    },
    {
        label: 'Paper Submitted',
        value: Boolean(props.stats.hasPaper) ? 'Yes' : 'No',
        border: 'border-teal-300',
        icon: BookOpenCheck,
        iconClass: 'text-teal-500',
    },
    {
        label: 'Current Step',
        value: String(props.stats.currentStepLabel ?? 'Not started'),
        border: 'border-indigo-300',
        icon: ClipboardList,
        iconClass: 'text-indigo-500',
    },
]);

const statCards = computed(() => {
    if (props.role === 'faculty') {
        return facultyStatCards.value;
    }

    if (props.role === 'admin' || props.role === 'staff') {
        return adminStaffStatCards.value;
    }

    return studentCards.value;
});

function isStepActive(step: string): boolean {
    return (props.paper?.current_step ?? '') === step;
}

function stepCount(step: string): number {
    return Number(props.stepCounts?.[step] ?? 0);
}

function formatDate(date: string | null | undefined): string {
    if (!date) {
        return '-';
    }

    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function announcementDate(date: string | null): string {
    return date ? formatDate(date) : 'Recently posted';
}

function stepLabel(step?: string | null): string {
    if (!step) {
        return 'Unassigned';
    }

    return props.stepLabels[step] ?? step;
}

function paperLink(paperId: number): string {
    if (props.role === 'admin' || props.role === 'staff') {
        return admin.research.show.url({ paper: paperId });
    }

    return papersShow.url({ paper: paperId });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />

            <div class="flex flex-col gap-6 p-5 md:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-muted p-2.5">
                            <Megaphone class="h-5 w-5 text-orange-500" />
                        </div>
                        <div class="space-y-1">
                            <h1 class="text-2xl font-bold text-foreground">
                                Dashboard
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                Track announcements, workflow progress, and recent research activity in one place.
                            </p>
                        </div>
                    </div>

                    <div class="hidden rounded-xl bg-muted px-3 py-2 text-right md:block">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                            Updates
                        </p>
                        <p class="text-sm font-bold text-foreground">
                            {{ announcements.length }} active
                        </p>
                    </div>
                </div>

                <div
                    v-if="announcements.length === 0"
                    class="rounded-xl border border-dashed border-border bg-muted px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No announcements available.
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="announcement in announcements"
                        :key="announcement.id"
                        :class="[
                            'flex h-full flex-col gap-4 rounded-2xl border p-4',
                            announcementColors[announcement.type],
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p class="text-sm font-bold">
                                    {{ announcement.title }}
                                </p>
                                <p class="text-xs font-medium opacity-80">
                                    {{ announcementDate(announcement.published_at) }}
                                </p>
                            </div>
                            <span
                                v-if="announcement.is_pinned"
                                class="rounded-full border border-current px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                            >
                                Pinned
                            </span>
                        </div>

                        <p class="line-clamp-3 text-sm opacity-90">
                            {{ announcement.content }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <div
                v-for="card in statCards"
                :key="card.label"
                :class="[
                    'rounded-2xl border border-border border-l-4 bg-card p-4',
                    {
                        'border-l-orange-500': card.border === 'border-orange-300',
                        'border-l-teal-500': card.border === 'border-teal-300',
                        'border-l-green-500': card.border === 'border-green-300',
                        'border-l-indigo-500': card.border === 'border-indigo-300',
                        'border-l-amber-500': card.border === 'border-amber-300',
                        'border-l-blue-500': card.border === 'border-blue-300',
                    },
                ]"
            >
                <div class="flex items-center gap-2">
                    <component :is="card.icon" :class="['h-5 w-5', card.iconClass]" />
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        {{ card.label }}
                    </p>
                </div>
                <p class="mt-4 text-2xl font-bold text-foreground">
                    {{ card.value }}
                </p>
            </div>
        </section>

        <section
            v-if="role !== 'student'"
            class="rounded-2xl border border-border bg-card p-5 md:p-6"
        >
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-base font-bold text-foreground">
                    Workflow Step Counts
                </h2>
                <Link
                    v-if="role === 'admin' || role === 'staff'"
                    :href="admin.research.index.url()"
                    class="text-xs font-semibold text-orange-500 hover:text-orange-600"
                >
                    View Research Queue
                </Link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="[step, label] in orderedStepEntries"
                    :key="step"
                    class="rounded-2xl border border-border bg-muted p-4"
                >
                    <div class="mb-3 flex items-center justify-between gap-3 text-sm">
                        <span class="font-medium text-foreground">{{ label }}</span>
                        <span class="font-bold text-foreground">{{ stepCount(step) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-background">
                        <div
                            :class="['h-full rounded-full', getStepBarClass(step)]"
                            :style="{ width: `${(stepCount(step) / maxStepCount) * 100}%` }"
                        />
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-6" :class="role === 'faculty' ? 'xl:grid-cols-[2fr_1fr]' : 'grid-cols-1'">
            <section class="rounded-2xl border border-border bg-card p-5 md:p-6">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-base font-bold text-foreground">
                        Recent Papers
                    </h2>
                    <Link
                        :href="(role === 'admin' || role === 'staff') ? admin.research.index.url() : papersIndex.url()"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-teal-500 hover:text-teal-600"
                    >
                        Open list <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div v-if="visibleRecentPapers.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                    No papers to show.
                </div>

                <div v-else class="overflow-hidden rounded-2xl border border-border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Tracking</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Title</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground md:table-cell">Student</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground lg:table-cell">Step</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground xl:table-cell">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="paper in visibleRecentPapers"
                                :key="paper.id"
                                class="hover:bg-muted"
                            >
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ paper.tracking_id }}</td>
                                <td class="px-4 py-3">
                                    <Link
                                        :href="paperLink(paper.id)"
                                        class="line-clamp-1 font-semibold text-foreground hover:text-orange-500"
                                    >
                                        {{ paper.title }}
                                    </Link>
                                </td>
                                <td class="hidden px-4 py-3 text-muted-foreground md:table-cell">{{ paper.student_name ?? '-' }}</td>
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            getStepBadgeClass(paper.current_step ?? ''),
                                        ]"
                                    >
                                        {{ paper.step_label ?? stepLabel(paper.current_step) }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 text-xs text-muted-foreground xl:table-cell">{{ formatDate(paper.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                v-if="role === 'faculty'"
                class="rounded-2xl border border-border bg-card p-5 md:p-6"
            >
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 class="text-base font-bold text-foreground">
                        My Classes
                    </h2>
                    <Link
                        :href="facultyClassesIndex.url()"
                        class="text-xs font-semibold text-orange-500 hover:text-orange-600"
                    >
                        Open Classes
                    </Link>
                </div>
                <div v-if="visibleClasses.length === 0" class="text-sm text-muted-foreground">
                    No classes assigned.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="classItem in visibleClasses"
                        :key="classItem.id"
                        class="rounded-2xl border border-border bg-muted p-4"
                    >
                        <p class="text-sm font-semibold text-foreground">
                            {{ classItem.name }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ classItem.members_count ?? 0 }} students •
                            {{ classItem.research_papers_count ?? 0 }} papers
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <section
            v-if="role === 'student'"
            class="rounded-2xl border border-border bg-card p-5 md:p-6"
        >
            <div class="grid gap-6 lg:grid-cols-2">
                <div>
                    <h2 class="mb-4 text-base font-bold text-foreground">
                        Student Workflow
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="[step, label] in orderedStepEntries"
                            :key="step"
                            :class="[
                                'rounded-full border border-transparent px-2.5 py-1 text-xs font-semibold',
                                isStepActive(step)
                                    ? getStepBadgeClass(step)
                                    : 'border-border bg-card text-muted-foreground',
                            ]"
                        >
                            {{ label }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <h3 class="text-sm font-bold text-foreground">
                        Quick Actions
                    </h3>
                    <Link
                        :href="papersCreate.url()"
                        class="inline-flex w-fit items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-xs font-semibold text-white hover:bg-orange-600"
                    >
                        <BookOpen class="h-4 w-4" /> Submit Paper
                    </Link>
                    <Link
                        :href="papersIndex.url()"
                        class="inline-flex w-fit items-center gap-2 rounded-lg border border-border px-3 py-2 text-xs font-semibold text-foreground hover:border-teal-500 hover:text-teal-600"
                    >
                        <ClipboardList class="h-4 w-4" /> My Papers
                    </Link>
                </div>
            </div>
        </section>
    </div>
</template>
