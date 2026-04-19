<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import {
    AlertCircle,
    ArrowRight,
    ArrowUpRight,
    BookOpen,
    BookOpenCheck,
    CheckCircle2,
    ClipboardList,
    GraduationCap,
    Megaphone,
    School,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { getStepBadgeClass } from '@/lib/step-colors';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { index as facultyClassesIndex } from '@/routes/faculty/classes';
import { index as facultyResearchIndex } from '@/routes/faculty/research';
import {
    create as papersCreate,
    index as papersIndex,
    show as papersShow,
} from '@/routes/papers';

ChartJS.register(
    ArcElement,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
);

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

interface SubmissionPoint {
    month: string;
    count: number;
}

interface Props {
    role: 'admin' | 'staff' | 'faculty' | 'student';
    announcements: Announcement[];
    stepLabels: Record<string, string>;
    stats: Record<string, number | string | boolean | null>;
    stepCounts?: Record<string, number>;
    submissionsOverTime?: SubmissionPoint[];
    recentPapers?: DashboardPaper[];
    classes?: ClassSummary[];
    paper?: StudentPaper | null;
}

const props = defineProps<Props>();

const announcementIcon: Record<Announcement['type'], string> = {
    info: 'bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400',
    success: 'bg-green-100 text-green-600 dark:bg-green-950/50 dark:text-green-400',
    warning: 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
    danger: 'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400',
};

const announcementColors: Record<Announcement['type'], string> = {
    info: 'border-blue-200 bg-blue-50/50 dark:border-blue-900/40 dark:bg-blue-950/20',
    success: 'border-green-200 bg-green-50/50 dark:border-green-900/40 dark:bg-green-950/20',
    warning: 'border-amber-200 bg-amber-50/50 dark:border-amber-900/40 dark:bg-amber-950/20',
    danger: 'border-red-200 bg-red-50/50 dark:border-red-900/40 dark:bg-red-950/20',
};

const orderedStepEntries = computed(() => Object.entries(props.stepLabels ?? {}));
const visibleRecentPapers = computed(() => props.recentPapers ?? []);
const visibleClasses = computed(() => props.classes ?? []);

const maxStepCount = computed(() => {
    const counts = Object.values(props.stepCounts ?? {});
    return counts.length > 0 ? Math.max(1, ...counts) : 1;
});

const totalStepPapers = computed(() =>
    Object.values(props.stepCounts ?? {}).reduce((a, b) => a + b, 0),
);

// Chart data for submissions over time
const submissionsChartData = computed(() => ({
    labels: (props.submissionsOverTime ?? []).map(p => p.month),
    datasets: [
        {
            label: 'Submissions',
            data: (props.submissionsOverTime ?? []).map(p => p.count),
            backgroundColor: 'rgba(249, 115, 22, 0.15)',
            borderColor: 'rgb(249, 115, 22)',
            borderWidth: 2,
            borderRadius: 6,
            hoverBackgroundColor: 'rgba(249, 115, 22, 0.3)',
        },
    ],
}));

const submissionsChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleFont: { size: 12 },
            bodyFont: { size: 12 },
            padding: 10,
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, color: '#9ca3af' },
        },
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                font: { size: 11 },
                color: '#9ca3af',
            },
            grid: { color: 'rgba(156, 163, 175, 0.1)' },
        },
    },
}));

// Chart data for step distribution (doughnut)
// Uses the same stepCounts data already computed on the backend,
// mapped to each step's canonical color from the design system.
const stepChartRgb: Record<string, string> = {
    title_proposal:  'rgb(249, 115, 22)',
    ric_review:      'rgb(20, 184, 166)',
    plagiarism_check:'rgb(168, 85, 247)',
    outline_defense: 'rgb(59, 130, 246)',
    rating:          'rgb(245, 158, 11)',
    final_manuscript:'rgb(99, 102, 241)',
    final_defense:   'rgb(6, 182, 212)',
    hard_bound:      'rgb(132, 204, 22)',
    completed:       'rgb(34, 197, 94)',
};

