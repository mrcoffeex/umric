<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardList,
    FileDown,
} from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';
import faculty from '@/routes/faculty';

type Criterion = {
    id: string;
    name: string;
    content?: string | null;
    section_heading?: string | null;
    max_points: number;
    sort_order: number;
};

type LineItem = {
    criterion_id: string;
    name: string;
    content?: string | null;
    max_points: number;
    score: number;
    section_heading?: string | null;
};

type DefenseInfo = {
    id: string;
    schedule: string | null;
    defense_type: string;
    defense_type_label: string;
    paper_title: string | null;
    tracking_id: string | null;
    student_name: string | null;
    evaluation_format: {
        id: string;
        name: string;
        evaluation_type?: 'scoring' | 'checklist';
        use_weights?: boolean;
    } | null;
};

type EvalOut = {
    id: string;
    line_items: LineItem[];
    final_score: number;
    /** Present for submitted evaluations; legacy rows may be null. */
    comments?: string | null;
    /** Panelist’s SDG picks for title evaluations (separate from paper `sdg_ids`). */
    sdg_ids: string[];
    is_mine: boolean;
    evaluator_name: string | null;
};

type SdgItem = { id: string; name: string; number?: number | null };

const props = defineProps<{
    context: 'admin' | 'faculty';
    mode: 'create' | 'view' | 'admin_edit';
    defense: DefenseInfo;
    listFilters: Record<string, string | number | undefined>;
    criteria: Criterion[];
    criteriaReady: boolean;
    criteriaTotalMax: number;
    /** Rubric type for this panel defense. */
    evaluation_type: 'scoring' | 'checklist';
    /** When true, `max_points` on each row is a % weight (totals 100). */
    scoring_uses_weights?: boolean;
    evaluation: EvalOut | null;
    initialScores: Record<string, number>;
    /** Required when submitting; prefilled on admin edit. */
    initialComment: string;
    /** PDF download (view mode) when format allows and user may download. */
    canDownloadEvaluationPdf?: boolean;
    /** Active SDGs (for title evaluation checklist). */
    sdgs: SdgItem[];
    titleSdgChecklist: boolean;
    initialSdgIds: string[];
}>();

const { confirm } = useConfirm();

const isAdmin = computed(() => props.context === 'admin');
const isReadonly = computed(() => props.mode === 'view');
const isEdit = computed(
    () => props.mode === 'create' || props.mode === 'admin_edit',
);
const isChecklist = computed(() => props.evaluation_type === 'checklist');
const scoringUseWeights = computed(
    () => !isChecklist.value && (props.scoring_uses_weights ?? false),
);
const isTitleSdg = computed(
    () => props.titleSdgChecklist && props.sdgs.length > 0,
);
const sdgOptions = computed(() =>
    props.sdgs.map((s) => ({
        value: s.id,
        label: s.number ? `SDG ${s.number}: ${s.name}` : s.name,
    })),
);

const listUrl = computed(() =>
    isAdmin.value
        ? admin.evaluation.index.url({ query: props.listFilters as never })
        : faculty.evaluation.index.url({ query: props.listFilters as never }),
);

const evaluationPdfUrl = computed(() => {
    if (!props.evaluation?.id || !props.canDownloadEvaluationPdf) {
        return null;
    }

    return isAdmin.value
        ? admin.evaluation.pdf.url({
              panelDefenseEvaluation: props.evaluation.id,
          })
        : faculty.evaluation.pdf.url({
              panelDefenseEvaluation: props.evaluation.id,
          });
});

const form = useForm({
    scores: { ...props.initialScores } as Record<string, number>,
    comments: props.initialComment,
    sdg_ids: [...props.initialSdgIds],
    q: (props.listFilters.q as string) ?? '',
    defense_type: (props.listFilters.defense_type as string) ?? '',
    status: (props.listFilters.status as string) ?? '',
    schedule_date: (props.listFilters.schedule_date as string) ?? '',
    per_page: (props.listFilters.per_page as number) ?? 15,
    page: (props.listFilters.page as number) ?? 1,
});

/** Avoid `as string | undefined` in templates — `|` is parsed as a Vue 2 filter. */
const sdgIdsError = computed((): string | undefined => {
    const e = form.errors.sdg_ids;

    if (e === undefined || e === null) {
        return undefined;
    }

    return Array.isArray(e) ? e.join(' ') : String(e);
});

