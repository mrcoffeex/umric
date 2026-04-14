<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    CalendarClock,
    Check,
    Clock3,
    FileCheck2,
    Link2,
    ShieldCheck,
    UserPlus,
    X,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { getStepStatusClass } from '@/lib/step-colors';
import admin from '@/routes/admin';

interface TrackingRecord {
    id: number;
    step: string | null;
    action: string;
    status: string | null;
    old_status: string | null;
    notes: string | null;
    metadata: Record<string, unknown> | null;
    performed_by: string | null;
    created_at: string;
}

interface PaperDetail {
    id: number;
    tracking_id: string;
    title: string;
    abstract: string | null;
    proponents: string[] | null;
    sdg_ids: number[] | null;
    agenda_ids: number[] | null;
    keywords: string[] | null;
    current_step: string;
    step_label: string;
    step_ric_review: string | null;
    step_plagiarism: string | null;
    plagiarism_attempts: number;
    plagiarism_score: number | null;
    step_outline_defense: string | null;
    outline_defense_schedule: string | null;
    step_rating: string | null;
    grade: number | null;
    step_final_manuscript: string | null;
    step_final_defense: string | null;
    final_defense_schedule: string | null;
    step_hard_bound: string | null;
    user: { id: number; name: string; email: string };
    school_class: { id: number; name: string } | null;
    adviser: { id: number; name: string } | null;
    statistician: { id: number; name: string } | null;
    tracking_records: TrackingRecord[];
}

const props = defineProps<{
    paper: PaperDetail;
    facultyUsers: Array<{ id: number; name: string }>;
    staffUsers: Array<{ id: number; name: string }>;
    sdgs: Array<{ id: number; number: number; name: string; color: string }>;
    agendas: Array<{ id: number; title: string }>;
    stepLabels: Record<string, string>;
}>();

const workflowSteps = [
    'ric_review',
    'plagiarism_check',
    'outline_defense',
    'rating',
    'final_manuscript',
    'final_defense',
    'hard_bound',
    'completed',
];

const assignmentForm = useForm({
    adviser_id: props.paper.adviser?.id ?? '',
    statistician_id: props.paper.statistician?.id ?? '',
});

const stepForm = useForm({
    step: props.paper.current_step,
    status: '',
    notes: '',
    grade: '',
    schedule: '',
    plagiarism_score: '',
});

const selectedSdgs = computed(() =>
    props.sdgs.filter((sdg) => (props.paper.sdg_ids ?? []).includes(sdg.id)),
);
const selectedAgendas = computed(() =>
    props.agendas.filter((agenda) => (props.paper.agenda_ids ?? []).includes(agenda.id)),
);

function submitAssignment(): void {
    assignmentForm.post(admin.research.assign.url({ paper: props.paper.id }));
}

