<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    FileSearch,
    Filter,
    Loader2,
    Search,
    ScrollText,
    Users,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { getStepBadgeClass } from '@/lib/step-colors';
import { index as classesIndex } from '@/routes/faculty/classes';
import research from '@/routes/faculty/classes/research';

interface SchoolClass {
    id: string;
    name: string;
    section?: string | null;
    subjects?: Array<{ id: string; name: string; code?: string }>;
    faculty_id?: number;
    members_count?: number;
}

interface SdgItem {
    id: string;
    number: number;
    name: string;
    color: string | null;
}

interface AgendaItem {
    id: string;
    name: string;
}

interface Paper {
    id: string;
    title: string;
    tracking_id: string;
    current_step: string;
    step_label?: string;
    student_name?: string;
    student?: { id: string; name: string } | null;
    sdg_ids?: string[];
    agenda_ids?: string[];
    created_at?: string;
}

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    class?: SchoolClass;
    schoolClass?: SchoolClass;
    papers: Paginator<Paper>;
    sdgs: SdgItem[];
    agendas: AgendaItem[];
    stepCounts: Record<string, number>;
    stepLabels: Record<string, string>;
    filters: {
        search?: string | null;
        step?: string | null;
    };
}

const props = defineProps<Props>();

const schoolClass = computed(() => props.schoolClass ?? props.class);

const sdgMap = computed(() => {
    const map = new Map<string, SdgItem>();

    for (const sdg of props.sdgs) {
        map.set(sdg.id, sdg);
    }

    return map;
});

const agendaMap = computed(() => {
    const map = new Map<string, AgendaItem>();

    for (const agenda of props.agendas) {
        map.set(agenda.id, agenda);
    }

    return map;
});

function paperSdgs(paper: Paper): SdgItem[] {
    return (paper.sdg_ids ?? [])
        .map((id) => sdgMap.value.get(id))
        .filter((s): s is SdgItem => !!s);
}

function paperAgendas(paper: Paper): AgendaItem[] {
    return (paper.agenda_ids ?? [])
        .map((id) => agendaMap.value.get(id))
        .filter((a): a is AgendaItem => !!a);
}

const search = ref(props.filters.search ?? '');
const selectedStep = ref(props.filters.step ?? '');
const loading = ref(false);
const mounted = ref(false);
let debounce: ReturnType<typeof setTimeout>;

const stopStart = router.on('start', () => {
    loading.value = true;
});
const stopFinish = router.on('finish', () => {
    loading.value = false;
});
onUnmounted(() => {
    stopStart();
    stopFinish();
});

onMounted(() => {
    mounted.value = true;
});

function applyFilters() {
    if (!mounted.value) {
        return;
    }

    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            research.index.url({ class: schoolClass.value?.id ?? '' }),
            {
                search: search.value || undefined,
                step: selectedStep.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 350);
}

watch([search, selectedStep], applyFilters);

const orderedSteps = [
    'title_proposal',
    'ric_review',
    'outline_defense',
    'data_gathering',
    'rating',
    'final_manuscript',
    'final_defense',
    'hard_bound',
    'completed',
];

const totalPaperCount = computed(() =>
    Object.values(props.stepCounts).reduce((s, c) => s + c, 0),
);

const stepPills = computed(() => [
    { key: '', label: 'All', count: totalPaperCount.value },
    ...orderedSteps.map((step) => ({
        key: step,
        label: props.stepLabels[step] ?? step,
        count: Number(props.stepCounts[step] ?? 0),
    })),
]);

const completedCount = computed(() => props.stepCounts['completed'] ?? 0);

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function studentName(paper: Paper): string {
    return paper.student_name ?? paper.student?.name ?? '-';
}