const stepDistributionData = computed(() => {
    const counts = props.stepCounts ?? {};
    const labels = props.stepLabels ?? {};

    // Only include steps that actually have papers
    const activeSteps = Object.entries(counts).filter(([, v]) => v > 0);

    const stepKeys = activeSteps.map(([k]) => k);
    const data = activeSteps.map(([, v]) => v);
    const colors = stepKeys.map(k => stepChartRgb[k] ?? 'rgb(156, 163, 175)');

    return {
        labels: stepKeys.map(k => labels[k] ?? k),
        datasets: [
            {
                data,
                backgroundColor: colors.map(c => c.replace('rgb(', 'rgba(').replace(')', ', 0.25)')),
                borderColor: colors,
                borderWidth: 2.5,
                hoverOffset: 6,
                hoverBorderWidth: 3,
            },
        ],
    };
});

const stepDistributionHasData = computed(() =>
    Object.values(props.stepCounts ?? {}).some(v => v > 0),
);

const stepDistributionOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                padding: 12,
                usePointStyle: true,
                pointStyleWidth: 7,
                font: { size: 10 },
                color: '#6b7280',
                boxHeight: 7,
            },
        },
        tooltip: {
            backgroundColor: 'rgba(15, 15, 15, 0.88)',
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => {
                    const total = (ctx.dataset.data as number[]).reduce((a: number, b: number) => a + b, 0);
                    const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                    return `  ${ctx.parsed} paper${ctx.parsed !== 1 ? 's' : ''} (${pct}%)`;
                },
            },
        },
    },
}));

const adminStaffStatCards = computed(() => [
    {
        label: 'Total Papers',
        value: Number(props.stats.totalPapers ?? 0),
        icon: BookOpen,
        iconBg: 'bg-orange-100 dark:bg-orange-950/40',
        iconClass: 'text-orange-600 dark:text-orange-400',
        accent: 'from-orange-500/10 to-transparent',
        href: admin.research.index.url(),
    },
    {
        label: 'Pending Review',
        value: Number(props.stats.pendingReview ?? 0),
        icon: ClipboardList,
        iconBg: 'bg-teal-100 dark:bg-teal-950/40',
        iconClass: 'text-teal-600 dark:text-teal-400',
        accent: 'from-teal-500/10 to-transparent',
        href: admin.research.index.url({ query: { step: 'ric_review' } }),
    },
    {
        label: 'Completed',
        value: Number(props.stats.completed ?? 0),
        icon: CheckCircle2,
        iconBg: 'bg-green-100 dark:bg-green-950/40',
        iconClass: 'text-green-600 dark:text-green-400',
        accent: 'from-green-500/10 to-transparent',
        href: admin.research.index.url({ query: { step: 'completed' } }),
    },
    {
        label: 'Total Users',
        value: Number(props.stats.totalUsers ?? 0),
        icon: Users,
        iconBg: 'bg-indigo-100 dark:bg-indigo-950/40',
        iconClass: 'text-indigo-600 dark:text-indigo-400',
        accent: 'from-indigo-500/10 to-transparent',
        href: admin.users.index.url(),
    },
    {
        label: 'Pending Approval',
        value: Number(props.stats.pendingApproval ?? 0),
        icon: AlertCircle,
        iconBg: 'bg-amber-100 dark:bg-amber-950/40',
        iconClass: 'text-amber-600 dark:text-amber-400',
        accent: 'from-amber-500/10 to-transparent',
        href: admin.users.index.url({ query: { role: 'faculty' } }),
    },
]);