function submitStep(status: string): void {
    stepForm.step = props.paper.current_step;
    stepForm.status = status;
    stepForm.patch(admin.research.updateStep.url({ paper: props.paper.id }), {
        preserveScroll: true,
    });
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function toDateTimeLocal(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);
    const offset = date.getTimezoneOffset();
    const adjusted = new Date(date.getTime() - offset * 60000);

    return adjusted.toISOString().slice(0, 16);
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Research', href: admin.research.index() },
            { title: 'Paper Details', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="`Research: ${paper.tracking_id}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="rounded-2xl border border-border bg-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        {{ paper.title }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ paper.user.name }} • {{ paper.school_class?.name ?? 'No class' }}
                    </p>
                </div>
                <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-700 dark:bg-orange-950/40 dark:text-orange-300">
                    {{ paper.tracking_id }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <span
                    v-for="step in workflowSteps"
                    :key="step"
                    :class="[
                        'rounded-full border px-2.5 py-1 text-xs font-semibold',
                        getStepStatusClass(step, paper.current_step, workflowSteps),
                    ]"
                >
                    {{ stepLabels[step] ?? step }}
                </span>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="mb-3 text-base font-bold text-foreground">Workflow Action Panel</h2>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Current step: {{ paper.step_label }}
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Notes</label>
                            <textarea
                                v-model="stepForm.notes"
                                rows="3"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                placeholder="Add workflow notes (optional)..."
                            />
                        </div>

                        <template v-if="paper.current_step === 'ric_review'">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50" @click="submitStep('approved')">
                                    <Check class="h-4 w-4" /> Approve
                                </button>
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-destructive px-3 py-2 text-sm font-semibold text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50" @click="submitStep('rejected')">
                                    <X class="h-4 w-4" /> Reject
                                </button>
                            </div>
                        </template>

                        <template v-else-if="paper.current_step === 'plagiarism_check'">
                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Plagiarism Score</label>
                                    <input v-model="stepForm.plagiarism_score" type="number" min="0" max="100" step="0.01" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                                </div>
                                <div class="rounded-xl border border-border bg-muted px-3 py-2 text-xs font-semibold text-foreground">
                                    Attempts: {{ paper.plagiarism_attempts }} / 3
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50" @click="submitStep('passed')">
                                    <ShieldCheck class="h-4 w-4" /> Pass
                                </button>
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-destructive px-3 py-2 text-sm font-semibold text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50" @click="submitStep('failed')">
                                    <X class="h-4 w-4" /> Fail
                                </button>
                            </div>
                        </template>

                        <template v-else-if="paper.current_step === 'outline_defense'">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Defense Schedule</label>
                                <input v-model="stepForm.schedule" type="datetime-local" :placeholder="toDateTimeLocal(paper.outline_defense_schedule)" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="rounded-lg border border-border bg-card px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted disabled:opacity-50" @click="submitStep('pending')">Pending</button>
                                <button type="button" class="rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50" @click="submitStep('passed')">Pass</button>
                                <button type="button" class="rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50" @click="submitStep('re_defense')">Re-defense</button>
                            </div>
                        </template>

                        <template v-else-if="paper.current_step === 'rating'">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Grade</label>
                                <input v-model="stepForm.grade" type="number" min="1" max="100" step="0.01" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                            </div>
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50" @click="submitStep('rated')">
                                <FileCheck2 class="h-4 w-4" /> Mark as Rated
                            </button>
                        </template>

                        <template v-else-if="paper.current_step === 'final_manuscript'">
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50" @click="submitStep('submitted')">
                                <Check class="h-4 w-4" /> Mark Submitted
                            </button>
                        </template>

                        <template v-else-if="paper.current_step === 'final_defense'">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Final Defense Schedule</label>
                                <input v-model="stepForm.schedule" type="datetime-local" :placeholder="toDateTimeLocal(paper.final_defense_schedule)" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="rounded-lg border border-border bg-card px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted disabled:opacity-50" @click="submitStep('pending')">Pending</button>
                                <button type="button" class="rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50" @click="submitStep('passed')">Pass</button>
                                <button type="button" class="rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50" @click="submitStep('re_defense')">Re-defense</button>
                            </div>
                        </template>

                        <template v-else-if="paper.current_step === 'hard_bound'">
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50" @click="submitStep('submitted')">
                                <Link2 class="h-4 w-4" /> Mark Hard Bound Submitted
                            </button>
                        </template>

                        <template v-else>
                            <p class="text-sm text-muted-foreground">This paper is already completed.</p>
                        </template>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="mb-3 text-base font-bold text-foreground">Tracking History</h2>
                    <div v-if="paper.tracking_records.length === 0" class="text-sm text-muted-foreground">No tracking records yet.</div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="record in paper.tracking_records"
                            :key="record.id"
                            class="rounded-xl border border-border px-3 py-2"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-foreground">{{ record.action }}</p>
                                <span class="text-xs text-muted-foreground">{{ formatDateTime(record.created_at) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Step: {{ stepLabels[record.step ?? ''] ?? record.step ?? '-' }}
                                • Status: {{ record.status ?? '-' }}
                                <span v-if="record.old_status"> • Previous: {{ record.old_status }}</span>
                            </p>
                            <p v-if="record.notes" class="mt-1 text-xs text-muted-foreground">{{ record.notes }}</p>
                            <p v-if="record.performed_by" class="mt-1 text-[11px] text-muted-foreground">By {{ record.performed_by }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="mb-3 flex items-center gap-2 text-base font-bold text-foreground">
                        <UserPlus class="h-5 w-5 text-orange-500" />
                        Adviser & Statistician Assignment
                    </h2>

                    <form class="space-y-3" @submit.prevent="submitAssignment">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Adviser</label>
                            <select v-model="assignmentForm.adviser_id" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
                                <option value="">None</option>
                                <option v-for="faculty in facultyUsers" :key="faculty.id" :value="faculty.id">{{ faculty.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Statistician</label>
                            <select v-model="assignmentForm.statistician_id" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30">
                                <option value="">None</option>
                                <option v-for="staff in staffUsers" :key="staff.id" :value="staff.id">{{ staff.name }}</option>
                            </select>
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50" :disabled="assignmentForm.processing">
                            Save Assignments
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="mb-3 text-base font-bold text-foreground">Research Metadata</h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Abstract</p>
                            <p class="mt-1 text-foreground">{{ paper.abstract || 'No abstract provided.' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">SDGs</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span v-for="sdg in selectedSdgs" :key="sdg.id" class="rounded-full border border-border bg-muted px-2 py-0.5 text-xs text-foreground">
                                    SDG {{ sdg.number }}
                                </span>
                                <span v-if="selectedSdgs.length === 0" class="text-xs text-muted-foreground">None</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Agendas</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <span v-for="agenda in selectedAgendas" :key="agenda.id" class="rounded-full border border-border bg-muted px-2 py-0.5 text-xs text-foreground">
                                    {{ agenda.title }}
                                </span>
                                <span v-if="selectedAgendas.length === 0" class="text-xs text-muted-foreground">None</span>
                            </div>
                        </div>
                        <div v-if="paper.outline_defense_schedule || paper.final_defense_schedule" class="rounded-xl border border-border bg-muted px-3 py-2">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Schedules</p>
                            <p v-if="paper.outline_defense_schedule" class="inline-flex items-center gap-1.5 text-xs text-foreground"><Clock3 class="h-4 w-4 text-blue-500" /> Outline: {{ formatDateTime(paper.outline_defense_schedule) }}</p>
                            <p v-if="paper.final_defense_schedule" class="mt-1 inline-flex items-center gap-1.5 text-xs text-foreground"><CalendarClock class="h-4 w-4 text-cyan-500" /> Final: {{ formatDateTime(paper.final_defense_schedule) }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
