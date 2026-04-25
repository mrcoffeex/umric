<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ClipboardList,
    ExternalLink,
    Filter,
    Search,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import { show as adminResearchShow } from '@/routes/admin/research';
import faculty from '@/routes/faculty';
import { show as facultyResearchShow } from '@/routes/faculty/research';

type LineItem = {
    criterion_id: string;
    name: string;
    content?: string | null;
    max_points: number;
    score: number;
};

type MyEvaluation = {
    id: string;
    line_items: LineItem[];
    final_score: number;
    comments?: string | null;
};

type AdminEvalRow = {
    id: string;
    evaluator_id: string;
    evaluator_name: string;
    line_items: LineItem[];
    final_score: number;
    comments?: string | null;
    is_mine: boolean;
};

type DefenseRow = {
    id: string;
    schedule: string | null;
    defense_type: string;
    defense_type_label: string;
    research_paper_id: string | null;
    paper_title: string | null;
    tracking_id: string | null;
    student_name: string | null;
    /** Names from the panel defense (ordered as stored) */
    panel_members: string[];
    is_on_panel: boolean;
    evaluation_format: {
        id: string;
        name: string;
        evaluation_type?: string;
    } | null;
    evaluation_format_ready: boolean;
    can_evaluate: boolean;
    my_evaluation: MyEvaluation | null;
    evaluations: AdminEvalRow[];
    /** Admin: mean of all panelist totals; null if none submitted */
    average_score?: number | null;
};

type FilterProps = {
    q: string;
    defense_type: string;
    status: string;
    /** Y-m-d or empty */
    schedule_date: string;
    per_page: number;
    status_options: Record<string, string>;
};

type DefenseTypeOption = { value: string; label: string };

type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{
    defenses: Paginator<DefenseRow>;
    /** At least one rubric has criteria totaling 100 (new schedules can use it) */
    anyEvaluationFormatReady: boolean;
    context: 'admin' | 'faculty';
    defenseTypeOptions: DefenseTypeOption[];
    filters: FilterProps;
}>();

const searchInput = ref(props.filters.q ?? '');
const defenseType = ref(props.filters.defense_type ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const scheduleDate = ref(props.filters.schedule_date ?? '');
const perPage = ref(String(props.filters.per_page ?? 15));

watch(
    () => props.filters.q,
    (v) => {
        searchInput.value = v ?? '';
    },
);

watch(
    () => props.filters.schedule_date,
    (v) => {
        scheduleDate.value = v ?? '';
    },
);

const page = usePage();
const authUserNameNorm = computed((): string => {
    const u = page.props.auth as { user?: { name?: string } } | undefined;
    const n = u?.user?.name;

    return typeof n === 'string' && n.trim() !== ''
        ? n.trim().toLowerCase()
        : '';
});

const rows = computed(() => props.defenses.data ?? []);
const isAdmin = computed(() => props.context === 'admin');
const listQuery = (): Record<string, string | number> =>
    buildQuery() as Record<string, string | number>;

function listUrl(): string {
    return props.context === 'admin'
        ? admin.evaluation.index.url()
        : faculty.evaluation.index.url();
}

function buildQuery(
    overrides: Record<string, string | number> = {},
): Record<string, string | number> {
    const q: Record<string, string | number> = {
        per_page: Number(perPage.value) || 15,
        status: statusFilter.value,
    };

    if (defenseType.value) {
        q.defense_type = defenseType.value;
    }

    if (searchInput.value.trim()) {
        q.q = searchInput.value.trim();
    }

    if (scheduleDate.value) {
        q.schedule_date = scheduleDate.value;
    }

    return { ...q, ...overrides };
}

let searchDebounce: ReturnType<typeof setTimeout> | null = null;
function runListFetch(
    options: { resetPage: boolean; debounceSearch: boolean } = {
        resetPage: true,
        debounceSearch: false,
    },
) {
    const go = () => {
        const query = buildQuery(options.resetPage ? { page: 1 } : {});
        router.get(listUrl(), query, {
            preserveState: true,
            replace: true,
            only: ['defenses', 'filters', 'anyEvaluationFormatReady'],
        });
    };

    if (options.debounceSearch) {
        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }

        searchDebounce = setTimeout(go, 350);
    } else {
        go();
    }
}

