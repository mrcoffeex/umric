<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Eye,
    FileSearch,
    Filter,
    Globe,
    GraduationCap,
    Loader2,
    ScrollText,
    Search,
    Target,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { getStepBadgeClass } from '@/lib/step-colors';
import admin from '@/routes/admin';

interface PaperRow {
    id: string;
    tracking_id: string;
    title: string;
    current_step: string;
    step_label?: string;
    created_at: string;
    sdg_ids?: string[] | null;
    agenda_ids?: string[] | null;
    user?: { name: string } | null;
    school_class?: { name: string } | null;
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

interface ClassItem {
    id: string;
    name: string;
    section: string | null;
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
    papers: Paginator<PaperRow>;
    stepCounts: Record<string, number>;
    filters: {
        step: string | null;
        search: string | null;
        sdg: string | null;
        agenda: string | null;
        class: string | null;
    };
    stepLabels: Record<string, string>;
    facultyUsers: Array<{ id: string; name: string }>;
    staffUsers: Array<{ id: string; name: string }>;
    sdgs: SdgItem[];
    agendas: AgendaItem[];
    classes: ClassItem[];
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

function paperSdgs(paper: PaperRow): SdgItem[] {
    return (paper.sdg_ids ?? [])
        .map((id) => sdgMap.value.get(id))
        .filter((s): s is SdgItem => !!s);
}

function paperAgendas(paper: PaperRow): AgendaItem[] {
    return (paper.agenda_ids ?? [])
        .map((id) => agendaMap.value.get(id))
        .filter((a): a is AgendaItem => !!a);
}

const search = ref(props.filters.search ?? '');
const selectedStep = ref(props.filters.step ?? '');
const selectedSdg = ref(props.filters.sdg ?? '');
const selectedAgenda = ref(props.filters.agenda ?? '');
const selectedClass = ref(props.filters.class ?? '');
const filtersOpen = ref(
    !!props.filters.sdg || !!props.filters.agenda || !!props.filters.class,
);
const mounted = ref(false);
const loading = ref(false);
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
            admin.research.index(),
            {
                search: search.value || undefined,
                step: selectedStep.value || undefined,
                sdg: selectedSdg.value || undefined,
                agenda: selectedAgenda.value || undefined,
                class: selectedClass.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 350);
}

watch(
    [search, selectedStep, selectedSdg, selectedAgenda, selectedClass],
    applyFilters,
);

const activeFilterCount = computed(() => {
    let count = 0;

    if (selectedSdg.value) {
        count++;
    }

    if (selectedAgenda.value) {
        count++;
    }

    if (selectedClass.value) {
        count++;
    }

    return count;
});

function clearAllFilters() {
    selectedSdg.value = '';
    selectedAgenda.value = '';
    selectedClass.value = '';
}

const totalPaperCount = computed(() => {
    return Object.values(props.stepCounts).reduce((sum, c) => sum + c, 0);
});

const completedCount = computed(() => props.stepCounts['completed'] ?? 0);

const stepTabs = computed(() => [
    { key: '', label: 'All', count: totalPaperCount.value },
    ...Object.entries(props.stepLabels).map(([key, label]) => ({
        key,
        label,
        count: Number(props.stepCounts[key] ?? 0),
    })),
]);

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Research', href: admin.research.index() },
        ],
    },
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">
                    Research Workflow
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Review and monitor all papers in the workflow pipeline.
                </p>
            </div>
            <div class="text-sm text-muted-foreground">
                {{ totalPaperCount }} papers
            </div>
        </div>

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
                        {{ totalPaperCount }}
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
                        {{ completedCount }}
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
        </div>

        <!-- Search + Filters -->
        <div class="rounded-2xl border border-border bg-card">
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
                        placeholder="Search by tracking ID, title, or student..."
                        class="w-full rounded-xl border border-input bg-background py-2.5 pr-3 pl-10 text-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>
            </div>

            <!-- Collapsible Advanced Filters -->
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
                        Advanced Filters
                        <span
                            v-if="activeFilterCount > 0"
                            class="rounded-full bg-orange-100 px-1.5 py-0.5 text-[10px] font-bold text-orange-600 dark:bg-orange-950/40 dark:text-orange-400"
                            >{{ activeFilterCount }}</span
                        >
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
                    <div v-if="classes.length > 0">
                        <p
                            class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <GraduationCap class="h-3.5 w-3.5" /> Class
                        </p>
                        <select
                            v-model="selectedClass"
                            class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                        >
                            <option value="">All Classes</option>
                            <option
                                v-for="cls in classes"
                                :key="cls.id"
                                :value="String(cls.id)"
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
                                    !selectedSdg
                                        ? 'border-teal-500 bg-teal-50 text-teal-700 dark:border-teal-700 dark:bg-teal-950/30 dark:text-teal-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-teal-300 hover:text-teal-600',
                                ]"
                                @click="selectedSdg = ''"
                            >
                                All
                            </button>
                            <button
                                v-for="sdg in sdgs"
                                :key="sdg.id"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                    selectedSdg === String(sdg.id)
                                        ? 'border-teal-500 bg-teal-50 text-teal-700 dark:border-teal-700 dark:bg-teal-950/30 dark:text-teal-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-teal-300 hover:text-teal-600',
                                ]"
                                :title="sdg.name"
                                @click="selectedSdg = String(sdg.id)"
                            >
                                <span
                                    class="inline-block h-2 w-2 rounded-full"
                                    :style="
                                        sdg.color
                                            ? { backgroundColor: sdg.color }
                                            : {}
                                    "
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
                                    !selectedAgenda
                                        ? 'border-violet-500 bg-violet-50 text-violet-700 dark:border-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-violet-300 hover:text-violet-600',
                                ]"
                                @click="selectedAgenda = ''"
                            >
                                All
                            </button>
                            <button
                                v-for="agenda in agendas"
                                :key="agenda.id"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold transition-colors',
                                    selectedAgenda === String(agenda.id)
                                        ? 'border-violet-500 bg-violet-50 text-violet-700 dark:border-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                        : 'border-border bg-card text-muted-foreground hover:border-violet-300 hover:text-violet-600',
                                ]"
                                @click="selectedAgenda = String(agenda.id)"
                            >
                                {{ agenda.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step Tabs -->
        <div class="flex flex-wrap gap-2">
            <button
                v-for="tab in stepTabs"
                :key="tab.key || 'all'"
                type="button"
                @click="selectedStep = tab.key"
                :class="[
                    'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
                    selectedStep === tab.key
                        ? 'border-orange-500 bg-orange-50 text-orange-700 dark:border-orange-700 dark:bg-orange-950/30 dark:text-orange-300'
                        : 'border-border bg-card text-muted-foreground hover:border-orange-300 hover:text-orange-600',
                ]"
            >
                {{ tab.label }}
                <span
                    class="rounded-full bg-black/5 px-2 py-0.5 text-[10px] dark:bg-white/10"
                    >{{ tab.count }}</span
                >
            </button>
        </div>

        <!-- Results count -->
        <div
            class="flex items-center justify-between text-xs text-muted-foreground"
        >
            <span v-if="!loading">
                <template v-if="papers.total === 0">No results</template>
                <template v-else>
                    Showing
                    <span class="font-semibold text-foreground"
                        >{{ papers.from }}–{{ papers.to }}</span
                    >
                    of
                    <span class="font-semibold text-foreground">{{
                        papers.total
                    }}</span>
                    {{ papers.total === 1 ? 'paper' : 'papers' }}
                </template>
            </span>
            <span v-else class="italic">Fetching results…</span>
        </div>

        <div
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
                    <FileSearch class="h-5 w-5 text-orange-400" />
                </div>
                <p class="font-semibold text-foreground">No papers found</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Adjust search or step filters.
                </p>
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted">
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Tracking ID
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Title
                        </th>
                        <th
                            class="hidden px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase lg:table-cell"
                        >
                            Student
                        </th>
                        <th
                            class="hidden px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase md:table-cell"
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
                            class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Current Step
                        </th>
                        <th
                            class="hidden px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase md:table-cell"
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
                        <td class="px-4 py-3 font-mono text-xs text-foreground">
                            {{ paper.tracking_id }}
                        </td>
                        <td class="max-w-xs px-4 py-3">
                            <p
                                class="line-clamp-1 font-semibold text-foreground"
                            >
                                {{ paper.title }}
                            </p>
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                        >
                            {{ paper.user?.name ?? '-' }}
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            <Badge
                                v-if="paper.school_class?.name"
                                variant="outline"
                                class="text-[11px]"
                            >
                                {{ paper.school_class.name }}
                            </Badge>
                            <span v-else class="text-xs text-muted-foreground"
                                >-</span
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
                                                  borderColor: sdg.color + '40',
                                              }
                                            : {}
                                    "
                                >
                                    SDG {{ sdg.number }}
                                </Badge>
                            </div>
                            <span v-else class="text-xs text-muted-foreground"
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
                            <span v-else class="text-xs text-muted-foreground"
                                >-</span
                            >
                        </td>
                        <td class="px-4 py-3 text-sm text-nowrap">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                                    getStepBadgeClass(paper.current_step),
                                ]"
                            >
                                {{
                                    paper.step_label ??
                                    stepLabel(paper.current_step)
                                }}
                            </span>
                        </td>
                        <td
                            class="hidden px-4 py-3 text-xs text-nowrap text-muted-foreground md:table-cell"
                        >
                            {{ formatDate(paper.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="
                                    admin.research.show.url({ paper: paper.id })
                                "
                                class="inline-flex items-center gap-1 rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-semibold text-foreground hover:bg-muted"
                            >
                                <Eye class="h-4 w-4" /> View
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

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
                        @click="link.url && router.get(link.url)"
                        class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <button
                        v-else-if="index === papers.links.length - 1"
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                        class="rounded-lg border border-border bg-card p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                    <button
                        v-else
                        :disabled="!link.url"
                        @click="link.url && router.get(link.url)"
                        :class="[
                            'min-w-[2rem] rounded-lg border px-2 py-1.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                            link.active
                                ? 'border-orange-500 bg-orange-500 text-white'
                                : 'border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        {{ link.label }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
