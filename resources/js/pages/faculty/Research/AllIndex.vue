<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import {
    ChevronDown,
    FileSearch,
    Filter,
    Globe,
    GraduationCap,
    Loader2,
    ScrollText,
    Search,
    Target,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { getStepBadgeClass } from '@/lib/step-colors';
import { index as classesIndex } from '@/routes/faculty/classes';
import {
    index as researchIndex,
    show as researchShow,
} from '@/routes/faculty/research';

interface SchoolClassInfo {
    id: string;
    name: string;
    section: string | null;
    class_code: string | null;
}

interface SdgItem {
    id: string;
    number: number;
    name: string;
    code: string;
    color: string;
}

interface AgendaItem {
    id: string;
    name: string;
    code: string;
}

interface Paper {
    id: string;
    title: string;
    tracking_id: string;
    current_step: string;
    step_label?: string;
    grade?: string | null;
    sdg_ids?: string[];
    agenda_ids?: string[];
    student?: { id: string; name: string } | null;
    school_class?: SchoolClassInfo | null;
}

interface Props {
    papers: Paper[];
    classes: SchoolClassInfo[];
    sdgs: SdgItem[];
    agendas: AgendaItem[];
    stepCounts: Record<string, number>;
    stepLabels: Record<string, string>;
}

const props = defineProps<Props>();

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
const searchQuery = ref('');
const debouncedSearch = ref('');
const loading = ref(false);

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

watch(searchQuery, () => {
    loading.value = true;
});
watchDebounced(
    searchQuery,
    (val) => {
        debouncedSearch.value = val;
        loading.value = false;
    },
    { debounce: 300 },
);
const filtersOpen = ref(false);
const activeStep = ref<string>('all');
const activeClass = ref<number | 'all'>('all');
const activeSdg = ref<string>('all');
const activeAgenda = ref<string>('all');

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

const baseFilteredPapers = computed(() => {
    let result = props.papers as Paper[];

    const q = debouncedSearch.value.trim().toLowerCase();

    if (q) {
        result = result.filter(
            (p) =>
                p.title.toLowerCase().includes(q) ||
                p.tracking_id.toLowerCase().includes(q) ||
                (p.student?.name ?? '').toLowerCase().includes(q),
        );
    }

    if (activeClass.value !== 'all') {
        result = result.filter((p) => p.school_class?.id === activeClass.value);
    }

    if (activeSdg.value !== 'all') {
        result = result.filter((p) =>
            (p.sdg_ids ?? []).includes(activeSdg.value),
        );
    }

    if (activeAgenda.value !== 'all') {
        result = result.filter((p) =>
            (p.agenda_ids ?? []).includes(activeAgenda.value),
        );
    }

    return result;
});

const stepPills = computed(() => {
    return [
        { key: 'all', label: 'All', count: baseFilteredPapers.value.length },
        ...orderedSteps.map((step) => ({
            key: step,
            label: props.stepLabels[step] ?? step,
            count: baseFilteredPapers.value.filter(
                (p) => p.current_step === step,
            ).length,
        })),
    ];
});

const filteredPapers = computed(() => {
    if (activeStep.value === 'all') {
        return baseFilteredPapers.value;
    }

    return baseFilteredPapers.value.filter(
        (p) => p.current_step === activeStep.value,
    );
});

const activeFilterCount = computed(() => {
    let count = 0;

    if (searchQuery.value.trim()) {
        count++;
    }

    if (activeClass.value !== 'all') {
        count++;
    }

    if (activeSdg.value !== 'all') {
        count++;
    }

    if (activeAgenda.value !== 'all') {
        count++;
    }

    if (activeStep.value !== 'all') {
        count++;
    }

    return count;
});

function clearAllFilters() {
    searchQuery.value = '';
    activeClass.value = 'all';
    activeSdg.value = 'all';
    activeAgenda.value = 'all';
    activeStep.value = 'all';
}

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My Classes', href: classesIndex() },
            { title: 'My Research', href: researchIndex() },
        ],
    },
});
</script>

