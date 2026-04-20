<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BookCheck,
    CalendarClock,
    Check,
    CheckCircle2,
    ClipboardCopy,
    Clock3,
    FileBarChart2,
    FileCheck2,
    FileSearch,
    GraduationCap,
    Link2,
    Maximize2,
    MessageSquare,
    PackageCheck,
    Pencil,
    ScrollText,
    Send,
    Shield,
    ShieldCheck,
    Trophy,
    UserPlus,
    X,
} from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, ref } from 'vue';
import { getStepBadgeClass } from '@/lib/step-colors';
import { index as researchIndex, show as researchShow, updateStep as updateStepRoute, assign as assignRoute, storeComment as storeCommentRoute, receive as receiveRoute } from '@/routes/admin/research';
import FormSelect from '@/components/FormSelect.vue';

interface StepRecord {
    id: number;
    step: string | null;
    action: string;
    status: string | null;
    old_status?: string | null;
    notes?: string | null;
    updated_by?: { id: number; name: string } | null;
    created_at?: string | null;
}

interface Comment {
    id: number;
    body: string;
    user: { id: number; name: string } | null;
    created_at: string;
}

interface Paper {
    id: number;
    title: string;
    abstract?: string | null;
    tracking_id: string;
    status: string;
    current_step: string;
    step_label?: string;
    submission_date?: string | null;
    created_at: string;
    keywords?: string | null;
    sdg_ids?: number[] | null;
    agenda_ids?: number[] | null;
    proponents?: string[] | Array<{ id: number; name: string }> | string | null;
    user_id?: number | null;
    step_ric_review?: string | null;
    step_plagiarism?: string | null;
    plagiarism_attempts?: number | null;
    plagiarism_score?: number | null;
    step_outline_defense?: string | null;
    outline_defense_schedule?: string | null;
    step_rating?: string | null;
    grade?: number | null;
    step_final_manuscript?: string | null;
    step_final_defense?: string | null;
    final_defense_schedule?: string | null;
    step_hard_bound?: string | null;
    student?: { id: number; name: string; email?: string } | null;
    adviser?: { id: number; name: string } | null;
    statistician?: { id: number; name: string } | null;
    school_class?: { id: number; name: string; section?: string | null } | null;
}

interface Props {
    paper: Paper;
    trackingRecords?: StepRecord[];
    stepLabels: Record<string, string>;
    steps: string[];
    sdgs: Array<{ id: number; name: string; number?: number; color?: string }>;
    agendas: Array<{ id: number; name: string }>;
    facultyUsers: Array<{ id: number; name: string }>;
    staffUsers: Array<{ id: number; name: string }>;
    comments?: Comment[];
}

const props = defineProps<Props>();

const sdgMap = computed(() =>
    Object.fromEntries(props.sdgs.map((s) => [s.id, s])),
);
const agendaMap = computed(() =>
    Object.fromEntries(props.agendas.map((a) => [a.id, a])),
);

const copied = ref(false);
const qrMode = ref<'tracking' | 'receiving'>('tracking');
const qrModalOpen = ref(false);

const trackingUrl = computed(() => {
    return `${window.location.origin}/track/${props.paper.tracking_id}`;
});

const receivingUrl = computed(() => {
    return `${window.location.origin}${receiveRoute.url({ paper: props.paper.id })}`;
});

const currentStepIndex = computed(() => {
    return props.steps.indexOf(props.paper.current_step);
});

const completedSteps = computed(() => currentStepIndex.value);

const progressPercent = computed(() => {
    if (props.steps.length <= 1) return 0;
    return Math.round((completedSteps.value / (props.steps.length - 1)) * 100);
});

const proponents = computed(() => {
    if (!props.paper.proponents) return [];
    if (Array.isArray(props.paper.proponents)) {
        return props.paper.proponents.map((p) => (typeof p === 'string' ? p : p.name));
    }
    return props.paper.proponents.split(',').map((v) => v.trim()).filter(Boolean);
});