const facultyStatCards = computed(() => [
    {
        label: 'Classes',
        value: Number(props.stats.totalClasses ?? 0),
        icon: School,
        iconBg: 'bg-orange-100 dark:bg-orange-950/40',
        iconClass: 'text-orange-600 dark:text-orange-400',
        accent: 'from-orange-500/10 to-transparent',
        href: facultyClassesIndex.url(),
    },
    {
        label: 'Students',
        value: Number(props.stats.totalStudents ?? 0),
        icon: GraduationCap,
        iconBg: 'bg-teal-100 dark:bg-teal-950/40',
        iconClass: 'text-teal-600 dark:text-teal-400',
        accent: 'from-teal-500/10 to-transparent',
        href: facultyClassesIndex.url(),
    },
    {
        label: 'Papers',
        value: Number(props.stats.totalPapers ?? 0),
        icon: BookOpen,
        iconBg: 'bg-blue-100 dark:bg-blue-950/40',
        iconClass: 'text-blue-600 dark:text-blue-400',
        accent: 'from-blue-500/10 to-transparent',
        href: facultyResearchIndex.url(),
    },
    {
        label: 'Pending Actions',
        value: Number(props.stats.pendingActions ?? 0),
        icon: ClipboardList,
        iconBg: 'bg-amber-100 dark:bg-amber-950/40',
        iconClass: 'text-amber-600 dark:text-amber-400',
        accent: 'from-amber-500/10 to-transparent',
        href: facultyResearchIndex.url({ query: { step: 'ric_review' } }),
    },
]);

const studentCards = computed(() => [
    {
        label: 'Class Enrolled',
        value: Boolean(props.stats.hasClass) ? 'Yes' : 'No',
        icon: School,
        iconBg: 'bg-orange-100 dark:bg-orange-950/40',
        iconClass: 'text-orange-600 dark:text-orange-400',
        accent: 'from-orange-500/10 to-transparent',
        href: undefined as string | undefined,
    },
    {
        label: 'Paper Submitted',
        value: Boolean(props.stats.hasPaper) ? 'Yes' : 'No',
        icon: BookOpenCheck,
        iconBg: 'bg-teal-100 dark:bg-teal-950/40',
        iconClass: 'text-teal-600 dark:text-teal-400',
        accent: 'from-teal-500/10 to-transparent',
    },
    {
        label: 'Current Step',
        value: String(props.stats.currentStepLabel ?? 'Not started'),
        icon: ClipboardList,
        iconBg: 'bg-indigo-100 dark:bg-indigo-950/40',
        iconClass: 'text-indigo-600 dark:text-indigo-400',
        accent: 'from-indigo-500/10 to-transparent',
        href: undefined as string | undefined,
    },
]);

const statCards = computed(() => {
    if (props.role === 'faculty') return facultyStatCards.value;
    if (props.role === 'admin' || props.role === 'staff') return adminStaffStatCards.value;
    return studentCards.value;
});

// Workflow pipeline line chart
const pipelineChartData = computed(() => ({
    labels: Object.values(props.stepLabels ?? {}),
    datasets: [
        {
            label: 'Papers',
            data: Object.keys(props.stepLabels ?? {}).map((s) => stepCount(s)),
            borderColor: 'rgb(249, 115, 22)',
            backgroundColor: 'rgba(249, 115, 22, 0.12)',
            borderWidth: 2.5,
            pointBackgroundColor: 'rgb(249, 115, 22)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true,
            tension: 0.4,
        },
    ],
}));

const pipelineChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleFont: { size: 12 },
            bodyFont: { size: 12 },
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => ` ${ctx.parsed.y} papers`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 10 }, color: '#9ca3af', maxRotation: 30 },
        },
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1, font: { size: 11 }, color: '#9ca3af' },
            grid: { color: 'rgba(156, 163, 175, 0.1)' },
        },
    },
}));

function stepCount(step: string): number {
    return Number(props.stepCounts?.[step] ?? 0);
}

function isStepActive(step: string): boolean {
    return (props.paper?.current_step ?? '') === step;
}