<template>
    <Head title="My Research" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Hero Header -->
        <section
            class="overflow-hidden rounded-2xl border border-border bg-card"
        >
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="p-5">
                <h1 class="text-2xl font-bold text-foreground">My Research</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    All research papers across your classes.
                </p>
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
                        Total Papers
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ papers.length }}
                    </p>
                </div>
            </div>
            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-950/30"
                >
                    <GraduationCap class="h-5 w-5 text-teal-500" />
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
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950/30"
                >
                    <ScrollText class="h-5 w-5 text-green-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Completed
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ stepCounts['completed'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Search + Filters -->
        <section class="rounded-2xl border border-border bg-card">
            <!-- Search bar -->
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
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by title, tracking ID, or student name..."
                        class="w-full rounded-xl border border-input bg-background py-2.5 pr-3 pl-10 text-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>
            </div>

            <!-- Collapsible filter toggle -->
            <div class="border-t border-border">
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-4 py-3 text-left"
                    @click="filtersOpen = !filtersOpen"
                >
                    <div
                        class="flex items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <Filter class="h-4 w-4" />
                        Filters
                        <span
                            v-if="activeFilterCount > 0"
                            class="rounded-full bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-600 dark:bg-orange-950/40 dark:text-orange-400"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="activeFilterCount > 0"
                            type="button"
                            class="text-xs font-semibold text-orange-500 hover:underline"
                            @click.stop="clearAllFilters"
                        >
                            Clear all
                        </button>
                        <ChevronDown
                            :class="[
                                'h-4 w-4 text-muted-foreground transition-transform duration-200',
                                filtersOpen ? 'rotate-180' : '',
                            ]"
                        />
                    </div>
                </button>

                <div v-show="filtersOpen" class="space-y-4 px-4 pb-4">
                    <!-- Class filter -->
                    <div v-if="classes.length > 1">
                        <p
                            class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <GraduationCap class="h-3.5 w-3.5" /> Class
                        </p>
                        <select
                            :value="activeClass"
                            class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                            @change="
                                activeClass =
                                    ($event.target as HTMLSelectElement)
                                        .value === 'all'
                                        ? 'all'
                                        : Number(
                                              (
                                                  $event.target as HTMLSelectElement
                                              ).value,
                                          )
                            "
                        >
                            <option value="all">All Classes</option>
                            <option
                                v-for="cls in classes"
                                :key="cls.id"
                                :value="cls.id"
                            >
                                {{ cls.name
                                }}{{ cls.section ? ` · ${cls.section}` : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- SDG filter -->
                    <div v-if="sdgs.length > 0">
                        <p
                            class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <Globe class="h-3.5 w-3.5" /> SDG
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                    activeSdg === 'all'
                                        ? 'border-teal-500 bg-teal-50 text-teal-700 dark:border-teal-700 dark:bg-teal-950/30 dark:text-teal-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-teal-300 hover:text-teal-600',
                                ]"
                                @click="activeSdg = 'all'"
                            >
                                All
                            </button>
                            <button
                                v-for="sdg in sdgs"
                                :key="sdg.id"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                    activeSdg === sdg.id
                                        ? 'border-teal-500 bg-teal-50 text-teal-700 dark:border-teal-700 dark:bg-teal-950/30 dark:text-teal-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-teal-300 hover:text-teal-600',
                                ]"
                                :title="sdg.name"
                                @click="activeSdg = sdg.id"
                            >
                                <span
                                    class="inline-block h-2 w-2 rounded-full"
                                    :style="{ backgroundColor: sdg.color }"
                                />
                                SDG {{ sdg.number }}
                            </button>
                        </div>
                    </div>

                    <!-- Agenda filter -->
                    <div v-if="agendas.length > 0">
                        <p
                            class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <Target class="h-3.5 w-3.5" /> Agenda
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                    activeAgenda === 'all'
                                        ? 'border-violet-500 bg-violet-50 text-violet-700 dark:border-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-violet-300 hover:text-violet-600',
                                ]"
                                @click="activeAgenda = 'all'"
                            >
                                All
                            </button>
                            <button
                                v-for="agenda in agendas"
                                :key="agenda.id"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                    activeAgenda === agenda.id
                                        ? 'border-violet-500 bg-violet-50 text-violet-700 dark:border-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-violet-300 hover:text-violet-600',
                                ]"
                                :title="agenda.name"
                                @click="activeAgenda = agenda.id"
                            >
                                {{ agenda.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Step filter -->
                    <div>
                        <p
                            class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <Filter class="h-3.5 w-3.5" /> Step
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="pill in stepPills"
                                :key="pill.key"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                    activeStep === pill.key
                                        ? 'border-orange-500 bg-orange-50 text-orange-700 dark:border-orange-700 dark:bg-orange-950/30 dark:text-orange-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-orange-300 hover:text-orange-600',
                                ]"
                                @click="activeStep = pill.key"
                            >
                                {{ pill.label }}
                                <span
                                    class="rounded-full bg-black/5 px-1.5 py-0.5 text-[10px] dark:bg-white/10"
                                    >{{ pill.count }}</span
                                >
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Results count -->
        <div
            class="flex items-center justify-between text-xs text-muted-foreground"
        >
            <span v-if="loading" class="italic">Fetching results…</span>
            <span v-else-if="filteredPapers.length === 0">No results</span>
            <span v-else>
                Showing
                <span class="font-semibold text-foreground">{{
                    filteredPapers.length
                }}</span>
                <template v-if="filteredPapers.length !== papers.length">
                    of
                    <span class="font-semibold text-foreground">{{
                        papers.length
                    }}</span>
                </template>
                {{ filteredPapers.length === 1 ? 'paper' : 'papers' }}
            </span>
        </div>

        <!-- Papers Table -->
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
            <div v-if="filteredPapers.length === 0" class="p-12 text-center">
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                >
                    <FileSearch class="h-6 w-6 text-orange-500" />
                </div>
                <h2 class="text-base font-bold text-foreground">
                    No papers found
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    No research papers match the selected filters.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
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
                                class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Class
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
                            <th class="w-24 px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="paper in filteredPapers"
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
                                {{ paper.student?.name ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="paper.school_class"
                                    class="inline-flex rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-950/30 dark:text-teal-300"
                                >
                                    {{ paper.school_class.name
                                    }}<span v-if="paper.school_class.section">
                                        · {{ paper.school_class.section }}</span
                                    >
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
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
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="
                                        researchShow.url({ paper: paper.id })
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
    </div>
</template>