function onSearchInput() {
    runListFetch({ resetPage: true, debounceSearch: true });
}

function onFilterChange() {
    runListFetch({ resetPage: true, debounceSearch: false });
}

function clearFilters() {
    searchInput.value = '';
    defenseType.value = '';
    statusFilter.value = 'all';
    scheduleDate.value = '';
    perPage.value = '15';
    runListFetch({ resetPage: true, debounceSearch: false });
}

function setScheduleToday() {
    const t = new Date();
    const y = t.getFullYear();
    const m = String(t.getMonth() + 1).padStart(2, '0');
    const d = String(t.getDate()).padStart(2, '0');
    scheduleDate.value = `${y}-${m}-${d}`;
    onFilterChange();
}

function evaluatePageUrl(d: DefenseRow) {
    const q = listQuery();

    return isAdmin.value
        ? admin.evaluation.evaluate.url(
              { panelDefense: d.id },
              { query: q as never },
          )
        : faculty.evaluation.evaluate.url(
              { panelDefense: d.id },
              { query: q as never },
          );
}

function researchShowUrl(paperId: string) {
    return isAdmin.value
        ? adminResearchShow.url({ paper: paperId })
        : facultyResearchShow.url({ paper: paperId });
}

function adminEditEvalUrl(evaluationId: string) {
    return admin.evaluation.edit.url(
        { panelDefenseEvaluation: evaluationId },
        { query: listQuery() as never },
    );
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Defense evaluation' }],
    },
});

