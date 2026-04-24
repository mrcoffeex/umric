<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, ClipboardList } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';
import faculty from '@/routes/faculty';

type Criterion = {
    id: string;
    name: string;
    max_points: number;
    sort_order: number;
};

type LineItem = {
    criterion_id: string;
    name: string;
    max_points: number;
    score: number;
};

type DefenseInfo = {
    id: string;
    schedule: string | null;
    defense_type: string;
    defense_type_label: string;
    paper_title: string | null;
    tracking_id: string | null;
    student_name: string | null;
};

type EvalOut = {
    id: string;
    line_items: LineItem[];
    final_score: number;
    is_mine: boolean;
    evaluator_name: string | null;
};

const props = defineProps<{
    context: 'admin' | 'faculty';
    mode: 'create' | 'view' | 'admin_edit';
    defense: DefenseInfo;
    listFilters: Record<string, string | number | undefined>;
    criteria: Criterion[];
    criteriaReady: boolean;
    criteriaTotalMax: number;
    evaluation: EvalOut | null;
    initialScores: Record<string, number>;
}>();

const { confirm } = useConfirm();

const isAdmin = computed(() => props.context === 'admin');
const isReadonly = computed(() => props.mode === 'view');
const isEdit = computed(
    () => props.mode === 'create' || props.mode === 'admin_edit',
);

const listUrl = computed(() =>
    isAdmin.value
        ? admin.evaluation.index.url({ query: props.listFilters as never })
        : faculty.evaluation.index.url({ query: props.listFilters as never }),
);

const form = useForm({
    scores: { ...props.initialScores } as Record<string, number>,
    q: (props.listFilters.q as string) ?? '',
    defense_type: (props.listFilters.defense_type as string) ?? '',
    status: (props.listFilters.status as string) ?? '',
    schedule_date: (props.listFilters.schedule_date as string) ?? '',
    per_page: (props.listFilters.per_page as number) ?? 15,
    page: (props.listFilters.page as number) ?? 1,
});

const rowsForForm = computed(() => {
    if (props.mode === 'create') {
        return props.criteria.map((c) => ({
            id: c.id,
            name: c.name,
            max_points: c.max_points,
        }));
    }

    if (props.mode === 'admin_edit' && props.evaluation) {
        return (props.evaluation.line_items ?? []).map((l) => ({
            id: l.criterion_id,
            name: l.name,
            max_points: l.max_points,
        }));
    }

    return [];
});

const displayLines = computed(() => {
    if (props.mode === 'view' && props.evaluation) {
        return props.evaluation.line_items ?? [];
    }

    return [];
});

const currentTotal = computed(() => {
    if (!isEdit.value) {
        return 0;
    }

    return rowsForForm.value.reduce(
        (sum, r) => sum + (Number(form.scores[r.id]) || 0),
        0,
    );
});

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

async function submit() {
    if (isReadonly.value) {
        return;
    }

    const n = currentTotal.value;
    const message =
        props.mode === 'admin_edit' && props.evaluation?.evaluator_name
            ? `Save updated scores for ${props.evaluation.evaluator_name}? New total: ${n} / 100.`
            : `You are submitting a total of ${n} / 100. ${
                  isAdmin.value
                      ? 'Save this evaluation?'
                      : 'This cannot be edited after submit.'
              }`;
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
        return 'Your evaluation';
    }

    if (props.mode === 'admin_edit') {
        return 'Edit panel evaluation';
    }

    return 'Score this defense';
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Defense evaluation' }],
    },
});
</script>

<template>
    <div>
        <Head :title="pageTitle" />
        <div
            class="mx-auto min-h-[60vh] max-w-2xl space-y-6 p-4 pb-20 md:space-y-8 md:p-6"
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
                    <div>
                        <dt class="text-muted-foreground">Schedule</dt>
                        <dd>
                            {{ formatWhen(defense.schedule) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                v-if="!criteriaReady && mode === 'create'"
                class="rounded-2xl border border-amber-500/40 bg-amber-50/80 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-100"
            >
                <p class="font-medium">
                    Criteria are not ready (current total:
                    {{ criteriaTotalMax }}/100).
                </p>
                <p class="mt-1 text-amber-900/80 dark:text-amber-200/80">
                    An admin must set evaluation criteria to total 100 before
                    you can submit. Go to
                    <Link
                        v-if="isAdmin"
                        :href="admin.evaluationCriteria.index.url()"
                        class="font-medium underline"
                        >Evaluation criteria</Link
                    >
                    <span v-else>Evaluation criteria in Admin</span>.
                </p>
            </div>

            <!-- View: historical snapshot -->
            <div v-else-if="mode === 'view' && evaluation" class="space-y-4">
                <div
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
                >
                    <h2
                        class="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Criteria & scores
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="(line, idx) in displayLines"
                            :key="line.criterion_id + String(idx)"
                            class="flex items-start justify-between gap-4 border-b border-border/60 pb-3 last:border-0"
                        >
                            <div class="min-w-0 text-sm">
                                <p class="font-medium text-foreground">
                                    {{ line.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Max {{ line.max_points }} pts
                                </p>
                            </div>
                            <p
                                class="shrink-0 text-sm font-semibold text-foreground tabular-nums"
                            >
                                {{ line.score }} / {{ line.max_points }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex items-center justify-between border-t border-border pt-4"
                    >
                        <span class="text-sm font-semibold text-foreground"
                            >Total</span
                        >
                        <span class="text-xl font-bold text-primary"
                            >{{ evaluation.final_score }} / 100</span
                        >
                    </div>
                </div>
                <p v-if="!isAdmin" class="text-xs text-muted-foreground">
                    This submission is final and cannot be changed.
                </p>
            </div>

            <!-- Create / admin edit form -->
            <form v-else-if="isEdit" class="space-y-5" @submit.prevent="submit">
                <div
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm md:p-6"
                >
                    <h2
                        class="mb-1 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{
                            mode === 'admin_edit'
                                ? 'Panelist snapshot'
                                : 'Scoring'
                        }}
                    </h2>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Award points for each line. The total cannot exceed 100,
                        and each line is capped as shown.
                    </p>
                    <div class="space-y-5">
                        <div
                            v-for="r in rowsForForm"
                            :key="r.id"
                            class="space-y-2"
                        >
                            <div
                                class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
                            >
                                <Label
                                    :for="`score-${r.id}`"
                                    class="text-foreground"
                                    >{{ r.name }}</Label
                                >
                                <span class="text-xs text-muted-foreground"
                                    >0 – {{ r.max_points }} points</span
                                >
                            </div>
                            <Input
                                :id="`score-${r.id}`"
                                v-model="form.scores[r.id]"
                                type="number"
                                :min="0"
                                :max="r.max_points"
                                required
                                :disabled="mode === 'create' && !criteriaReady"
                                class="h-10 bg-background"
                            />
                            <InputError
                                v-if="fieldError(r.id)"
                                :message="fieldError(r.id)!"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between rounded-xl border border-border bg-muted/30 px-4 py-3"
                >
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <CheckCircle2 class="h-4 w-4 text-primary" />
                        <span>Sum of awarded points</span>
                    </div>
                    <span class="text-xl font-bold text-primary tabular-nums"
                        >{{ currentTotal }} / 100</span
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