const sectionHeadingByCriterionId = computed(() => {
    const m = new Map<string, string | null>();

    for (const c of props.criteria) {
        m.set(c.id, c.section_heading ?? null);
    }

    return m;
});

const rowsForForm = computed(() => {
    if (props.mode === 'create') {
        return props.criteria.map((c) => ({
            id: c.id,
            name: c.name,
            content: c.content ?? null,
            max_points: c.max_points,
            section_heading: c.section_heading ?? null,
        }));
    }

    if (props.mode === 'admin_edit' && props.evaluation) {
        return (props.evaluation.line_items ?? []).map((l) => ({
            id: l.criterion_id,
            name: l.name,
            content: l.content ?? null,
            max_points: l.max_points,
            section_heading:
                sectionHeadingByCriterionId.value.get(l.criterion_id) ?? null,
        }));
    }

    return [];
});

const displayLines = computed((): LineItem[] => {
    if (props.mode === 'view' && props.evaluation) {
        return (props.evaluation.line_items ?? []).map((line) => ({
            ...line,
            section_heading:
                sectionHeadingByCriterionId.value.get(line.criterion_id) ??
                null,
        }));
    }

    return [];
});

const currentTotal = computed(() => {
    if (!isEdit.value) {
        return 0;
    }

    if (isChecklist.value) {
        return rowsForForm.value.reduce(
            (sum, r) => sum + (Number(form.scores[r.id]) || 0),
            0,
        );
    }

    const rows = rowsForForm.value;

    if (rows.length === 0) {
        return 0;
    }

    if (scoringUseWeights.value) {
        const raw = rows.reduce((sum, r) => {
            const s = Number(form.scores[r.id]) || 0;

            return sum + (s / 100) * r.max_points;
        }, 0);

        return Math.round(raw);
    }

    const vals = rows.map((r) => Number(form.scores[r.id]) || 0);

    return Math.round(vals.reduce((a, b) => a + b, 0) / vals.length);
});

/** For checklist: number of items; for scoring, 100. */
const totalCap = computed(() => {
    if (isChecklist.value) {
        return rowsForForm.value.length;
    }

    return 100;
});

const viewChecklistItemCount = computed(() => displayLines.value.length);

function lineScoreDisplay(line: { score: number; max_points: number }): string {
    if (props.evaluation_type === 'checklist') {
        return line.score >= 1 ? 'Yes' : 'No';
    }

    return `${line.score} / 100`;
}

