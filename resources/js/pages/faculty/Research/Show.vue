<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CalendarClock, Check, Clock3, FileBarChart2, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { getStepStatusClass } from '@/lib/step-colors';
import { index as classesIndex } from '@/routes/faculty/classes';
import research from '@/routes/faculty/classes/research';

interface StepRecord {
    id: number;
    step: string | null;
    action: string;
    status: string | null;
    notes?: string | null;
    updated_by?: { id: number; name: string } | null;
    performed_by?: string | null;
    created_at?: string | null;
}

interface Paper {
    id: number;
    title: string;
    tracking_id: string;
    abstract?: string | null;
    proponents?: string[] | string | null;
    sdg_ids?: number[] | null;
    agenda_ids?: number[] | null;
    submission_date?: string | null;
    status?: string | null;
    current_step: string;
    step_label?: string;
    step_ric_review?: string | null;
    step_outline_defense?: string | null;
    outline_defense_schedule?: string | null;
    step_rating?: string | null;
    grade?: number | null;
    step_final_defense?: string | null;
    final_defense_schedule?: string | null;
    student?: { id: number; name: string; email?: string } | null;
    user?: { id: number; name: string } | null;
    adviser?: { id: number; name: string } | null;
    statistician?: { id: number; name: string } | null;
    school_class?: { id: number; name: string } | null;
}

interface Props {
    paper: Paper;
    schoolClass?: { id: number; name: string } | null;
    trackingRecords?: StepRecord[];
    stepLabels: Record<string, string>;
}

const props = defineProps<Props>();

const schoolClass = computed(() => props.schoolClass ?? props.paper.school_class);
const student = computed(() => props.paper.student ?? props.paper.user ?? null);

const steps = [
    'ric_review',
    'plagiarism_check',
    'outline_defense',
    'rating',
    'final_manuscript',
    'final_defense',
    'hard_bound',
    'completed',
];

const timeline = computed(() => {
    return [...(props.trackingRecords ?? [])].sort((a, b) => {
        const left = new Date(a.created_at ?? '').getTime();
        const right = new Date(b.created_at ?? '').getTime();

        return left - right;
    });
});

const actionForm = useForm({
    step: props.paper.current_step,
    status: '',
    schedule: '',
    grade: '',
    notes: '',
});

const canRicApprove = computed(
    () =>
        props.paper.current_step === 'ric_review' &&
        (props.paper.step_ric_review ?? 'pending') === 'pending',
);

const canOutlineDefense = computed(() => props.paper.current_step === 'outline_defense');

const canRate = computed(
    () => props.paper.current_step === 'rating' && (props.paper.step_rating ?? 'pending') === 'pending',
);

const canFinalDefense = computed(() => props.paper.current_step === 'final_defense');

const proponents = computed(() => {
    if (!props.paper.proponents) {
        return [];
    }

    if (Array.isArray(props.paper.proponents)) {
        return props.paper.proponents;
    }

    return props.paper.proponents
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean);
});