function formatDate(value?: string): string {
    if (!value) {
        return '-';
    }

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
                    <div
                        class="rounded-xl border border-border bg-muted px-4 py-3 text-center"
                    >
                        <div
                            class="flex items-center justify-center gap-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <ScrollText class="h-3.5 w-3.5 text-orange-500" />
                            Papers
                        </div>
                        <p class="mt-1 text-xl font-bold text-foreground">
                            {{ totalPaperCount }}
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-border bg-muted px-4 py-3 text-center"
                    >
                        <div
                            class="flex items-center justify-center gap-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <ScrollText class="h-3.5 w-3.5 text-green-500" />
                            Completed
                        </div>
                        <p class="mt-1 text-xl font-bold text-foreground">
                            {{ completedCount }}
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-border bg-muted px-4 py-3 text-center"
                    >
                        <div
                            class="flex items-center justify-center gap-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <Users class="h-3.5 w-3.5 text-teal-500" />
                            Members
                        </div>
                        <p class="mt-1 text-xl font-bold text-foreground">
                            {{ schoolClass?.members_count ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search + Step Filters -->
        <section class="rounded-2xl border border-border bg-card">
            <div class="p-4">
                <div class="relative">
                    <Search
                        v-if="!loading"
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Loader2
                        v-else
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 animate-spin text-orange-500"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by title, tracking ID, or student..."
                        class="w-full rounded-xl border border-input bg-background py-2.5 pr-3 pl-10 text-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>
            </div>
            <div class="border-t border-border px-4 py-3">
                <div
                    class="mb-2 flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <Filter class="h-4 w-4" />
                    Step Filters
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="pill in stepPills"
                        :key="pill.key"
                        type="button"
                        @click="selectedStep = pill.key"
                        :class="[
                            'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                            selectedStep === pill.key
                                ? 'border-orange-500 bg-orange-50 text-orange-700 dark:border-orange-700 dark:bg-orange-950/30 dark:text-orange-300'
                                : 'border-border bg-card text-muted-foreground hover:border-orange-300 hover:text-orange-600',
                        ]"
                    >
                        {{ pill.label }}
                        <span
                            class="rounded-full bg-black/5 px-2 py-0.5 text-[10px] dark:bg-white/10"
                        >
                            {{ pill.count }}
                        </span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Results count -->
        <div
            class="flex items-center justify-between text-xs text-muted-foreground"
        >
            <span v-if="loading" class="italic">Fetching results…</span>
            <template v-else-if="papers.total === 0">
                <span>No results</span>
            </template>
            <span v-else>
                Showing
                <span class="font-semibold text-foreground"
                    >{{ papers.from }}–{{ papers.to }}</span
                >
                of
                <span class="font-semibold text-foreground">{{
                    papers.total
                }}</span>
                {{ papers.total === 1 ? 'paper' : 'papers' }}
            </span>
        </div>

        <section
            class="relative overflow-hidden rounded-2xl border border-border bg-card"
        >
            <!-- Loading overlay -->
            <div
                v-if="loading"
                class="absolute inset-0 z-10 flex items-center justify-center bg-background/60 backdrop-blur-[2px]"
            >
                <div
                    class="flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 shadow-sm"
                >
                    <Loader2 class="h-4 w-4 animate-spin text-orange-500" />
                    <span class="text-xs font-semibold text-muted-foreground"
                        >Loading results…</span
                    >
                </div>
            </div>
            <div v-if="papers.data.length === 0" class="p-12 text-center">
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                >
                    <FileSearch class="h-6 w-6 text-orange-500" />
                </div>
                <h2 class="text-base font-bold text-foreground">
                    No papers found
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    No research papers match this step yet.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted">
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Tracking ID
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Title
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Student
                            </th>
                            <th
                                class="hidden px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase xl:table-cell"
                            >
                                SDGs
                            </th>
                            <th
                                class="hidden px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase xl:table-cell"
                            >
                                Agendas
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Current Step
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Date
                            </th>
                            <th class="w-24 px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="paper in papers.data"
                            :key="paper.id"
                            class="hover:bg-muted/50"
                        >
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground"
                                >
                                    {{ paper.tracking_id }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p
                                    class="line-clamp-1 font-semibold text-foreground"
                                >
                                    {{ paper.title }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-foreground">
                                {{ studentName(paper) }}
                            </td>
                            <td class="hidden px-4 py-3 xl:table-cell">
                                <div
                                    v-if="paperSdgs(paper).length"
                                    class="flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="sdg in paperSdgs(paper)"
                                        :key="sdg.id"
                                        variant="secondary"
                                        class="text-[10px]"
                                        :style="
                                            sdg.color
                                                ? {
                                                      backgroundColor:
                                                          sdg.color + '20',
                                                      color: sdg.color,
                                                      borderColor:
                                                          sdg.color + '40',
                                                  }
                                                : {}
                                        "
                                    >
                                        SDG {{ sdg.number }}
                                    </Badge>
                                </div>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >-</span
                                >
                            </td>
                            <td class="hidden px-4 py-3 xl:table-cell">
                                <div
                                    v-if="paperAgendas(paper).length"
                                    class="flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="agenda in paperAgendas(paper)"
                                        :key="agenda.id"
                                        variant="info"
                                        class="text-[10px]"
                                    >
                                        {{ agenda.name }}
                                    </Badge>
                                </div>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >-</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        getStepBadgeClass(paper.current_step),
                                    ]"
                                >
                                    {{
                                        paper.step_label ??
                                        stepLabel(paper.current_step)
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{ formatDate(paper.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="
                                        research.show.url({
                                            class: schoolClass?.id ?? '',
                                            paper: paper.id,
                                        })
                                    "
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

        <!-- Pagination -->
        <div
            v-if="papers.last_page > 1"
            class="flex items-center justify-end text-sm"
        >
            <div class="flex items-center gap-1">
                <template
                    v-for="(link, index) in papers.links"
                    :key="link.label"
                >
                    <button
                        v-if="index === 0"
                        :disabled="!link.url"
                        class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                        @click="link.url && router.get(link.url)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        v-else-if="index === papers.links.length - 1"
                        :disabled="!link.url"
                        class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                        @click="link.url && router.get(link.url)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                    <button
                        v-else
                        :disabled="!link.url"
                        :class="[
                            'min-w-[2rem] rounded-lg border px-2 py-1.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                            link.active
                                ? 'border-orange-500 bg-orange-500 text-white'
                                : 'border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        @click="link.url && router.get(link.url)"
                    >
                        {{ link.label }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