function formatDate(date: string | null | undefined): string {
    if (!date) return '-';
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
    if (!step) return 'Unassigned';
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
        <!-- Page Header -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 p-2.5 shadow-lg shadow-orange-500/20">
                    <Megaphone class="h-5 w-5 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        Dashboard
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Monitor workflow progress, research activity, and key metrics.
                    </p>
                </div>
            </div>
            <div class="hidden items-center gap-2 rounded-full bg-muted px-3 py-1.5 text-xs font-medium text-muted-foreground md:flex">
                <span class="h-2 w-2 rounded-full bg-green-500" />
                Live data
            </div>
        </div>

        <!-- Announcements -->
        <section v-if="announcements.length > 0">
            <div class="mb-3 flex items-center gap-2">
                <h2 class="text-sm font-semibold text-foreground">Announcements</h2>
                <span class="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-600 dark:bg-orange-950/40 dark:text-orange-400">
                    {{ announcements.length }}
                </span>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="announcement in announcements"
                    :key="announcement.id"
                    :class="[
                        'group relative overflow-hidden rounded-xl border p-4 transition-shadow hover:shadow-md',
                        announcementColors[announcement.type],
                    ]"
                >
                    <div class="flex items-start gap-3">
                        <div :class="['shrink-0 rounded-lg p-1.5', announcementIcon[announcement.type]]">
                            <Megaphone class="h-3.5 w-3.5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-foreground">
                                    {{ announcement.title }}
                                </p>
                                <span
                                    v-if="announcement.is_pinned"
                                    class="shrink-0 rounded-md bg-foreground/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-foreground/70"
                                >
                                    Pinned
                                </span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                {{ announcement.content }}
                            </p>
                            <p class="mt-2 text-[10px] font-medium text-muted-foreground/70">
                                {{ announcementDate(announcement.published_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stat Cards -->
        <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            <Link
                v-for="card in statCards"
                :key="card.label"
                :href="card.href ?? '#'"
                class="group relative overflow-hidden rounded-xl border bg-card shadow-sm transition-shadow hover:shadow-md"
            >
                <div :class="['pointer-events-none absolute inset-0 bg-gradient-to-br', card.accent]" />
                <div class="relative p-4">
                    <div class="flex items-center justify-between">
                        <div :class="['rounded-lg p-2', card.iconBg]">
                            <component :is="card.icon" :class="['h-4 w-4', card.iconClass]" />
                        </div>
                        <ArrowUpRight class="h-4 w-4 text-muted-foreground/40 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-muted-foreground" />
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold tracking-tight text-foreground">
                            {{ card.value }}
                        </p>
                        <p class="mt-0.5 text-xs font-medium text-muted-foreground">
                            {{ card.label }}
                        </p>
                    </div>
                </div>
            </Link>
        </section>

        <!-- Charts Row (admin/staff/faculty) -->
        <section
            v-if="role !== 'student'"
            class="grid gap-4 lg:grid-cols-5"
        >
            <!-- Submissions Over Time -->
            <Card class="lg:col-span-3">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-semibold">Submissions Over Time</CardTitle>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <TrendingUp class="h-3.5 w-3.5" />
                        Last 6 months
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="h-56">
                        <Bar
                            v-if="(submissionsOverTime ?? []).length > 0"
                            :data="submissionsChartData"
                            :options="submissionsChartOptions"
                        />
                        <div v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                            No submission data yet.
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Step Distribution -->
            <Card class="lg:col-span-2">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-semibold">Step Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="relative h-56">
                        <Doughnut
                            v-if="stepDistributionHasData"
                            :data="stepDistributionData"
                            :options="stepDistributionOptions"
                        />
                        <div v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                            No pipeline data yet.
                        </div>
                        <!-- Center: total paper count -->
                        <div
                            v-if="stepDistributionHasData"
                            class="pointer-events-none absolute inset-x-0 top-0 flex items-center justify-center"
                            style="height: calc(100% - 3.5rem)"
                        >
                            <div class="text-center">
                                <p class="text-2xl font-bold tabular-nums text-foreground">{{ totalStepPapers }}</p>
                                <p class="text-[10px] font-medium text-muted-foreground">Total</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- Workflow Step Counts -->
        <section v-if="role !== 'student'">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <div>
                        <CardTitle class="text-sm font-semibold">Workflow Pipeline</CardTitle>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ totalStepPapers }} papers across all stages
                        </p>
                    </div>
                    <Link
                        v-if="role === 'admin' || role === 'staff'"
                        :href="admin.research.index.url()"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-orange-500 hover:text-orange-600"
                    >
                        View Queue <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </CardHeader>
                <CardContent>
                    <div class="h-64">
                        <Line
                            v-if="totalStepPapers > 0"
                            :data="pipelineChartData"
                            :options="pipelineChartOptions"
                        />
                        <div v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
                            No pipeline data yet.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <!-- Recent Papers + Sidebar -->
        <div class="grid gap-4" :class="role === 'faculty' ? 'xl:grid-cols-[3fr_1fr]' : 'grid-cols-1'">
            <!-- Recent Papers -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-semibold">Recent Papers</CardTitle>
                    <Link
                        :href="(role === 'admin' || role === 'staff') ? admin.research.index.url() : papersIndex.url()"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300"
                    >
                        View all <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </CardHeader>
                <CardContent>
                    <div v-if="visibleRecentPapers.length === 0" class="py-12 text-center">
                        <BookOpen class="mx-auto h-8 w-8 text-muted-foreground/40" />
                        <p class="mt-2 text-sm text-muted-foreground">No papers to show yet.</p>
                    </div>

                    <div v-else class="overflow-hidden rounded-lg border border-border">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border bg-muted/50">
                                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Tracking</th>
                                    <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Title</th>
                                    <th class="hidden px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground md:table-cell">Student</th>
                                    <th class="hidden px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell">Step</th>
                                    <th class="hidden px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="paper in visibleRecentPapers"
                                    :key="paper.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-muted px-1.5 py-0.5 font-mono text-[11px] text-muted-foreground">
                                            {{ paper.tracking_id }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="paperLink(paper.id)"
                                            class="line-clamp-1 text-sm font-medium text-foreground hover:text-orange-500"
                                        >
                                            {{ paper.title }}
                                        </Link>
                                    </td>
                                    <td class="hidden px-4 py-3 text-xs text-muted-foreground md:table-cell">
                                        {{ paper.student_name ?? '-' }}
                                    </td>
                                    <td class="hidden px-4 py-3 lg:table-cell">
                                        <span
                                            :class="[
                                                'inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                                getStepBadgeClass(paper.current_step ?? ''),
                                            ]"
                                        >
                                            {{ paper.step_label ?? stepLabel(paper.current_step) }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-3 text-xs text-muted-foreground xl:table-cell">
                                        {{ formatDate(paper.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Faculty Classes Sidebar -->
            <Card v-if="role === 'faculty'">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-semibold">My Classes</CardTitle>
                    <Link
                        :href="facultyClassesIndex.url()"
                        class="text-xs font-semibold text-orange-500 hover:text-orange-600"
                    >
                        View all
                    </Link>
                </CardHeader>
                <CardContent>
                    <div v-if="visibleClasses.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        No classes assigned.
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="classItem in visibleClasses"
                            :key="classItem.id"
                            class="rounded-lg border border-border bg-background p-3 transition-colors hover:bg-muted/50"
                        >
                            <p class="text-sm font-semibold text-foreground">
                                {{ classItem.name }}
                            </p>
                            <div class="mt-1.5 flex items-center gap-3 text-[11px] text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Users class="h-3 w-3" />
                                    {{ classItem.members_count ?? 0 }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <BookOpen class="h-3 w-3" />
                                    {{ classItem.research_papers_count ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Student Section -->
        <section v-if="role === 'student'">
            <Card>
                <CardContent class="p-5">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <h2 class="mb-4 text-sm font-semibold text-foreground">
                                Workflow Progress
                            </h2>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="[step, label] in orderedStepEntries"
                                    :key="step"
                                    :class="[
                                        'rounded-md border border-transparent px-2.5 py-1 text-xs font-semibold transition-all',
                                        isStepActive(step)
                                            ? getStepBadgeClass(step)
                                            : 'border-border bg-card text-muted-foreground',
                                    ]"
                                >
                                    {{ label }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <h3 class="text-sm font-semibold text-foreground">
                                Quick Actions
                            </h3>
                            <Link
                                :href="papersCreate.url()"
                                class="inline-flex w-fit items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-orange-600"
                            >
                                <BookOpen class="h-4 w-4" /> Submit Paper
                            </Link>
                            <Link
                                :href="papersIndex.url()"
                                class="inline-flex w-fit items-center gap-2 rounded-lg border border-border px-4 py-2 text-xs font-semibold text-foreground transition-colors hover:border-teal-500 hover:text-teal-600"
                            >
                                <ClipboardList class="h-4 w-4" /> My Papers
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
