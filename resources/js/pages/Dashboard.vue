<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    ClipboardList,
    Eye,
    FileText,
    GraduationCap,
    PenLine,
    PlusCircle,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import { index as papersIndex, create as papersCreate } from '@/routes/papers';
import { edit as profileEdit } from '@/routes/profile';

interface Paper {
    id: number;
    title: string;
    status: string;
    tracking_id: string;
    views: number;
    created_at: string;
    category?: { name: string };
    user?: { name: string };
}

interface Stats {
    totalPapers: number;
    published: number;
    underReview: number;
    totalViews: number;
    authoredPapers?: number; // faculty
    pendingReview?: number; // staff
    totalUsers?: number; // staff
}

interface Props {
    role: string;
    stats: Stats;
    recentPapers: Paper[];
    statusCounts: Record<string, number>;
    profileComplete?: boolean; // student
}

const props = defineProps<Props>();

const statusColors: Record<string, string> = {
    submitted:
        'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
    under_review:
        'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
    approved:
        'bg-teal-100 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300',
    presented:
        'bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300',
    published:
        'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-300',
    archived:
        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
};

const statusLabel = (s: string) =>
    s.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const pageTitle = computed(() => {
    if (props.role === 'faculty') {
return 'Research Overview';
}

    if (props.role === 'staff' || props.role === 'admin') {
return 'System Overview';
}

    return 'Overview';
});

const pageSubtitle = computed(() => {
    if (props.role === 'faculty') {
return 'Papers you submitted or co-authored.';
}

    if (props.role === 'staff' || props.role === 'admin') {
return 'System-wide research activity.';
}

    return 'Your research activity at a glance.';
});

const statCards = computed(() => {
    const base = [
        {
            label:
                props.role === 'staff' || props.role === 'admin'
                    ? 'All Papers'
                    : 'Total Papers',
            value: props.stats.totalPapers,
            icon: FileText,
            color: 'text-orange-500',
            bg: 'bg-orange-50 dark:bg-orange-950/30',
            border: 'border-orange-100 dark:border-orange-900/40',
        },
        {
            label: 'Published',
            value: props.stats.published,
            icon: BookOpen,
            color: 'text-teal-500',
            bg: 'bg-teal-50 dark:bg-teal-950/30',
            border: 'border-teal-100 dark:border-teal-900/40',
        },
        {
            label: 'Under Review',
            value: props.stats.underReview,
            icon: TrendingUp,
            color: 'text-amber-500',
            bg: 'bg-amber-50 dark:bg-amber-950/30',
            border: 'border-amber-100 dark:border-amber-900/40',
        },
        {
            label: 'Total Views',
            value: props.stats.totalViews,
            icon: Eye,
            color: 'text-blue-500',
            bg: 'bg-blue-50 dark:bg-blue-950/30',
            border: 'border-blue-100 dark:border-blue-900/40',
        },
    ];

    if (props.role === 'faculty' && props.stats.authoredPapers !== undefined) {
        base[0] = {
            label: 'Co-authored',
            value: props.stats.authoredPapers,
            icon: PenLine,
            color: 'text-orange-500',
            bg: 'bg-orange-50 dark:bg-orange-950/30',
            border: 'border-orange-100 dark:border-orange-900/40',
        };
    }

    if (
        (props.role === 'staff' || props.role === 'admin') &&
        props.stats.pendingReview !== undefined
    ) {
        base.push({
            label: 'Pending Review',
            value: props.stats.pendingReview,
            icon: ClipboardList,
            color: 'text-violet-500',
            bg: 'bg-violet-50 dark:bg-violet-950/30',
            border: 'border-violet-100 dark:border-violet-900/40',
        });
        base.push({
            label: 'Total Users',
            value: props.stats.totalUsers ?? 0,
            icon: Users,
            color: 'text-rose-500',
            bg: 'bg-rose-50 dark:bg-rose-950/30',
            border: 'border-rose-100 dark:border-rose-900/40',
        });
    }

    return base;
});

const maxCount = computed(() =>
    Math.max(1, ...Object.values(props.statusCounts).map(Number)),
);