function formatWhen(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

/** Tailwind classes applied on top of `Badge variant="outline"` for defense type */
function defenseTypeBadgeClass(defenseType: string): string {
    switch (defenseType) {
        case 'title':
            return 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-800 dark:bg-violet-950/35 dark:text-violet-300';
        case 'outline':
            return 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-800 dark:bg-indigo-950/35 dark:text-indigo-300';
        case 'final':
            return 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-800 dark:bg-orange-950/35 dark:text-orange-300';
        default:
            return 'bg-muted/80 text-foreground';
    }
}

type ScoreVariant = 'secondary' | 'outline' | 'success' | 'warning' | 'info';

function formatEvalDisplay(
    d: DefenseRow,
    score: number,
    lineItems?: { length: number } | null,
): string {
    if (
        d.evaluation_format?.evaluation_type === 'checklist' &&
        lineItems &&
        lineItems.length > 0
    ) {
        return `${score} / ${lineItems.length} yes`;
    }

    return `${score} / 100`;
}

function scoreBadgeVariant(score: number): ScoreVariant {
    if (score >= 85) {
        return 'success';
    }

    if (score >= 70) {
        return 'info';
    }

    if (score >= 50) {
        return 'secondary';
    }

    if (score >= 30) {
        return 'warning';
    }

    return 'outline';
}

/** Rotating palette so each panelist is visually distinct (outline + fill). */
const PANELIST_BADGE_CLASSES: readonly string[] = [
    'border-indigo-200 bg-indigo-50/90 text-indigo-900 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200',
    'border-violet-200 bg-violet-50/90 text-violet-900 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-200',
    'border-orange-200 bg-orange-50/90 text-orange-900 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-200',
    'border-emerald-200 bg-emerald-50/90 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200',
    'border-rose-200 bg-rose-50/90 text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200',
    'border-cyan-200 bg-cyan-50/90 text-cyan-900 dark:border-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-200',
    'border-amber-200 bg-amber-50/90 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
];

function panelistBadgeClass(index: number): string {
    return (
        PANELIST_BADGE_CLASSES[index % PANELIST_BADGE_CLASSES.length] ??
        PANELIST_BADGE_CLASSES[0]
    );
}

function isCurrentUserPanelistName(name: string): boolean {
    const a = authUserNameNorm.value;

    if (a === '') {
        return false;
    }

    return a === name.trim().toLowerCase();
}

/** Format mean total (0–100); trim trailing .0 */
function formatAverageScore(n: number): string {
    if (Number.isInteger(n)) {
        return String(n);
    }

    return n.toFixed(1);
}
</script>

<template>
    <div>
        <Head title="Defense Evaluations" />

        <div class="mx-auto space-y-6 p-4 md:p-6">
            <div
                v-if="isAdmin && !anyEvaluationFormatReady"
                class="flex gap-3 rounded-2xl border border-amber-500/30 bg-amber-50/80 p-4 text-sm text-amber-950 dark:border-amber-500/20 dark:bg-amber-950/30 dark:text-amber-100"
            >
                <AlertTriangle
                    class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                />
                <div>
                    <p class="font-semibold">
                        No evaluation format is ready to use yet.
                    </p>
                    <p class="mt-1 text-amber-900/80 dark:text-amber-200/80">
                        Create a format and set criteria so weights add up to
                        100. Panel defense schedules can only use a “ready”
                        rubric.
                    </p>
                    <Button
                        as-child
                        class="mt-3 bg-orange-500 text-white hover:bg-orange-600"
                        size="sm"
                    >
                        <Link :href="admin.evaluationFormats.index.url()">
                            Open evaluation formats
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-orange-200/70 bg-orange-50/50 text-orange-600 dark:border-orange-800/50 dark:bg-orange-950/30 dark:text-orange-400"
                    >
                        <ClipboardList class="h-5 w-5" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold tracking-tight text-foreground capitalize md:text-2xl"
                        >
                            Defense evaluation
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            <template v-if="context === 'admin'">
                                Search scheduled defenses. Each row shows which
                                rubric applies. You can open a full-page form to
                                score or edit a panelist file.
                            </template>
                            <template v-else>
                                Your panel. Submissions are final and are kept
                                as a snapshot of the criteria in effect at that
                                time.
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="space-y-4 rounded-2xl border border-border bg-card p-4 shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center gap-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <Filter class="h-3.5 w-3.5" />
                    Filters
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <Label class="text-muted-foreground" for="eval-q"
                            >Search</Label
                        >
                        <div class="relative mt-1.5">
                            <Search
                                class="absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="eval-q"
                                v-model="searchInput"
                                type="search"
                                placeholder="Title, tracking ID, or student name…"
                                class="h-9 bg-background pl-9"
                                @input="onSearchInput"
                            />
                        </div>
                    </div>
                    <div>
                        <Label class="text-muted-foreground" for="eval-date"
                            >Schedule date</Label
                        >
                        <div class="mt-1.5 flex gap-2">
                            <Input
                                id="eval-date"
                                v-model="scheduleDate"
                                type="date"
                                class="h-9 min-w-0 flex-1 bg-background"
                                @change="onFilterChange"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                class="h-9 shrink-0 border-teal-500/50 bg-teal-50/90 px-3 text-teal-900 hover:bg-teal-100 dark:border-teal-600 dark:bg-teal-950/50 dark:text-teal-200 dark:hover:bg-teal-900/40"
                                @click="setScheduleToday"
                            >
                                Today
                            </Button>
                        </div>
                    </div>
                    <div>
                        <Label class="text-muted-foreground" for="eval-type"
                            >Defense type</Label
                        >
                        <FormSelect
                            id="eval-type"
                            v-model="defenseType"
                            class="mt-1.5 h-9 py-0"
                            @update:model-value="onFilterChange"
                        >
                            <option value="">All types</option>
                            <option
                                v-for="opt in defenseTypeOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </FormSelect>
                    </div>
                    <div>
                        <Label class="text-muted-foreground" for="eval-st"
                            >Status</Label
                        >
                        <FormSelect
                            id="eval-st"
                            v-model="statusFilter"
                            class="mt-1.5 h-9 py-0"
                            @update:model-value="onFilterChange"
                        >
                            <option
                                v-for="(label, val) in filters.status_options"
                                :key="val"
                                :value="val"
                            >
                                {{ label }}
                            </option>
                        </FormSelect>
                    </div>
                    <div>
                        <Label class="text-muted-foreground" for="eval-pp"
                            >Per page</Label
                        >
                        <FormSelect
                            id="eval-pp"
                            v-model="perPage"
                            class="mt-1.5 h-9 py-0"
                            @update:model-value="onFilterChange"
                        >
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </FormSelect>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs text-muted-foreground">
                        <template v-if="defenses.total !== undefined">
                            {{ defenses.from ?? 0 }}–{{ defenses.to ?? 0 }} of
                            {{ defenses.total }} defense(s)
                        </template>
                    </p>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                    >
                        Clear filters
                    </Button>
                </div>
            </div>

            <div
                v-if="rows.length === 0"
                class="rounded-2xl border border-dashed border-border bg-muted/20 p-10 text-center text-sm text-muted-foreground"
            >
                <template v-if="context === 'faculty'">
                    No matching defenses. Try adjusting search or filters.
                </template>
                <template v-else> No matching defenses. </template>
            </div>

            <div
                v-else
                class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm"
            >
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead
                        class="border-b border-border bg-muted/40 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Schedule</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Rubric</th>
                            <th class="px-4 py-3">Research</th>
                            <th class="px-4 py-3">Student</th>
                            <th v-if="context === 'admin'" class="px-4 py-3">
                                Panel
                            </th>
                            <th v-if="isAdmin" class="px-4 py-3">
                                Scores (avg.)
                            </th>
                            <th v-else class="px-4 py-3">Your total</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="d in rows"
                            :key="d.id"
                            class="align-top transition-colors hover:bg-muted/30"
                        >
                            <td
                                class="px-4 py-3 font-medium whitespace-nowrap text-foreground"
                            >
                                <Badge
                                    variant="secondary"
                                    class="font-medium tabular-nums"
                                >
                                    {{ formatWhen(d.schedule) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    variant="outline"
                                    :class="
                                        defenseTypeBadgeClass(d.defense_type)
                                    "
                                >
                                    {{ d.defense_type_label }}
                                </Badge>
                            </td>
                            <td class="max-w-[200px] px-4 py-3 align-top">
                                <p
                                    v-if="d.evaluation_format"
                                    class="text-xs font-medium text-foreground"
                                >
                                    {{ d.evaluation_format.name }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">
                                    —
                                </p>
                                <p
                                    v-if="d.evaluation_format?.evaluation_type"
                                    class="mt-0.5 text-[10px] text-muted-foreground"
                                >
                                    {{
                                        d.evaluation_format.evaluation_type ===
                                        'checklist'
                                            ? 'Checklist (yes / no)'
                                            : 'Scoring (100%)'
                                    }}
                                </p>
                                <p
                                    v-if="
                                        d.evaluation_format &&
                                        !d.evaluation_format_ready
                                    "
                                    class="mt-1 text-[10px] text-amber-800 dark:text-amber-300/90"
                                >
                                    {{
                                        d.evaluation_format.evaluation_type ===
                                        'checklist'
                                            ? 'Rubric not ready (add items)'
                                            : 'Rubric not ready (weights ≠ 100)'
                                    }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="max-w-xs font-medium text-foreground"
                                >
                                    {{ d.paper_title ?? '—' }}
                                </div>
                                <div
                                    class="mt-1.5 flex flex-wrap items-center gap-2"
                                >
                                    <Badge
                                        v-if="d.tracking_id"
                                        variant="outline"
                                        class="w-fit max-w-full truncate font-mono text-[10px] text-muted-foreground"
                                    >
                                        {{ d.tracking_id }}
                                    </Badge>
                                    <a
                                        v-if="d.research_paper_id"
                                        :href="
                                            researchShowUrl(d.research_paper_id)
                                        "
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 hover:text-teal-800 hover:underline dark:text-teal-400 dark:hover:text-teal-300"
                                    >
                                        Open
                                        <ExternalLink
                                            class="h-3.5 w-3.5 shrink-0 opacity-80"
                                        />
                                    </a>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                <Badge
                                    v-if="d.student_name"
                                    variant="secondary"
                                    class="max-w-[200px] truncate text-xs font-medium text-foreground"
                                >
                                    {{ d.student_name }}
                                </Badge>
                                <span v-else class="text-muted-foreground/80"
                                    >—</span
                                >
                            </td>
                            <td
                                v-if="context === 'admin'"
                                class="max-w-[260px] px-4 py-3 align-top"
                            >
                                <div
                                    v-if="
                                        d.panel_members &&
                                        d.panel_members.length
                                    "
                                    class="flex flex-wrap content-start gap-1.5"
                                >
                                    <Badge
                                        v-for="(name, idx) in d.panel_members"
                                        :key="idx"
                                        :variant="
                                            isCurrentUserPanelistName(name)
                                                ? 'success'
                                                : 'outline'
                                        "
                                        :class="[
                                            'max-w-full justify-start text-left text-xs font-medium',
                                            isCurrentUserPanelistName(name)
                                                ? 'shadow-xs'
                                                : panelistBadgeClass(idx),
                                        ]"
                                    >
                                        <span
                                            class="line-clamp-2 break-words"
                                            >{{ name }}</span
                                        >
                                    </Badge>
                                </div>
                                <p v-else class="text-xs text-muted-foreground">
                                    No panelists listed
                                </p>
                            </td>
                            <td
                                v-if="isAdmin"
                                class="max-w-xs px-4 py-3 text-xs"
                            >
                                <template v-if="d.evaluations.length">
                                    <div class="flex flex-col gap-2">
                                        <div
                                            v-for="ev in d.evaluations"
                                            :key="ev.id"
                                            class="flex flex-col gap-1"
                                        >
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <span
                                                    class="line-clamp-1 min-w-0 text-muted-foreground"
                                                    >{{
                                                        ev.evaluator_name
                                                    }}</span
                                                >
                                                <Badge
                                                    v-if="ev.is_mine"
                                                    variant="secondary"
                                                    class="shrink-0 text-[9px] uppercase"
                                                    >You</Badge
                                                >
                                                <Badge
                                                    :variant="
                                                        scoreBadgeVariant(
                                                            d.evaluation_format
                                                                ?.evaluation_type ===
                                                                'checklist' &&
                                                                ev.line_items
                                                                    .length
                                                                ? (ev.final_score /
                                                                      ev
                                                                          .line_items
                                                                          .length) *
                                                                      100
                                                                : ev.final_score,
                                                        )
                                                    "
                                                    class="shrink-0 font-semibold tabular-nums"
                                                >
                                                    {{
                                                        formatEvalDisplay(
                                                            d,
                                                            ev.final_score,
                                                            ev.line_items,
                                                        )
                                                    }}
                                                </Badge>
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    class="h-7 text-[11px]"
                                                    as-child
                                                >
                                                    <Link
                                                        :href="
                                                            adminEditEvalUrl(
                                                                ev.id,
                                                            )
                                                        "
                                                        >Edit</Link
                                                    >
                                                </Button>
                                            </div>
                                            <p
                                                v-if="ev.comments"
                                                class="line-clamp-2 max-w-full text-[11px] leading-snug break-words text-muted-foreground"
                                            >
                                                {{ ev.comments }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-if="d.average_score != null"
                                        class="mt-2 flex flex-wrap items-center gap-2 border-t border-border/60 pt-2"
                                    >
                                        <span
                                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                            >Average</span
                                        >
                                        <Badge
                                            v-if="
                                                d.evaluation_format
                                                    ?.evaluation_type ===
                                                'checklist'
                                            "
                                            variant="outline"
                                            class="shrink-0 border-teal-300 bg-teal-100/95 font-semibold text-teal-950 tabular-nums dark:border-teal-600 dark:bg-teal-950/60 dark:text-teal-100"
                                        >
                                            {{
                                                formatAverageScore(
                                                    d.average_score,
                                                )
                                            }}
                                            <span
                                                class="ml-0.5 text-[9px] font-normal opacity-80"
                                                >yes avg</span
                                            >
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="outline"
                                            class="shrink-0 border-teal-300 bg-teal-100/95 font-semibold text-teal-950 tabular-nums dark:border-teal-600 dark:bg-teal-950/60 dark:text-teal-100"
                                        >
                                            {{
                                                formatAverageScore(
                                                    d.average_score,
                                                )
                                            }}/100
                                        </Badge>
                                    </div>
                                </template>
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="text-[11px] text-muted-foreground"
                                    >No scores yet</Badge
                                >
                            </td>
                            <td v-else class="px-4 py-3 text-muted-foreground">
                                <Badge
                                    v-if="d.my_evaluation"
                                    :variant="
                                        scoreBadgeVariant(
                                            d.evaluation_format
                                                ?.evaluation_type ===
                                                'checklist' &&
                                                d.my_evaluation.line_items
                                                    .length
                                                ? (d.my_evaluation.final_score /
                                                      d.my_evaluation.line_items
                                                          .length) *
                                                      100
                                                : d.my_evaluation.final_score,
                                        )
                                    "
                                    class="font-semibold tabular-nums"
                                >
                                    {{
                                        formatEvalDisplay(
                                            d,
                                            d.my_evaluation.final_score,
                                            d.my_evaluation.line_items,
                                        )
                                    }}
                                </Badge>
                                <span
                                    v-else
                                    class="text-sm text-muted-foreground/80"
                                    >—</span
                                >
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex flex-col items-end gap-1">
                                    <Button
                                        v-if="d.can_evaluate"
                                        as-child
                                        class="bg-orange-500 text-white hover:bg-orange-600"
                                    >
                                        <Link :href="evaluatePageUrl(d)">
                                            Open evaluation
                                        </Link>
                                    </Button>
                                    <Button
                                        v-else-if="d.my_evaluation"
                                        variant="outline"
                                        as-child
                                        class="border-teal-500/50 bg-background text-teal-800 hover:bg-teal-50 dark:border-teal-600 dark:bg-background dark:text-teal-200 dark:hover:bg-teal-950/40"
                                    >
                                        <Link :href="evaluatePageUrl(d)">
                                            View your scores
                                        </Link>
                                    </Button>
                                    <Badge
                                        v-else-if="
                                            !d.can_evaluate && !d.my_evaluation
                                        "
                                        variant="outline"
                                        class="text-[10px] text-muted-foreground"
                                    >
                                        Not on panel
                                    </Badge>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="defenses.links && defenses.links.length > 3"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <template
                    v-for="(link, index) in defenses.links"
                    :key="`${link.label}-${index}`"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            'inline-flex min-h-9 min-w-9 items-center justify-center rounded-md border px-2.5 text-sm transition-colors',
                            link.active
                                ? 'border-orange-400 bg-orange-50/90 font-semibold text-orange-950 dark:border-orange-600 dark:bg-orange-950/50 dark:text-orange-100'
                                : 'border-border bg-card hover:bg-muted/60',
                        ]"
                        preserve-scroll
                        preserve-state
                    >
                        <span v-if="index === 0" class="px-0.5">&lsaquo;</span>
                        <span
                            v-else-if="index === defenses.links.length - 1"
                            class="px-0.5"
                            >&rsaquo;</span
                        >
                        <span v-else v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        :class="[
                            'inline-flex min-h-9 min-w-9 items-center justify-center rounded-md border px-2.5 text-sm opacity-50',
                            link.active ? 'border-orange-400' : 'border-border',
                        ]"
                    >
                        <span v-if="index === 0">&lsaquo;</span>
                        <span v-else-if="index === defenses.links.length - 1"
                            >&rsaquo;</span
                        >
                        <span v-else v-html="link.label" />
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>