const timeline = computed(() => {
    return [...(props.trackingRecords ?? [])].sort((a, b) => {
        return new Date(b.created_at ?? '').getTime() - new Date(a.created_at ?? '').getTime();
    });
});

const stepDetails = computed(() => {
    const p = props.paper;
    return [
        { key: 'title_proposal', icon: Send, status: 'Submitted', statusType: 'success' as const, info: null },
        { key: 'ric_review', icon: Shield, status: statusLabel(p.step_ric_review), statusType: statusType(p.step_ric_review, ['approved'], ['rejected']), info: null },
        { key: 'plagiarism_check', icon: FileSearch, status: statusLabel(p.step_plagiarism), statusType: statusType(p.step_plagiarism, ['passed'], ['failed']), info: `Attempts: ${p.plagiarism_attempts ?? 0} / 3${p.plagiarism_score != null ? ` · Score: ${p.plagiarism_score}%` : ''}` },
        { key: 'outline_defense', icon: BookCheck, status: statusLabel(p.step_outline_defense), statusType: statusType(p.step_outline_defense, ['passed'], ['re_defense']), info: p.outline_defense_schedule ? `Scheduled: ${formatDateTime(p.outline_defense_schedule)}` : null },
        { key: 'rating', icon: FileBarChart2, status: p.step_rating === 'rated' && p.grade != null ? `Rated — ${p.grade}` : statusLabel(p.step_rating), statusType: statusType(p.step_rating, ['rated'], []), info: null },
        { key: 'final_manuscript', icon: ScrollText, status: statusLabel(p.step_final_manuscript), statusType: statusType(p.step_final_manuscript, ['submitted'], []), info: null },
        { key: 'final_defense', icon: GraduationCap, status: statusLabel(p.step_final_defense), statusType: statusType(p.step_final_defense, ['passed'], ['re_defense']), info: p.final_defense_schedule ? `Scheduled: ${formatDateTime(p.final_defense_schedule)}` : null },
        { key: 'hard_bound', icon: Trophy, status: statusLabel(p.step_hard_bound), statusType: statusType(p.step_hard_bound, ['submitted'], []), info: null },
        { key: 'completed', icon: CheckCircle2, status: p.current_step === 'completed' ? 'Completed' : 'Pending', statusType: p.current_step === 'completed' ? ('success' as const) : ('neutral' as const), info: null },
    ];
});

// --- Per-step status options ---
const stepStatusOptions: Record<string, Array<{ value: string; label: string; color: string }>> = {
    ric_review: [
        { value: 'pending', label: 'Pending', color: 'bg-muted text-foreground' },
        { value: 'approved', label: 'Approved', color: 'bg-teal-500 text-white hover:bg-teal-600' },
        { value: 'rejected', label: 'Rejected', color: 'bg-destructive text-destructive-foreground hover:bg-destructive/90' },
    ],
    plagiarism_check: [
        { value: 'pending', label: 'Pending', color: 'bg-muted text-foreground' },
        { value: 'passed', label: 'Passed', color: 'bg-teal-500 text-white hover:bg-teal-600' },
        { value: 'failed', label: 'Failed', color: 'bg-destructive text-destructive-foreground hover:bg-destructive/90' },
    ],
    outline_defense: [
        { value: 'pending', label: 'Pending', color: 'bg-muted text-foreground' },
        { value: 'passed', label: 'Passed', color: 'bg-teal-500 text-white hover:bg-teal-600' },
        { value: 're_defense', label: 'Re-Defense', color: 'bg-amber-500 text-white hover:bg-amber-600' },
    ],
    rating: [
        { value: 'pending', label: 'Pending', color: 'bg-muted text-foreground' },
        { value: 'rated', label: 'Rated', color: 'bg-amber-500 text-white hover:bg-amber-600' },
    ],
    final_manuscript: [
        { value: 'pending', label: 'Pending', color: 'bg-muted text-foreground' },
        { value: 'submitted', label: 'Submitted', color: 'bg-indigo-500 text-white hover:bg-indigo-600' },
    ],
    final_defense: [
        { value: 'pending', label: 'Pending', color: 'bg-muted text-foreground' },
        { value: 'passed', label: 'Passed', color: 'bg-teal-500 text-white hover:bg-teal-600' },
        { value: 're_defense', label: 'Re-Defense', color: 'bg-amber-500 text-white hover:bg-amber-600' },
    ],
    hard_bound: [
        { value: 'ongoing', label: 'Ongoing', color: 'bg-muted text-foreground' },
        { value: 'submitted', label: 'Submitted', color: 'bg-emerald-500 text-white hover:bg-emerald-600' },
    ],
};