const barColors: Record<string, string> = {
    submitted: 'bg-blue-500',
    under_review: 'bg-amber-500',
    approved: 'bg-teal-500',
    presented: 'bg-purple-500',
    published: 'bg-green-500',
    archived: 'bg-slate-400',
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Profile incomplete banner (students only) -->
        <div
            v-if="role === 'student' && profileComplete === false"
            class="flex items-center justify-between rounded-xl border border-orange-200 bg-orange-50 px-5 py-3.5 dark:border-orange-900/40 dark:bg-orange-950/20"
        >
            <div class="flex items-center gap-3">
                <GraduationCap class="h-5 w-5 shrink-0 text-orange-500" />
                <div>
                    <p
                        class="text-sm font-semibold text-orange-800 dark:text-orange-300"
                    >
                        Complete your profile
                    </p>
                    <p class="text-xs text-orange-600 dark:text-orange-400">
                        Add your department and program so your submissions are
                        properly tracked.
                    </p>
                </div>
            </div>
            <Link
                :href="profileEdit()"
                class="shrink-0 rounded-lg bg-orange-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-600"
            >
                Update profile
            </Link>
        </div>

        <!-- Welcome bar -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white">
                    {{ pageTitle }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ pageSubtitle }}
                </p>
            </div>
            <!-- Student CTA -->
            <Link v-if="role === 'student'" :href="papersCreate.url()">
                <button
                    class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-2 text-sm font-semibold text-white shadow shadow-orange-500/25 transition-all hover:scale-[1.02] hover:from-orange-600 hover:to-orange-700"
                >
                    <PlusCircle class="h-4 w-4" />
                    New Paper
                </button>
            </Link>
            <!-- Faculty CTA -->
            <Link v-else-if="role === 'faculty'" :href="papersCreate.url()">
                <button
                    class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow shadow-teal-500/25 transition-all hover:scale-[1.02] hover:from-teal-600 hover:to-teal-700"
                >
                    <PlusCircle class="h-4 w-4" />
                    Submit Paper
                </button>
            </Link>
            <!-- Staff/Admin CTA -->
            <Link v-else :href="papersIndex.url()">
                <button
                    class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-500 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow shadow-violet-500/25 transition-all hover:scale-[1.02] hover:from-violet-600 hover:to-violet-700"
                >
                    <ClipboardList class="h-4 w-4" />
                    Manage Papers
                </button>
            </Link>
        </div>

        <!-- Stats Grid -->
        <div
            :class="[
                'grid gap-4',
                role === 'staff' || role === 'admin'
                    ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-6'
                    : 'grid-cols-2 md:grid-cols-4',
            ]"
        >
            <div
                v-for="stat in statCards"
                :key="stat.label"
                :class="[
                    'flex items-center gap-4 rounded-2xl border p-5',
                    stat.bg,
                    stat.border,
                ]"
            >
                <div
                    :class="[
                        'flex h-10 w-10 items-center justify-center rounded-xl border bg-white/60 shadow-sm dark:bg-white/10',
                        stat.border,
                    ]"
                >
                    <component
                        :is="stat.icon"
                        :class="['h-5 w-5', stat.color]"
                    />
                </div>
                <div>
                    <p :class="['text-2xl font-black', stat.color]">
                        {{ stat.value }}
                    </p>
                    <p
                        class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        {{ stat.label }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Recent Papers -->
            <div
                class="rounded-2xl border border-sidebar-border/70 bg-white p-6 md:col-span-2 dark:bg-sidebar"
            >
                <div class="mb-5 flex items-center justify-between">
                    <h2
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        {{
                            role === 'staff' || role === 'admin'
                                ? 'Recent Submissions'
                                : 'Recent Papers'
                        }}
                    </h2>
                    <Link
                        :href="papersIndex.url()"
                        class="flex items-center gap-1 text-xs font-medium text-orange-500 hover:text-orange-600"
                    >
                        View all <ArrowRight class="h-3 w-3" />
                    </Link>
                </div>

                <div v-if="recentPapers.length === 0" class="py-10 text-center">
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <FileText class="h-6 w-6 text-orange-400" />
                    </div>
                    <p
                        class="text-sm font-medium text-slate-600 dark:text-slate-400"
                    >
                        No papers yet
                    </p>
                    <Link
                        v-if="role !== 'staff' && role !== 'admin'"
                        :href="papersCreate.url()"
                        class="mt-2 inline-block text-xs text-orange-500 hover:underline"
                    >
                        Submit your first paper →
                    </Link>
                </div>

                <div v-else class="space-y-2">
                    <Link
                        v-for="paper in recentPapers"
                        :key="paper.id"
                        :href="`/papers/${paper.id}`"
                        class="group flex items-center justify-between rounded-xl border border-transparent p-3 transition-all hover:border-sidebar-border/70 hover:bg-orange-50/50 dark:hover:bg-orange-950/10"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-semibold text-slate-800 transition-colors group-hover:text-orange-600 dark:text-slate-200 dark:group-hover:text-orange-400"
                            >
                                {{ paper.title }}
                            </p>
                            <div
                                class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span
                                    v-if="
                                        paper.user &&
                                        (role === 'staff' ||
                                            role === 'admin' ||
                                            role === 'faculty')
                                    "
                                    class="font-medium text-slate-500 dark:text-slate-400"
                                >
                                    {{ paper.user.name }}
                                </span>
                                <span v-if="paper.user && paper.category"
                                    >·</span
                                >
                                <span
                                    v-if="paper.category"
                                    class="font-medium"
                                    >{{ paper.category.name }}</span
                                >
                                <span v-if="paper.category">·</span>
                                <code
                                    class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-slate-800"
                                    >{{ paper.tracking_id }}</code
                                >
                            </div>
                        </div>
                        <span
                            :class="[
                                'ml-4 shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                statusColors[paper.status] ??
                                    statusColors.archived,
                            ]"
                        >
                            {{ statusLabel(paper.status) }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Status Breakdown -->
            <div
                class="rounded-2xl border border-sidebar-border/70 bg-white p-6 dark:bg-sidebar"
            >
                <h2
                    class="mb-5 text-base font-bold text-slate-900 dark:text-white"
                >
                    Status Breakdown
                </h2>

                <div
                    v-if="Object.keys(statusCounts).length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No data yet
                </div>

                <div v-else class="space-y-3.5">
                    <div v-for="(count, status) in statusCounts" :key="status">
                        <div
                            class="mb-1.5 flex items-center justify-between text-xs font-medium"
                        >
                            <span class="text-slate-600 dark:text-slate-400">{{
                                statusLabel(String(status))
                            }}</span>
                            <span
                                class="font-bold text-slate-800 dark:text-slate-200"
                                >{{ count }}</span
                            >
                        </div>
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                        >
                            <div
                                :class="[
                                    'h-full rounded-full transition-all',
                                    barColors[String(status)] ?? 'bg-slate-400',
                                ]"
                                :style="{
                                    width: `${(Number(count) / maxCount) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Role-specific quick links at the bottom -->
                <div
                    v-if="role === 'student'"
                    class="mt-6 border-t border-sidebar-border/50 pt-5"
                >
                    <p
                        class="mb-3 text-xs font-semibold tracking-wide text-slate-400 uppercase"
                    >
                        Quick Actions
                    </p>
                    <div class="space-y-2">
                        <Link
                            :href="papersCreate.url()"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-orange-50 hover:text-orange-600 dark:text-slate-400 dark:hover:bg-orange-950/20"
                        >
                            <PlusCircle class="h-4 w-4" /> Submit paper
                        </Link>
                        <Link
                            :href="papersIndex.url()"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-orange-50 hover:text-orange-600 dark:text-slate-400 dark:hover:bg-orange-950/20"
                        >
                            <FileText class="h-4 w-4" /> My papers
                        </Link>
                        <Link
                            :href="profileEdit()"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-orange-50 hover:text-orange-600 dark:text-slate-400 dark:hover:bg-orange-950/20"
                        >
                            <GraduationCap class="h-4 w-4" /> Complete profile
                        </Link>
                    </div>
                </div>

                <div
                    v-else-if="role === 'faculty'"
                    class="mt-6 border-t border-sidebar-border/50 pt-5"
                >
                    <p
                        class="mb-3 text-xs font-semibold tracking-wide text-slate-400 uppercase"
                    >
                        Quick Actions
                    </p>
                    <div class="space-y-2">
                        <Link
                            :href="papersCreate.url()"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-teal-50 hover:text-teal-600 dark:text-slate-400 dark:hover:bg-teal-950/20"
                        >
                            <PlusCircle class="h-4 w-4" /> Submit paper
                        </Link>
                        <Link
                            :href="papersIndex.url()"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-teal-50 hover:text-teal-600 dark:text-slate-400 dark:hover:bg-teal-950/20"
                        >
                            <FileText class="h-4 w-4" /> All papers
                        </Link>
                    </div>
                </div>

                <div v-else class="mt-6 border-t border-sidebar-border/50 pt-5">
                    <p
                        class="mb-3 text-xs font-semibold tracking-wide text-slate-400 uppercase"
                    >
                        Quick Actions
                    </p>
                    <div class="space-y-2">
                        <Link
                            :href="papersIndex.url()"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-violet-50 hover:text-violet-600 dark:text-slate-400 dark:hover:bg-violet-950/20"
                        >
                            <ClipboardList class="h-4 w-4" /> Review submissions
                        </Link>
                        <Link
                            href="/admin/users"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-violet-50 hover:text-violet-600 dark:text-slate-400 dark:hover:bg-violet-950/20"
                        >
                            <Users class="h-4 w-4" /> Manage users
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