function plainTextFromHtml(html: string): string {
    return html
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/&[a-z]+;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function escapeHtml(s: string): string {
    return s
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

/** Shared Tailwind for TipTap / sanitized HTML: spacing matches readable prose. */
const criterionRichTextClass =
    'criterion-rich-text max-w-none text-sm leading-relaxed ' +
    '[&_a]:break-words [&_a]:text-primary [&_a]:underline ' +
    '[&_p]:mb-2 [&_p]:mt-0 [&_p:last-child]:mb-0 ' +
    '[&_ul]:my-2 [&_ul]:ml-0 [&_ul]:list-disc [&_ul]:pl-5 ' +
    '[&_ol]:my-2 [&_ol]:ml-0 [&_ol]:list-decimal [&_ol]:pl-5 ' +
    '[&_li>ul]:mt-1.5 [&_li>ol]:mt-1.5 ' +
    '[&_li]:my-1.5 [&_li]:pl-0 ' +
    '[&_h2]:mb-2 [&_h2]:mt-3 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:leading-snug [&_h2]:text-foreground ' +
    '[&_h2:first-child]:mt-0 ' +
    '[&_h3]:mb-1.5 [&_h3]:mt-2.5 [&_h3]:text-sm [&_h3]:font-semibold [&_h3]:leading-snug [&_h3]:text-foreground ' +
    '[&_h3:first-child]:mt-0 ' +
    '[&_blockquote]:my-2 [&_blockquote]:border-l-2 [&_blockquote]:border-border [&_blockquote]:pl-3 [&_blockquote]:text-muted-foreground ' +
    '[&_code]:rounded [&_code]:bg-muted/80 [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-[0.8125rem] ' +
    '[&_pre]:my-2 [&_pre]:max-w-full [&_pre]:overflow-x-auto [&_pre]:rounded-md [&_pre]:bg-muted/80 [&_pre]:p-2.5 [&_pre]:text-xs ' +
    '[&_strong]:text-foreground';

function criterionDisplayHtml(r: {
    name: string;
    content?: string | null;
}): string {
    const c = r.content;

    if (c && plainTextFromHtml(c) !== '') {
        return c;
    }

    return `<p class="m-0 font-medium leading-snug text-foreground">${escapeHtml(r.name)}</p>`;
}

/** Yes: 1 / 0; No: 0 / 1 — toggling one updates the other via `score`. */
function onChecklistYes(criterionId: string, v: boolean | 'indeterminate') {
    form.scores[criterionId] = v === true ? 1 : 0;
}

function onChecklistNo(criterionId: string, v: boolean | 'indeterminate') {
    form.scores[criterionId] = v === true ? 0 : 1;
}

function storeUrl() {
    return isAdmin.value
        ? admin.evaluation.store.url({ panelDefense: props.defense.id })
        : faculty.evaluation.store.url({ panelDefense: props.defense.id });
}

function updateUrl() {
    if (!props.evaluation) {
        return '';
    }

    return admin.evaluation.update.url({
        panelDefenseEvaluation: props.evaluation.id,
    });
}

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

function fieldError(criterionId: string): string | undefined {
    const e = form.errors;

    if (e[`scores.${criterionId}`]) {
        return e[`scores.${criterionId}`] as string;
    }

    if (e.scores) {
        return e.scores as string;
    }

    return undefined;
}

function sdgLabel(id: string): string {
    const s = props.sdgs.find((x) => x.id === id);

    if (!s) {
        return id;
    }

    return s.number ? `SDG ${s.number}: ${s.name}` : s.name;
}

async function submit() {
    if (isReadonly.value) {
        return;
    }

    const n = currentTotal.value;
    const cap = totalCap.value;
    let message: string;

    if (isChecklist.value) {
        message =
            props.mode === 'admin_edit' && props.evaluation?.evaluator_name
                ? `Save this checklist for ${props.evaluation.evaluator_name}? ${n} of ${cap} items marked yes.`
                : `You are recording ${n} of ${cap} items as yes. ${
                      isAdmin.value
                          ? 'Save this evaluation?'
                          : 'This cannot be edited after submit.'
                  }`;
    } else {
        const kind = scoringUseWeights.value
            ? 'weighted total'
            : 'average score';
        message =
            props.mode === 'admin_edit' && props.evaluation?.evaluator_name
                ? `Save updated scores for ${props.evaluation.evaluator_name}? New ${kind}: ${n} / 100.`
                : `You are submitting a ${kind} of ${n} / 100. ${
                      isAdmin.value
                          ? 'Save this evaluation?'
                          : 'This cannot be edited after submit.'
                  }`;
    }

    const ok = await confirm(message, {
        title:
            props.mode === 'admin_edit' ? 'Save changes?' : 'Submit evaluation',
        confirmLabel: props.mode === 'admin_edit' ? 'Save' : 'Submit',
        cancelLabel: 'Back',
        destructive: props.mode === 'create',
    });

    if (!ok) {
        return;
    }

    if (props.mode === 'admin_edit' && props.evaluation) {
        form.patch(updateUrl(), {
            preserveScroll: true,
        });
    } else {
        form.post(storeUrl(), { preserveScroll: true });
    }
}

const pageTitle = computed(() => {
    if (props.mode === 'view') {
        return 'Your Evaluation';
    }

    if (props.mode === 'admin_edit') {
        return 'Edit panel evaluation';
    }

    if (props.evaluation_type === 'checklist') {
        return 'Checklist evaluation';
    }

    return 'Score this defense';
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Defense Evaluation' }],
    },
});
</script>

<template>
    <div>
        <Head :title="pageTitle" />
        <div
            class="mx-auto min-h-[60vh] max-w-5xl space-y-6 p-4 pb-20 md:space-y-8 md:p-6"
        >
            <div>
                <Link
                    :href="listUrl"
                    class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to list
                </Link>
                <div class="mt-1 flex items-start gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40 text-primary"
                    >
                        <ClipboardList class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
                        <h1
                            class="text-2xl font-bold tracking-tight text-foreground"
                        >
                            {{ pageTitle }}
                        </h1>
                        <p
                            v-if="mode === 'view'"
                            class="mt-0.5 text-sm text-muted-foreground"
                        >
                            Snapshot of criteria and scores for this file.
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="space-y-4 rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
            >
                <h2
                    class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Defense
                </h2>
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div v-if="defense.paper_title" class="sm:col-span-2">
                        <dt class="text-muted-foreground">Research</dt>
                        <dd class="font-medium text-foreground">
                            {{ defense.paper_title }}
                        </dd>
                    </div>
                    <div v-if="defense.tracking_id">
                        <dt class="text-muted-foreground">Tracking</dt>
                        <dd>
                            {{ defense.tracking_id }}
                        </dd>
                    </div>
                    <div v-if="defense.student_name">
                        <dt class="text-muted-foreground">Student</dt>
                        <dd>
                            {{ defense.student_name }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Type</dt>
                        <dd>
                            {{ defense.defense_type_label }}
                        </dd>
                    </div>
                    <div v-if="defense.evaluation_format">
                        <dt class="text-muted-foreground">Rubric</dt>
                        <dd>
                            {{ defense.evaluation_format.name }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Schedule</dt>
                        <dd>
                            {{ formatWhen(defense.schedule) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Title evaluation: SDG picks (per panelist; paper SDGs are admin-only) -->
            <div
                v-if="mode === 'view' && evaluation && isTitleSdg"
                class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
            >
                <h2
                    class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    SDG checklist (evaluator)
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    SDGs you indicated for this title evaluation. Official paper
                    SDG tags are set on the research record by an admin.
                </p>
                <div
                    v-if="evaluation.sdg_ids?.length"
                    class="mt-3 flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="id in evaluation.sdg_ids"
                        :key="id"
                        class="inline-flex max-w-full items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-950/50 dark:text-blue-200"
                    >
                        <span class="line-clamp-2 break-words">{{
                            sdgLabel(id)
                        }}</span>
                    </span>
                </div>
                <p v-else class="mt-2 text-sm text-muted-foreground italic">
                    No SDG selections on file (older submission or not
                    required).
                </p>
            </div>

            <div
                v-if="!criteriaReady && mode === 'create'"
                class="rounded-2xl border border-amber-500/40 bg-amber-50/80 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-100"
            >
                <template v-if="isChecklist">
                    <p class="font-medium">
                        This checklist is not ready (no items yet).
                    </p>
                    <p class="mt-1 text-amber-900/80 dark:text-amber-200/80">
                        An admin must add at least one yes / no item for this
                        format. Go to
                        <Link
                            v-if="isAdmin"
                            :href="admin.evaluationFormats.index.url()"
                            class="font-medium underline"
                            >Evaluation formats</Link
                        >
                        <span v-else>Evaluation formats in Admin</span>.
                    </p>
                </template>
                <template v-else>
                    <p class="font-medium">
                        <template v-if="scoringUseWeights"
                            >This rubric is not ready (weights total
                            {{ criteriaTotalMax }}/100).</template
                        >
                        <template v-else
                            >This rubric is not ready — add at least one
                            criterion.</template
                        >
                    </p>
                    <p class="mt-1 text-amber-900/80 dark:text-amber-200/80">
                        <template v-if="scoringUseWeights"
                            >An admin must set criteria so percentage weights
                            add up to 100. Go to
                            <Link
                                v-if="isAdmin"
                                :href="admin.evaluationFormats.index.url()"
                                class="font-medium underline"
                                >Evaluation formats</Link
                            >
                            <span v-else>Evaluation formats in Admin</span
                            >.</template
                        >
                        <template v-else
                            >An admin must add scoring criteria (each line is
                            scored 1–100; the evaluation total is the average).
                            Go to
                            <Link
                                v-if="isAdmin"
                                :href="admin.evaluationFormats.index.url()"
                                class="font-medium underline"
                                >Evaluation formats</Link
                            >
                            <span v-else>Evaluation formats in Admin</span
                            >.</template
                        >
                    </p>
                </template>
            </div>

            <!-- View: historical snapshot -->
            <div v-else-if="mode === 'view' && evaluation" class="space-y-4">
                <div
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
                >
                    <div
                        class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <h2
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ isChecklist ? 'Checklist' : 'Criteria' }} &
                            scores
                        </h2>
                        <Button
                            v-if="evaluationPdfUrl"
                            variant="secondary"
                            class="shrink-0"
                            as-child
                        >
                            <a
                                :href="evaluationPdfUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <FileDown
                                    class="mr-1.5 inline h-4 w-4 align-text-bottom"
                                />
                                Download PDF form
                            </a>
                        </Button>
                    </div>
                    <div
                        v-if="isChecklist"
                        class="overflow-x-auto rounded-lg border border-border"
                    >
                        <table
                            class="w-full min-w-[min(100%,20rem)] border-collapse text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    <th class="min-w-0 px-3 py-2.5">Item</th>
                                    <th class="w-20 px-3 py-2.5 text-center">
                                        Yes
                                    </th>
                                    <th class="w-20 px-3 py-2.5 text-center">
                                        No
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template
                                    v-for="(line, idx) in displayLines"
                                    :key="line.criterion_id + String(idx)"
                                >
                                    <tr
                                        v-if="
                                            line.section_heading &&
                                            (idx === 0 ||
                                                displayLines[idx - 1]
                                                    ?.section_heading !==
                                                    line.section_heading)
                                        "
                                        class="border-b border-border bg-muted/40"
                                    >
                                        <td
                                            colspan="3"
                                            class="px-3 py-2 text-xs font-semibold tracking-wide text-foreground uppercase"
                                        >
                                            {{ line.section_heading }}
                                        </td>
                                    </tr>
                                    <tr
                                        class="border-b border-border/60 last:border-0"
                                    >
                                        <td
                                            class="min-w-0 px-3 py-3 align-top text-foreground"
                                        >
                                            <div
                                                :class="criterionRichTextClass"
                                                v-html="
                                                    criterionDisplayHtml(line)
                                                "
                                            />
                                        </td>
                                        <td
                                            class="px-3 py-3 text-center align-middle text-foreground"
                                        >
                                            <span
                                                v-if="line.score >= 1"
                                                class="font-semibold text-primary"
                                                aria-label="Yes"
                                                >✓</span
                                            >
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >—</span
                                            >
                                        </td>
                                        <td
                                            class="px-3 py-3 text-center align-middle text-foreground"
                                        >
                                            <span
                                                v-if="line.score < 1"
                                                class="font-semibold text-muted-foreground"
                                                aria-label="No"
                                                >✓</span
                                            >
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >—</span
                                            >
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="space-y-3">
                        <template
                            v-for="(line, idx) in displayLines"
                            :key="line.criterion_id + String(idx)"
                        >
                            <p
                                v-if="
                                    line.section_heading &&
                                    (idx === 0 ||
                                        displayLines[idx - 1]
                                            ?.section_heading !==
                                            line.section_heading)
                                "
                                class="border-b border-border pb-1 text-xs font-semibold tracking-wide text-foreground uppercase"
                            >
                                {{ line.section_heading }}
                            </p>
                            <div
                                class="flex items-start justify-between gap-4 border-b border-border/60 pb-3 last:border-0"
                            >
                                <div class="min-w-0 text-sm">
                                    <div
                                        :class="criterionRichTextClass"
                                        v-html="criterionDisplayHtml(line)"
                                    />
                                    <p
                                        class="mt-2 text-xs text-muted-foreground"
                                    >
                                        <template
                                            v-if="
                                                line.max_points !== 100 &&
                                                evaluation_type === 'scoring'
                                            "
                                            >Weight {{ line.max_points }}% ·
                                            score 1–100</template
                                        >
                                        <template
                                            v-else-if="
                                                evaluation_type === 'scoring'
                                            "
                                            >Score 1–100</template
                                        >
                                    </p>
                                </div>
                                <p
                                    class="shrink-0 text-sm font-semibold text-foreground tabular-nums"
                                >
                                    {{ lineScoreDisplay(line) }}
                                </p>
                            </div>
                        </template>
                    </div>
                    <div
                        class="mt-4 flex items-center justify-between border-t border-border pt-4"
                    >
                        <span class="text-sm font-semibold text-foreground">{{
                            isChecklist
                                ? 'Yes count'
                                : defense.evaluation_format?.use_weights
                                  ? 'Weighted total'
                                  : 'Average score'
                        }}</span>
                        <span class="text-xl font-bold text-primary">
                            <template v-if="isChecklist"
                                >{{ evaluation.final_score }} /
                                {{ viewChecklistItemCount }}</template
                            >
                            <template v-else
                                >{{ evaluation.final_score }} / 100</template
                            >
                        </span>
                    </div>
                    <div class="mt-4 border-t border-border pt-4">
                        <h3 class="mb-2 text-sm font-semibold text-foreground">
                            Comments
                        </h3>
                        <p
                            v-if="evaluation.comments?.trim()"
                            class="text-sm whitespace-pre-wrap text-foreground"
                        >
                            {{ evaluation.comments }}
                        </p>
                        <p v-else class="text-sm text-muted-foreground italic">
                            —
                        </p>
                    </div>
                </div>
                <p v-if="!isAdmin" class="text-xs text-muted-foreground">
                    This submission is final and cannot be changed.
                </p>
            </div>

            <!-- Create / admin edit form -->
            <form v-else-if="isEdit" class="space-y-5" @submit.prevent="submit">
                <div
                    v-if="isTitleSdg"
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
                >
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        SDG checklist
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Select all sustainable development goals that apply to
                        this title proposal (your assessment). The official SDG
                        list on the research paper is set by an admin
                        separately.
                    </p>
                    <div class="mt-3">
                        <MultiSelect
                            v-model="form.sdg_ids"
                            :options="sdgOptions"
                            search-placeholder="Search SDGs…"
                            checkbox-accent-class="accent-primary"
                        />
                    </div>
                    <InputError :message="sdgIdsError" class="mt-2" />
                </div>

                <div
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
                >
                    <h2
                        class="mb-1 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{
                            mode === 'admin_edit'
                                ? 'Panelist snapshot'
                                : isChecklist
                                  ? 'Checklist'
                                  : 'Scoring'
                        }}
                    </h2>
                    <p class="mb-4 text-sm text-muted-foreground">
                        <template v-if="isChecklist"
                            >For each row, mark Yes or No. The total is how many
                            items are yes.</template
                        >
                        <template v-else-if="scoringUseWeights"
                            >Score each line 1–100. The total is the sum of
                            (score ÷ 100 × weight%) per line — e.g. 80 on a 10%
                            line adds 8 points (max 100 overall).</template
                        >
                        <template v-else
                            >Score each criterion 1–100. The final result is the
                            <strong class="text-foreground">average</strong> of
                            all lines (out of 100).</template
                        >
                    </p>
                    <div
                        v-if="isChecklist"
                        class="overflow-x-auto rounded-lg border border-border"
                    >
                        <table
                            class="w-full min-w-[min(100%,20rem)] border-collapse text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b border-border bg-muted/40 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    <th class="min-w-0 px-3 py-2.5">Item</th>
                                    <th class="w-24 px-3 py-2.5 text-center">
                                        Yes
                                    </th>
                                    <th class="w-24 px-3 py-2.5 text-center">
                                        No
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template
                                    v-for="(r, idx) in rowsForForm"
                                    :key="r.id"
                                >
                                    <tr
                                        v-if="
                                            r.section_heading &&
                                            (idx === 0 ||
                                                rowsForForm[idx - 1]
                                                    ?.section_heading !==
                                                    r.section_heading)
                                        "
                                        class="border-b border-border bg-muted/40"
                                    >
                                        <td
                                            colspan="3"
                                            class="px-3 py-2 text-xs font-semibold tracking-wide text-foreground uppercase"
                                        >
                                            {{ r.section_heading }}
                                        </td>
                                    </tr>
                                    <tr
                                        class="border-b border-border/60 last:border-0"
                                    >
                                        <td class="min-w-0 px-3 py-3 align-top">
                                            <div
                                                :class="criterionRichTextClass"
                                                v-html="criterionDisplayHtml(r)"
                                            />
                                            <InputError
                                                v-if="fieldError(r.id)"
                                                :message="fieldError(r.id)!"
                                                class="mt-1"
                                            />
                                        </td>
                                        <td
                                            class="px-3 py-3 text-center align-middle"
                                        >
                                            <div
                                                class="inline-flex items-center justify-center"
                                            >
                                                <Checkbox
                                                    :id="`checklist-yes-${r.id}`"
                                                    :model-value="
                                                        Number(
                                                            form.scores[r.id] ??
                                                                0,
                                                        ) === 1
                                                    "
                                                    :disabled="
                                                        mode === 'create' &&
                                                        !criteriaReady
                                                    "
                                                    :aria-label="`Yes — ${r.name}`"
                                                    @update:model-value="
                                                        (v) =>
                                                            onChecklistYes(
                                                                r.id,
                                                                v,
                                                            )
                                                    "
                                                />
                                            </div>
                                        </td>
                                        <td
                                            class="px-3 py-3 text-center align-middle"
                                        >
                                            <div
                                                class="inline-flex items-center justify-center"
                                            >
                                                <Checkbox
                                                    :id="`checklist-no-${r.id}`"
                                                    :model-value="
                                                        Number(
                                                            form.scores[r.id] ??
                                                                0,
                                                        ) === 0
                                                    "
                                                    :disabled="
                                                        mode === 'create' &&
                                                        !criteriaReady
                                                    "
                                                    :aria-label="`No — ${r.name}`"
                                                    @update:model-value="
                                                        (v) =>
                                                            onChecklistNo(
                                                                r.id,
                                                                v,
                                                            )
                                                    "
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="space-y-5">
                        <template v-for="(r, idx) in rowsForForm" :key="r.id">
                            <p
                                v-if="
                                    r.section_heading &&
                                    (idx === 0 ||
                                        rowsForForm[idx - 1]
                                            ?.section_heading !==
                                            r.section_heading)
                                "
                                class="border-b border-border pb-1 text-xs font-semibold tracking-wide text-foreground uppercase"
                            >
                                {{ r.section_heading }}
                            </p>
                            <div class="space-y-2">
                                <div
                                    class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
                                >
                                    <Label
                                        :for="`score-${r.id}`"
                                        class="block w-full max-w-full cursor-default flex-col items-stretch gap-0 text-left font-normal"
                                    >
                                        <span
                                            :class="criterionRichTextClass"
                                            v-html="criterionDisplayHtml(r)"
                                        />
                                    </Label>
                                    <div
                                        class="min-w-10 shrink-0 text-xs text-muted-foreground sm:min-w-10"
                                    >
                                        <span v-if="scoringUseWeights"
                                            >Weight {{ r.max_points }}% · 1 –
                                            100</span
                                        >
                                        <span v-else>1 – 100</span>
                                    </div>
                                </div>
                                <Input
                                    :id="`score-${r.id}`"
                                    v-model="form.scores[r.id]"
                                    type="number"
                                    :min="0"
                                    :max="100"
                                    required
                                    :disabled="
                                        mode === 'create' && !criteriaReady
                                    "
                                    class="h-10 bg-background"
                                />
                                <InputError
                                    v-if="fieldError(r.id)"
                                    :message="fieldError(r.id)!"
                                />
                            </div>
                        </template>
                    </div>
                    <div class="mt-6 space-y-2 border-t border-border pt-4">
                        <Label for="eval-comments" class="text-foreground"
                            >Comments
                            <span class="text-destructive" aria-hidden="true"
                                >*</span
                            ></Label
                        >
                        <p class="text-xs text-muted-foreground">
                            Add remarks or feedback for this evaluation.
                            Required to submit.
                        </p>
                        <textarea
                            id="eval-comments"
                            v-model="form.comments"
                            required
                            rows="5"
                            minlength="1"
                            maxlength="20000"
                            :disabled="mode === 'create' && !criteriaReady"
                            class="min-h-28 w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Write your comments…"
                        />
                        <InputError
                            v-if="form.errors.comments"
                            :message="form.errors.comments as string"
                        />
                    </div>
                </div>

                <div
                    class="flex items-center justify-between rounded-xl border border-border bg-muted/30 px-4 py-3"
                >
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <CheckCircle2 class="h-4 w-4 text-primary" />
                        <span v-if="isChecklist">Items marked yes</span>
                        <span v-else-if="scoringUseWeights"
                            >Weighted total</span
                        >
                        <span v-else>Average score</span>
                    </div>
                    <span class="text-xl font-bold text-primary tabular-nums"
                        >{{ currentTotal }} / {{ totalCap }}</span
                    >
                </div>
                <InputError
                    v-if="form.errors.scores"
                    :message="form.errors.scores as string"
                />

                <div class="flex flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        as-child
                        class="border-teal-500/50 text-teal-800 hover:bg-teal-50 dark:border-teal-600 dark:text-teal-200 dark:hover:bg-teal-950/40"
                    >
                        <Link :href="listUrl">Cancel</Link>
                    </Button>
                    <Button
                        type="submit"
                        class="bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50"
                        :disabled="
                            form.processing ||
                            (mode === 'create' && !criteriaReady)
                        "
                    >
                        {{ mode === 'admin_edit' ? 'Save changes' : 'Submit' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