const managingStep = ref<string | null>(null);

function toggleManageStep(stepKey: string): void {
    if (managingStep.value === stepKey) {
        managingStep.value = null;
        stepForm.reset();
    } else {
        managingStep.value = stepKey;
        stepForm.reset();
        stepForm.step = stepKey;
    }
}

// --- Admin action forms ---
const stepForm = useForm({
    step: '',
    status: '',
    notes: '',
    grade: '',
    schedule: '',
    plagiarism_score: '',
});

const assignmentForm = useForm({
    adviser_id: props.paper.adviser?.id ?? '',
    statistician_id: props.paper.statistician?.id ?? '',
});

const commentForm = useForm({ body: '' });

function submitStepFor(step: string, status: string): void {
    stepForm.step = step;
    stepForm.status = status;
    stepForm.patch(updateStepRoute.url({ paper: props.paper.id }), {
        preserveScroll: true,
        onSuccess: () => {
            managingStep.value = null;
            stepForm.reset();
        },
    });
}

function submitAssignment(): void {
    assignmentForm.post(assignRoute.url({ paper: props.paper.id }), {
        preserveScroll: true,
    });
}

function submitComment(): void {
    commentForm.post(storeCommentRoute.url({ paper: props.paper.id }), {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
}

function timeAgo(value: string): string {
    const seconds = Math.floor((Date.now() - new Date(value).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;
    return formatDateTime(value);
}

function statusLabel(value?: string | null): string {
    if (!value) return 'Pending';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusType(value: string | null | undefined, successValues: string[], dangerValues: string[]): 'success' | 'warning' | 'danger' | 'neutral' {
    if (!value || value === 'pending') return 'neutral';
    if (successValues.includes(value)) return 'success';
    if (dangerValues.includes(value)) return 'danger';
    return 'warning';
}

const statusTypeClasses: Record<string, string> = {
    success: 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300',
    warning: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    danger: 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300',
    neutral: 'bg-muted text-muted-foreground',
};

function formatDateTime(value?: string | null): string {
    if (!value) return '-';
    return new Date(value).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatDate(value?: string | null): string {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function isCompletedStep(idx: number): boolean { return currentStepIndex.value > idx; }
function isCurrentStep(idx: number): boolean { return currentStepIndex.value === idx; }
function isFutureStep(idx: number): boolean { return currentStepIndex.value < idx; }

async function copyToClipboard(text: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch { /* clipboard API not available */ }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Research', href: researchIndex() },
            { title: 'Paper Details', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="paper.title" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Hero Header -->
        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold text-foreground md:text-2xl">{{ paper.title }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ paper.student?.name ?? 'Unknown student' }}
                        <span v-if="paper.school_class"> · {{ paper.school_class.name }}</span>
                        · Submitted {{ formatDate(paper.submission_date ?? paper.created_at) }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-950/30 dark:text-orange-300">
                            {{ paper.step_label ?? stepLabel(paper.current_step) }}
                        </span>
                        <span class="text-xs text-muted-foreground">{{ progressPercent }}% complete</span>
                    </div>
                </div>
                <span class="shrink-0 rounded-full border border-border bg-muted px-3 py-1 font-mono text-xs font-bold text-foreground">
                    {{ paper.tracking_id }}
                </span>
            </div>
        </section>

        <!-- QR Code Panel -->
        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="flex items-start gap-3 p-4">
                <!-- QR image -->
                <div
                    :class="['relative shrink-0 cursor-pointer rounded-xl border-2 bg-white p-2 transition hover:border-orange-400', qrMode === 'receiving' ? 'border-teal-400' : 'border-border']"
                    @click="qrModalOpen = true"
                    title="Click to enlarge"
                >
                    <QrcodeVue :value="qrMode === 'tracking' ? trackingUrl : receivingUrl" :size="72" level="M" />
                    <span class="absolute right-1 bottom-1 rounded bg-black/30 p-0.5">
                        <Maximize2 class="h-2.5 w-2.5 text-white" />
                    </span>
                </div>

                <!-- Right: label + toggle (top) + URL (bottom) -->
                <div class="min-w-0 flex-1 space-y-2">
                    <!-- Label row + toggle -->
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-foreground">
                                {{ qrMode === 'tracking' ? 'Tracking QR' : 'Receiving QR' }}
                            </span>
                            <span v-if="qrMode === 'receiving'" class="rounded-full border border-teal-200 bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-700 dark:border-teal-900/40 dark:bg-teal-950/20 dark:text-teal-400">Admin-only</span>
                        </div>
                        <div class="flex shrink-0 rounded-lg border border-border bg-muted p-0.5">
                            <button
                                type="button"
                                @click="qrMode = 'tracking'"
                                :class="[
                                    'flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold transition',
                                    qrMode === 'tracking' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                <Link2 class="h-3 w-3" /> Track
                            </button>
                            <button
                                type="button"
                                @click="qrMode = 'receiving'"
                                :class="[
                                    'flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold transition',
                                    qrMode === 'receiving' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                <PackageCheck class="h-3 w-3" /> Receive
                            </button>
                        </div>
                    </div>
                    <!-- URL row -->
                    <div class="flex items-center gap-2">
                        <code class="min-w-0 flex-1 truncate rounded-lg bg-muted px-3 py-1.5 font-mono text-xs text-muted-foreground">
                            {{ qrMode === 'tracking' ? trackingUrl : receivingUrl }}
                        </code>
                        <button type="button" @click="copyToClipboard(qrMode === 'tracking' ? trackingUrl : receivingUrl)" class="shrink-0 rounded-lg border border-border bg-background p-1.5 text-foreground transition hover:bg-muted">
                            <ClipboardCopy class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p v-if="copied" class="text-xs text-green-600">Copied!</p>
                </div>
            </div>
        </section>

        <!-- Body Grid -->
        <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <!-- Left: Step Details + Admin Actions + History + Comments -->
            <div class="space-y-6">
                <!-- Step Management -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950/30">
                            <ShieldCheck class="h-4 w-4 text-orange-500" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-foreground">Step Management</h2>
                            <p class="text-xs text-muted-foreground">Click <Pencil class="inline h-3 w-3" /> to manage any step</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="(detail, idx) in stepDetails"
                            :key="detail.key"
                            :class="[
                                'relative overflow-hidden rounded-xl border transition-all',
                                managingStep === detail.key
                                    ? 'border-orange-400 ring-2 ring-orange-200 dark:border-orange-600 dark:ring-orange-900/40'
                                    : isCurrentStep(idx)
                                      ? 'border-orange-300 bg-orange-50/50 dark:border-orange-800 dark:bg-orange-950/20'
                                      : isCompletedStep(idx)
                                        ? 'border-green-200 bg-green-50/30 dark:border-green-900 dark:bg-green-950/10'
                                        : 'border-border bg-card',
                            ]"
                        >
                            <div v-if="isCurrentStep(idx)" class="absolute top-0 right-0 rounded-bl-lg bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white uppercase">
                                Current
                            </div>

                            <!-- Step header row -->
                            <div class="flex items-start gap-3 p-4">
                                <div
                                    :class="[
                                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                        isCompletedStep(idx) ? 'bg-green-100 text-green-600 dark:bg-green-950/40 dark:text-green-400'
                                            : isCurrentStep(idx) ? 'bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400'
                                              : 'bg-muted text-muted-foreground',
                                    ]"
                                >
                                    <Check v-if="isCompletedStep(idx)" class="h-4 w-4" />
                                    <component :is="detail.icon" v-else class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-foreground">{{ stepLabel(detail.key) }}</span>
                                        <span v-if="!isFutureStep(idx)" :class="['rounded-full px-2 py-0.5 text-[10px] font-semibold', statusTypeClasses[detail.statusType]]">
                                            {{ detail.status }}
                                        </span>
                                    </div>
                                    <p v-if="detail.info && !isFutureStep(idx)" class="mt-1 text-xs text-muted-foreground">{{ detail.info }}</p>
                                    <p v-if="isFutureStep(idx)" class="mt-1 text-xs text-muted-foreground">Waiting for previous steps to complete.</p>
                                </div>
                                <!-- Manage button (not for title_proposal or completed) -->
                                <button
                                    v-if="detail.key !== 'title_proposal' && detail.key !== 'completed'"
                                    type="button"
                                    @click="toggleManageStep(detail.key)"
                                    :class="[
                                        'shrink-0 rounded-lg border p-1.5 transition',
                                        managingStep === detail.key
                                            ? 'border-orange-300 bg-orange-100 text-orange-600 dark:border-orange-700 dark:bg-orange-950/40 dark:text-orange-400'
                                            : 'border-border bg-background text-muted-foreground hover:bg-muted hover:text-foreground',
                                    ]"
                                    :title="managingStep === detail.key ? 'Close' : 'Manage step'"
                                >
                                    <X v-if="managingStep === detail.key" class="h-3.5 w-3.5" />
                                    <Pencil v-else class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Expandable manage panel -->
                            <div v-if="managingStep === detail.key" class="border-t border-border bg-muted/30 p-4">
                                <div class="space-y-3">
                                    <!-- Notes -->
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Notes</label>
                                        <textarea
                                            v-model="stepForm.notes"
                                            rows="2"
                                            class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                            placeholder="Optional notes for tracking record..."
                                        />
                                    </div>

                                    <!-- Plagiarism-specific: score -->
                                    <div v-if="detail.key === 'plagiarism_check'" class="grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Plagiarism Score</label>
                                            <input v-model="stepForm.plagiarism_score" type="number" min="0" max="100" step="0.01" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                                        </div>
                                        <div class="flex items-center rounded-xl border border-border bg-muted px-3 py-2 text-xs font-semibold text-foreground">
                                            Attempts: {{ paper.plagiarism_attempts ?? 0 }} / 3
                                        </div>
                                    </div>

                                    <!-- Schedule (outline_defense, final_defense) -->
                                    <div v-if="detail.key === 'outline_defense' || detail.key === 'final_defense'">
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Schedule</label>
                                        <input v-model="stepForm.schedule" type="datetime-local" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                                    </div>

                                    <!-- Grade (rating) -->
                                    <div v-if="detail.key === 'rating'">
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Grade</label>
                                        <input v-model="stepForm.grade" type="number" min="1" max="100" step="0.01" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30" />
                                    </div>

                                    <!-- Status action buttons -->
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Set Status</label>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                v-for="opt in stepStatusOptions[detail.key] ?? []"
                                                :key="opt.value"
                                                type="button"
                                                @click="submitStepFor(detail.key, opt.value)"
                                                :disabled="stepForm.processing"
                                                :class="['inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition disabled:opacity-50', opt.color]"
                                            >
                                                {{ opt.label }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tracking History -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <Clock3 class="h-4 w-4 text-orange-500" />
                        <h2 class="text-base font-bold text-foreground">Tracking History</h2>
                        <span v-if="timeline.length" class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground">
                            {{ timeline.length }}
                        </span>
                    </div>

                    <div v-if="timeline.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                        No tracking records yet.
                    </div>

                    <div v-else class="relative ml-3 space-y-0 border-l-2 border-border pl-6">
                        <div v-for="(record, idx) in timeline" :key="record.id" class="relative pb-6 last:pb-0">
                            <div :class="['absolute -left-[31px] flex h-4 w-4 items-center justify-center rounded-full border-2 border-background', idx === 0 ? 'bg-orange-500' : 'bg-green-500']">
                                <div class="h-1.5 w-1.5 rounded-full bg-white" />
                            </div>
                            <div class="rounded-xl border border-border bg-card p-3.5 shadow-xs">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-foreground">{{ record.action }}</p>
                                    <span v-if="record.step" :class="['rounded-full px-2 py-0.5 text-[10px] font-semibold', getStepBadgeClass(record.step)]">
                                        {{ record.step ? stepLabel(record.step) : '' }}
                                    </span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span v-if="record.status" class="inline-flex items-center gap-1">
                                        <span class="font-medium text-foreground">Status:</span> {{ record.status }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="font-medium text-foreground">By:</span> {{ record.updated_by?.name ?? 'System' }}
                                    </span>
                                    <span>{{ formatDateTime(record.created_at) }}</span>
                                </div>
                                <p v-if="record.notes" class="mt-2 rounded-lg bg-muted/50 px-3 py-2 text-xs text-foreground">{{ record.notes }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Comments -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <MessageSquare class="h-4 w-4 text-violet-500" />
                        <h2 class="text-base font-bold text-foreground">Comments</h2>
                        <span v-if="(comments ?? []).length" class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground">
                            {{ (comments ?? []).length }}
                        </span>
                    </div>

                    <form class="mt-2" @submit.prevent="submitComment">
                        <textarea
                            v-model="commentForm.body"
                            rows="3"
                            class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                            placeholder="Write a comment..."
                        />
                        <p v-if="commentForm.errors.body" class="mt-1 text-xs text-red-500">{{ commentForm.errors.body }}</p>
                        <div class="mt-2 flex justify-end">
                            <button
                                type="submit"
                                :disabled="commentForm.processing || !commentForm.body.trim()"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 disabled:opacity-50"
                            >
                                <Send class="h-3.5 w-3.5" /> Post Comment
                            </button>
                        </div>
                    </form>

                    <div v-if="(comments ?? []).length === 0" class="mt-3 rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                        No comments yet. Be the first to comment.
                    </div>

                    <div v-else class="mt-4 space-y-3">
                        <div v-for="comment in comments" :key="comment.id" class="rounded-xl border border-border/60 bg-muted/30 px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-[10px] font-bold text-orange-600 dark:bg-orange-950/40 dark:text-orange-300">
                                        {{ comment.user?.name.charAt(0).toUpperCase() ?? '?' }}
                                    </div>
                                    <p class="text-sm font-semibold text-foreground">{{ comment.user?.name ?? 'Unknown' }}</p>
                                </div>
                                <span class="text-[11px] text-muted-foreground">{{ timeAgo(comment.created_at) }}</span>
                            </div>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-foreground">{{ comment.body }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                <!-- Adviser & Statistician Assignment (admin-specific) -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <h3 class="mb-3 flex items-center gap-2 text-base font-bold text-foreground">
                        <UserPlus class="h-5 w-5 text-orange-500" />
                        Adviser & Statistician
                    </h3>
                    <form class="space-y-3" @submit.prevent="submitAssignment">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Adviser</label>
                            <FormSelect v-model="assignmentForm.adviser_id">
                                <option value="">None</option>
                                <option v-for="faculty in facultyUsers" :key="faculty.id" :value="faculty.id">{{ faculty.name }}</option>
                            </FormSelect>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Statistician</label>
                            <FormSelect v-model="assignmentForm.statistician_id">
                                <option value="">None</option>
                                <option v-for="staff in staffUsers" :key="staff.id" :value="staff.id">{{ staff.name }}</option>
                            </FormSelect>
                        </div>
                        <button type="submit" :disabled="assignmentForm.processing" class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 disabled:opacity-50">
                            Save Assignments
                        </button>
                    </form>
                </section>

                <!-- Paper Info -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <h3 class="mb-4 flex items-center gap-2 text-base font-bold text-foreground">
                        <FileSearch class="h-5 w-5 text-orange-500" />
                        Paper Info
                    </h3>

                    <!-- Abstract — separate visual block -->
                    <div v-if="paper.abstract" class="mb-4 rounded-xl border border-border bg-muted/30 p-4">
                        <p class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Abstract</p>
                        <p class="text-sm leading-relaxed text-foreground">{{ paper.abstract }}</p>
                    </div>

                    <!-- Key details in a definition-list style grid -->
                    <div class="divide-y divide-border">
                        <div v-if="proponents.length" class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Proponents</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="name in proponents" :key="name" class="inline-flex items-center rounded-full border border-border bg-muted/50 px-2.5 py-0.5 text-xs font-medium text-foreground">{{ name }}</span>
                            </div>
                        </div>

                        <div v-if="paper.student" class="flex items-start justify-between gap-2 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Student</p>
                            <p class="text-right text-sm text-foreground">
                                {{ paper.student.name }}
                                <span v-if="paper.student.email" class="block text-xs text-muted-foreground">{{ paper.student.email }}</span>
                            </p>
                        </div>

                        <div v-if="paper.adviser" class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Adviser</p>
                            <p class="text-sm text-foreground">{{ paper.adviser.name }}</p>
                        </div>

                        <div v-if="paper.statistician" class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Statistician</p>
                            <p class="text-sm text-foreground">{{ paper.statistician.name }}</p>
                        </div>

                        <div v-if="paper.school_class" class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Class</p>
                            <p class="text-sm text-foreground">
                                {{ paper.school_class.name }}
                                <span v-if="paper.school_class.section" class="text-muted-foreground"> · Section {{ paper.school_class.section }}</span>
                            </p>
                        </div>

                        <div v-if="paper.keywords" class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Keywords</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="keyword in (typeof paper.keywords === 'string' ? paper.keywords.split(',') : paper.keywords)" :key="typeof keyword === 'string' ? keyword.trim() : keyword" class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-950/40 dark:text-orange-300">
                                    {{ typeof keyword === 'string' ? keyword.trim() : keyword }}
                                </span>
                            </div>
                        </div>

                        <div v-if="paper.sdg_ids?.length" class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">SDGs</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="id in paper.sdg_ids" :key="id" class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/40 dark:text-blue-400">
                                    {{ sdgMap[id] ? (sdgMap[id].number ? `SDG ${sdgMap[id].number}: ${sdgMap[id].name}` : sdgMap[id].name) : `SDG ${id}` }}
                                </span>
                            </div>
                        </div>

                        <div v-if="paper.agenda_ids?.length" class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0">
                            <p class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">Research Agendas</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="id in paper.agenda_ids" :key="id" class="rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-950/40 dark:text-violet-400">
                                    {{ agendaMap[id]?.name ?? `Agenda ${id}` }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Schedules — visually distinct callout -->
                    <div v-if="paper.outline_defense_schedule || paper.final_defense_schedule" class="mt-4 rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 dark:border-blue-900/40 dark:bg-blue-950/20">
                        <p class="mb-2 text-[11px] font-semibold tracking-wide text-blue-600 uppercase dark:text-blue-400">Schedules</p>
                        <div class="space-y-1.5">
                            <p v-if="paper.outline_defense_schedule" class="flex items-center gap-2 text-xs text-foreground">
                                <Clock3 class="h-3.5 w-3.5 shrink-0 text-blue-500" />
                                <span class="font-medium">Outline Defense:</span> {{ formatDateTime(paper.outline_defense_schedule) }}
                            </p>
                            <p v-if="paper.final_defense_schedule" class="flex items-center gap-2 text-xs text-foreground">
                                <CalendarClock class="h-3.5 w-3.5 shrink-0 text-cyan-500" />
                                <span class="font-medium">Final Defense:</span> {{ formatDateTime(paper.final_defense_schedule) }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Plagiarism Warning Alert -->
                <section
                    v-if="paper.current_step === 'plagiarism_check' && (paper.plagiarism_attempts ?? 0) >= 2"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/20"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div>
                            <p class="text-sm font-bold text-amber-700 dark:text-amber-300">Plagiarism Check Warning</p>
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                Student has used {{ paper.plagiarism_attempts }} of 3 attempts.
                                <template v-if="(paper.plagiarism_attempts ?? 0) >= 3">No more attempts remaining.</template>
                                <template v-else>1 attempt remaining.</template>
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- QR Full-Page Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="qrModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
                @click.self="qrModalOpen = false"
            >
                <div class="relative flex w-full max-w-sm flex-col items-center gap-6 rounded-2xl border border-border bg-card p-8 shadow-2xl">
                    <!-- Close -->
                    <button
                        type="button"
                        class="absolute top-3 right-3 rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                        @click="qrModalOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <!-- Title -->
                    <div class="text-center">
                        <h2 class="text-base font-bold text-foreground">
                            {{ qrMode === 'tracking' ? 'Tracking QR Code' : 'Receiving QR Code' }}
                        </h2>
                        <p class="mt-0.5 font-mono text-xs text-muted-foreground">{{ paper.tracking_id }}</p>
                    </div>

                    <!-- QR -->
                    <div :class="['rounded-2xl border-4 bg-white p-4', qrMode === 'receiving' ? 'border-teal-400' : 'border-orange-400']">
                        <QrcodeVue :value="qrMode === 'tracking' ? trackingUrl : receivingUrl" :size="220" level="H" />
                    </div>

                    <!-- Toggle -->
                    <div class="flex shrink-0 rounded-lg border border-border bg-muted p-0.5">
                        <button
                            type="button"
                            @click="qrMode = 'tracking'"
                            :class="[
                                'flex items-center gap-1.5 rounded-md px-4 py-1.5 text-sm font-semibold transition',
                                qrMode === 'tracking' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <Link2 class="h-3.5 w-3.5" /> Tracking
                        </button>
                        <button
                            type="button"
                            @click="qrMode = 'receiving'"
                            :class="[
                                'flex items-center gap-1.5 rounded-md px-4 py-1.5 text-sm font-semibold transition',
                                qrMode === 'receiving' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                            ]"
                        >
                            <PackageCheck class="h-3.5 w-3.5" /> Receive
                        </button>
                    </div>

                    <!-- URL + copy -->
                    <div class="flex w-full items-center gap-2">
                        <code class="min-w-0 flex-1 truncate rounded-lg bg-muted px-3 py-2 font-mono text-xs text-muted-foreground">
                            {{ qrMode === 'tracking' ? trackingUrl : receivingUrl }}
                        </code>
                        <button
                            type="button"
                            @click="copyToClipboard(qrMode === 'tracking' ? trackingUrl : receivingUrl)"
                            class="shrink-0 rounded-lg border border-border bg-background p-2 text-foreground transition hover:bg-muted"
                        >
                            <ClipboardCopy class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="copied" class="text-xs text-green-600">Copied!</p>

                    <span v-if="qrMode === 'receiving'" class="rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 dark:border-teal-900/40 dark:bg-teal-950/20 dark:text-teal-400">
                        Admin-only · Receiving QR
                    </span>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