function formatDateTime(value?: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function approveRic(): void {
    actionForm.post(
        research.approve.url({
            class: schoolClass.value?.id ?? 0,
            paper: props.paper.id,
        }),
        { preserveScroll: true },
    );
}

function updateStep(status: string): void {
    actionForm.step = props.paper.current_step;
    actionForm.status = status;
    actionForm.patch(
        research.updateStep.url({
            class: schoolClass.value?.id ?? 0,
            paper: props.paper.id,
        }),
        { preserveScroll: true },
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My Classes', href: classesIndex() },
            { title: 'Research', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="paper.title" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border border-border bg-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ paper.title }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ student?.name ?? '-' }}
                    </p>
                </div>
                <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-950/30 dark:text-orange-300">
                    {{ paper.tracking_id }}
                </span>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <div
                    v-for="step in steps"
                    :key="step"
                    :class="['rounded-xl border px-3 py-2 text-center text-[11px] font-semibold', getStepStatusClass(step, paper.current_step, steps)]"
                >
                    {{ stepLabel(step) }}
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="text-base font-bold text-foreground">Faculty Action Panel</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Current step: {{ paper.step_label ?? stepLabel(paper.current_step) }}</p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Notes</label>
                            <textarea
                                v-model="actionForm.notes"
                                rows="3"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                placeholder="Optional notes..."
                            />
                        </div>

                        <div v-if="canRicApprove" class="rounded-xl border border-teal-200 bg-teal-50 p-4 dark:border-teal-800 dark:bg-teal-950/30">
                            <p class="mb-2 text-sm font-semibold text-teal-700 dark:text-teal-300">RIC Review</p>
                            <button
                                type="button"
                                @click="approveRic"
                                :disabled="actionForm.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50"
                            >
                                <Check class="h-3.5 w-3.5" />
                                Approve for Plagiarism Check
                            </button>
                        </div>

                        <div v-if="canOutlineDefense" class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
                            <p class="mb-2 text-sm font-semibold text-blue-700 dark:text-blue-300">Outline Defense</p>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Schedule</label>
                            <input
                                v-model="actionForm.schedule"
                                type="datetime-local"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                            />
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" @click="updateStep('passed')" :disabled="actionForm.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50">
                                    <Check class="h-3.5 w-3.5" /> Passed
                                </button>
                                <button type="button" @click="updateStep('re_defense')" :disabled="actionForm.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50">
                                    <X class="h-3.5 w-3.5" /> Re-Defense
                                </button>
                            </div>
                        </div>

                        <div v-if="canRate" class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                            <p class="mb-2 text-sm font-semibold text-amber-700 dark:text-amber-300">Rating</p>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Grade</label>
                            <input
                                v-model="actionForm.grade"
                                type="number"
                                min="1"
                                max="5"
                                step="0.25"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                            />
                            <button type="button" @click="updateStep('rated')" :disabled="actionForm.processing" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50">
                                <FileBarChart2 class="h-3.5 w-3.5" /> Submit Grade
                            </button>
                        </div>

                        <div v-if="canFinalDefense" class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 dark:border-cyan-800 dark:bg-cyan-950/30">
                            <p class="mb-2 text-sm font-semibold text-cyan-700 dark:text-cyan-300">Final Defense</p>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Schedule</label>
                            <input
                                v-model="actionForm.schedule"
                                type="datetime-local"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                            />
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" @click="updateStep('passed')" :disabled="actionForm.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50">
                                    <Check class="h-3.5 w-3.5" /> Passed
                                </button>
                                <button type="button" @click="updateStep('re_defense')" :disabled="actionForm.processing" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50">
                                    <X class="h-3.5 w-3.5" /> Re-Defense
                                </button>
                            </div>
                        </div>

                        <p v-if="!canRicApprove && !canOutlineDefense && !canRate && !canFinalDefense" class="text-sm text-muted-foreground">
                            No faculty action is required for this step.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="text-base font-bold text-foreground">Tracking History</h2>

                    <div v-if="timeline.length === 0" class="mt-3 text-sm text-muted-foreground">
                        No tracking records yet.
                    </div>

                    <div v-else class="mt-4 divide-y divide-border">
                        <div
                            v-for="record in timeline"
                            :key="record.id"
                            class="py-4 first:pt-0 last:pb-0"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-foreground">{{ record.action }}</p>
                                <span class="text-xs text-muted-foreground">{{ formatDateTime(record.created_at) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Step: {{ record.step ? stepLabel(record.step) : '-' }}
                                • Status: {{ record.status ?? '-' }}
                            </p>
                            <p v-if="record.notes" class="mt-1 text-xs text-foreground">{{ record.notes }}</p>
                            <p class="mt-1 text-[11px] text-muted-foreground">
                                <span class="font-medium text-foreground">By</span>
                                {{ record.updated_by?.name ?? record.performed_by ?? 'System' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="text-base font-bold text-foreground">Paper Metadata</h2>
                    <div class="mt-3 space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Abstract</p>
                            <p class="mt-1 text-foreground">
                                {{ paper.abstract || 'No abstract provided.' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Proponents</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span v-for="item in proponents" :key="item" class="rounded-full border border-border px-2 py-0.5 text-xs text-foreground">
                                    {{ item }}
                                </span>
                                <span v-if="proponents.length === 0" class="text-xs text-muted-foreground">No proponents listed.</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">SDGs</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span v-for="id in paper.sdg_ids ?? []" :key="`sdg-${id}`" class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700 dark:bg-teal-950/40 dark:text-teal-300">
                                    SDG {{ id }}
                                </span>
                                <span v-if="!(paper.sdg_ids ?? []).length" class="text-xs text-muted-foreground">None</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Agendas</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span v-for="id in paper.agenda_ids ?? []" :key="`agenda-${id}`" class="rounded-full bg-orange-50 px-2 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-950/40 dark:text-orange-300">
                                    Agenda {{ id }}
                                </span>
                                <span v-if="!(paper.agenda_ids ?? []).length" class="text-xs text-muted-foreground">None</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5 text-sm">
                    <h2 class="text-base font-bold text-foreground">People</h2>
                    <div class="mt-3 space-y-2">
                        <p class="text-foreground"><span class="text-muted-foreground">Adviser:</span> {{ paper.adviser?.name ?? 'Not assigned' }}</p>
                        <p class="text-foreground"><span class="text-muted-foreground">Statistician:</span> {{ paper.statistician?.name ?? 'Not assigned' }}</p>
                    </div>
                    <div class="mt-3 space-y-1 text-xs text-muted-foreground">
                        <p class="inline-flex items-center gap-1.5">
                            <Clock3 class="h-3.5 w-3.5 text-blue-500" />
                            Outline schedule: {{ formatDateTime(paper.outline_defense_schedule) }}
                        </p>
                        <p class="inline-flex items-center gap-1.5">
                            <CalendarClock class="h-3.5 w-3.5 text-cyan-500" />
                            Final schedule: {{ formatDateTime(paper.final_defense_schedule) }}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
