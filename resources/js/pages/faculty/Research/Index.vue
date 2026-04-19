<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileSearch, Filter, Search, ScrollText, Users } from 'lucide-vue-next';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import { getStepBadgeClass } from '@/lib/step-colors';
import { index as classesIndex } from '@/routes/faculty/classes';
import research from '@/routes/faculty/classes/research';

interface SchoolClass {
    id: number;
    name: string;
    section?: string | null;
    subjects?: Array<{ id: number; name: string; code?: string }>;
    faculty_id?: number;
    members_count?: number;
}

interface Paper {
    id: number;
    title: string;
    tracking_id: string;
    current_step: string;
    step_label?: string;
    student_name?: string;
    student?: { id: number; name: string } | null;
    created_at?: string;
}

interface Props {
    class?: SchoolClass;
    schoolClass?: SchoolClass;
    papers: Paper[];
    stepCounts: Record<string, number>;
    stepLabels: Record<string, string>;
}

const props = defineProps<Props>();

const schoolClass = computed(() => props.schoolClass ?? props.class);
const activeStep = ref<string>('all');
const searchQuery = ref('');
const debouncedSearch = ref('');
watchDebounced(searchQuery, (val) => { debouncedSearch.value = val; }, { debounce: 300 });

const orderedSteps = [
    'title_proposal',
    'ric_review',
    'plagiarism_check',
    'outline_defense',
    'rating',
    'final_manuscript',
    'final_defense',
    'hard_bound',
    'completed',
];

const stepPills = computed(() => {
    const allCount = props.papers.length;

    return [
        { key: 'all', label: 'All', count: allCount },
        ...orderedSteps.map((step) => ({
            key: step,
            label: props.stepLabels[step] ?? step,
            count: Number(props.stepCounts[step] ?? 0),
        })),
    ];
});

const filteredPapers = computed(() => {
    let list = props.papers;

    if (activeStep.value !== 'all') {
        list = list.filter((p) => p.current_step === activeStep.value);
    }

    const q = debouncedSearch.value.trim().toLowerCase();
    if (q) {
        list = list.filter((p) =>
            p.title.toLowerCase().includes(q) ||
            p.tracking_id.toLowerCase().includes(q) ||
            studentName(p).toLowerCase().includes(q),
        );
    }

    return list;
});

const completedCount = computed(() => props.stepCounts['completed'] ?? 0);

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function studentName(paper: Paper): string {
    return paper.student_name ?? paper.student?.name ?? '-';
}

function formatDate(value?: string): string {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My Classes', href: classesIndex() },
            { title: 'Research Papers', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="`${schoolClass?.name ?? 'Class'} Research Papers`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header + Stats -->
        <section class="rounded-2xl border border-border bg-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        {{ schoolClass?.name ?? 'Research Papers' }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Track paper progress by workflow step.
                    </p>
                </div>
                <div class="flex gap-3">
                    <div class="rounded-xl border border-border bg-muted px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            <ScrollText class="h-3.5 w-3.5 text-orange-500" />
                            Papers
                        </div>
                        <p class="mt-1 text-xl font-bold text-foreground">{{ papers.length }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            <ScrollText class="h-3.5 w-3.5 text-green-500" />
                            Completed
                        </div>
                        <p class="mt-1 text-xl font-bold text-foreground">{{ completedCount }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            <Users class="h-3.5 w-3.5 text-teal-500" />
                            Members
                        </div>
                        <p class="mt-1 text-xl font-bold text-foreground">{{ schoolClass?.members_count ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search + Step Filters -->
        <section class="rounded-2xl border border-border bg-card">
            <div class="p-4">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by title, tracking ID, or student..."
                        class="w-full rounded-xl border border-input bg-background py-2.5 pl-10 pr-3 text-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>
            </div>
            <div class="border-t border-border px-4 py-3">
                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <Filter class="h-4 w-4" />
                    Step Filters
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="pill in stepPills"
                        :key="pill.key"
                        type="button"
                        @click="activeStep = pill.key"
                        :class="[
                            'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                            activeStep === pill.key
                                ? 'border-orange-500 bg-orange-50 text-orange-700 dark:border-orange-700 dark:bg-orange-950/30 dark:text-orange-300'
                                : 'border-border bg-card text-muted-foreground hover:border-orange-300 hover:text-orange-600',
                        ]"
                    >
                        {{ pill.label }}
                        <span class="rounded-full bg-black/5 px-2 py-0.5 text-[10px] dark:bg-white/10">
                            {{ pill.count }}
                        </span>
                    </button>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div v-if="filteredPapers.length === 0" class="p-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30">
                    <FileSearch class="h-6 w-6 text-orange-500" />
                </div>
                <h2 class="text-base font-bold text-foreground">No papers found</h2>
                <p class="mt-1 text-sm text-muted-foreground">No research papers match this step yet.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tracking ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Current Step</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Date</th>
                            <th class="w-24 px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="paper in filteredPapers" :key="paper.id" class="hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                    {{ paper.tracking_id }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="line-clamp-1 font-semibold text-foreground">{{ paper.title }}</p>
                            </td>
                            <td class="px-4 py-3 text-foreground">
                                {{ studentName(paper) }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', getStepBadgeClass(paper.current_step)]">
                                    {{ paper.step_label ?? stepLabel(paper.current_step) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{ formatDate(paper.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="research.show.url({ class: schoolClass?.id ?? 0, paper: paper.id })"
                                    class="inline-flex rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-foreground transition-colors hover:bg-muted"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
