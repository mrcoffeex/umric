<script setup lang="ts">
import { Head, Link, setLayoutProps, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BookCheck,
    Check,
    CheckCircle2,
    ClipboardCopy,
    Clock3,
    FileBarChart2,
    FileSearch,
    GraduationCap,
    Pencil,
    ScrollText,
    Send,
    Shield,
    Trophy,
} from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, ref } from 'vue';
import { getStepBadgeClass } from '@/lib/step-colors';
import student from '@/routes/student';

interface StepRecord {
    id: number;
    step: string | null;
    action: string;
    status: string | null;
    notes?: string | null;
    updated_by?: { id: number; name: string } | null;
    created_at?: string | null;
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
    user_id?: number | null;
    adviser?: { id: number; name: string } | null;
    statistician?: { id: number; name: string } | null;
    school_class?: { id: number; name: string; section?: string | null } | null;
}

interface Props {
    paper: Paper;
    trackingLog?: StepRecord[];
    stepLabels: Record<string, string>;
    steps: string[];
    sdgs: Array<{ id: number; name: string; number?: number }>;
    agendas: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const page = usePage();
const authUserId = computed(() => (page.props.auth as { user: { id: number } }).user.id);
const canEdit = computed(
    () => props.paper.user_id === authUserId.value && props.paper.current_step === 'title_proposal',
);

const sdgMap = computed(() =>
    Object.fromEntries(props.sdgs.map((s) => [s.id, s])),
);
const agendaMap = computed(() =>
    Object.fromEntries(props.agendas.map((a) => [a.id, a])),
);

const copied = ref(false);

const trackingUrl = computed(() => {
    return `${window.location.origin}/track/${props.paper.tracking_id}`;
});

const currentStepIndex = computed(() => {
    return props.steps.indexOf(props.paper.current_step);
});

const completedSteps = computed(() => currentStepIndex.value);

const progressPercent = computed(() => {
    if (props.steps.length <= 1) {
        return 0;
    }

    return Math.round((completedSteps.value / (props.steps.length - 1)) * 100);
});

const proponents = computed(() => {
    if (!props.paper.proponents) {
        return [];
    }

    if (Array.isArray(props.paper.proponents)) {
        return props.paper.proponents.map((p) =>
            typeof p === 'string' ? p : p.name,
        );
    }

    return props.paper.proponents
        .split(',')
        .map((v) => v.trim())
        .filter(Boolean);
});

const timeline = computed(() => {
    return [...(props.trackingLog ?? [])].sort((a, b) => {
        const left = new Date(a.created_at ?? '').getTime();
        const right = new Date(b.created_at ?? '').getTime();

        return left - right;
    });
});

const stepDetails = computed(() => {
    const p = props.paper;

    return [
        {
            key: 'title_proposal',
            icon: Send,
            status: 'Submitted',
            statusType: 'success' as const,
            info: null,
        },
        {
            key: 'ric_review',
            icon: Shield,
            status: statusLabel(p.step_ric_review),
            statusType: statusType(
                p.step_ric_review,
                ['approved'],
                ['rejected'],
            ),
            info: null,
        },
        {
            key: 'plagiarism_check',
            icon: FileSearch,
            status: statusLabel(p.step_plagiarism),
            statusType: statusType(p.step_plagiarism, ['passed'], ['failed']),
            info: `Attempts: ${p.plagiarism_attempts ?? 0} / 3${p.plagiarism_score != null ? ` · Score: ${p.plagiarism_score}%` : ''}`,
        },
        {
            key: 'outline_defense',
            icon: BookCheck,
            status: statusLabel(p.step_outline_defense),
            statusType: statusType(
                p.step_outline_defense,
                ['passed'],
                ['re_defense'],
            ),
            info: p.outline_defense_schedule
                ? `Scheduled: ${formatDateTime(p.outline_defense_schedule)}`
                : null,
        },
        {
            key: 'rating',
            icon: FileBarChart2,
            status:
                p.step_rating === 'rated' && p.grade != null
                    ? `Rated — ${p.grade}`
                    : statusLabel(p.step_rating),
            statusType: statusType(p.step_rating, ['rated'], []),
            info: null,
        },
        {
            key: 'final_manuscript',
            icon: ScrollText,
            status: statusLabel(p.step_final_manuscript),
            statusType: statusType(p.step_final_manuscript, ['submitted'], []),
            info: null,
        },
        {
            key: 'final_defense',
            icon: GraduationCap,
            status: statusLabel(p.step_final_defense),
            statusType: statusType(
                p.step_final_defense,
                ['passed'],
                ['re_defense'],
            ),
            info: p.final_defense_schedule
                ? `Scheduled: ${formatDateTime(p.final_defense_schedule)}`
                : null,
        },
        {
            key: 'hard_bound',
            icon: Trophy,
            status: statusLabel(p.step_hard_bound),
            statusType: statusType(p.step_hard_bound, ['submitted'], []),
            info: null,
        },
        {
            key: 'completed',
            icon: CheckCircle2,
            status: p.current_step === 'completed' ? 'Completed' : 'Pending',
            statusType:
                p.current_step === 'completed'
                    ? ('success' as const)
                    : ('neutral' as const),
            info: null,
        },
    ];
});

function statusLabel(value?: string | null): string {
    if (!value) {
        return 'Pending';
    }

    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusType(
    value: string | null | undefined,
    successValues: string[],
    dangerValues: string[],
): 'success' | 'warning' | 'danger' | 'neutral' {
    if (!value || value === 'pending') {
        return 'neutral';
    }

    if (successValues.includes(value)) {
        return 'success';
    }

    if (dangerValues.includes(value)) {
        return 'danger';
    }

    return 'warning';
}

const statusTypeClasses: Record<string, string> = {
    success:
        'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300',
    warning:
        'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    danger: 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300',
    neutral: 'bg-muted text-muted-foreground',
};

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

function formatDate(value?: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function isCompletedStep(idx: number): boolean {
    return currentStepIndex.value > idx;
}

function isCurrentStep(idx: number): boolean {
    return currentStepIndex.value === idx;
}

function isFutureStep(idx: number): boolean {
    return currentStepIndex.value < idx;
}

async function copyToClipboard(text: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        // clipboard API not available
    }
}

setLayoutProps({
    breadcrumbs: [
        { title: 'My Research', href: student.research.index() },
        { title: props.paper.title, href: student.research.show(props.paper.id) },
    ],
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
            <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold text-foreground md:text-2xl">
                        {{ paper.title }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Submitted
                        {{
                            formatDate(
                                paper.submission_date ?? paper.created_at,
                            )
                        }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-950/30 dark:text-orange-300"
                        >
                            {{
                                paper.step_label ??
                                stepLabel(paper.current_step)
                            }}
                        </span>
                        <span class="text-xs text-muted-foreground"
                            >{{ progressPercent }}% complete</span
                        >
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Link
                        v-if="canEdit"
                        :href="student.research.edit.url(paper.id)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-orange-300 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100 dark:border-orange-800 dark:bg-orange-950/30 dark:text-orange-400 dark:hover:bg-orange-950/50"
                    >
                        <Pencil class="h-3 w-3" />
                        Edit
                    </Link>
                    <span
                        class="rounded-full border border-border bg-muted px-3 py-1 font-mono text-xs font-bold text-foreground"
                    >
                        {{ paper.tracking_id }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Body Grid -->
        <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <!-- Left: Step Details + History -->
            <div class="space-y-6">
                <!-- Step-by-Step Status -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <h2 class="mb-4 text-base font-bold text-foreground">
                        Step Details
                    </h2>

                    <div class="space-y-3">
                        <div
                            v-for="(detail, idx) in stepDetails"
                            :key="detail.key"
                            :class="[
                                'relative overflow-hidden rounded-xl border p-4 transition-all',
                                isCurrentStep(idx)
                                    ? 'border-orange-300 bg-orange-50/50 dark:border-orange-800 dark:bg-orange-950/20'
                                    : isCompletedStep(idx)
                                      ? 'border-green-200 bg-green-50/30 dark:border-green-900 dark:bg-green-950/10'
                                      : 'border-border bg-card',
                            ]"
                        >
                            <!-- Current step indicator -->
                            <div
                                v-if="isCurrentStep(idx)"
                                class="absolute top-0 right-0 rounded-bl-lg bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white uppercase"
                            >
                                Current
                            </div>

                            <div class="flex items-start gap-3">
                                <!-- Step icon -->
                                <div
                                    :class="[
                                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                        isCompletedStep(idx)
                                            ? 'bg-green-100 text-green-600 dark:bg-green-950/40 dark:text-green-400'
                                            : isCurrentStep(idx)
                                              ? 'bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400'
                                              : 'bg-muted text-muted-foreground',
                                    ]"
                                >
                                    <Check
                                        v-if="isCompletedStep(idx)"
                                        class="h-4 w-4"
                                    />
                                    <component
                                        :is="detail.icon"
                                        v-else
                                        class="h-4 w-4"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="text-sm font-bold text-foreground"
                                            >{{ stepLabel(detail.key) }}</span
                                        >
                                        <span
                                            v-if="!isFutureStep(idx)"
                                            :class="[
                                                'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                statusTypeClasses[
                                                    detail.statusType
                                                ],
                                            ]"
                                        >
                                            {{ detail.status }}
                                        </span>
                                    </div>

                                    <p
                                        v-if="detail.info && !isFutureStep(idx)"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ detail.info }}
                                    </p>

                                    <p
                                        v-if="isFutureStep(idx)"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Waiting for previous steps to complete.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tracking History -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <Clock3 class="h-4 w-4 text-orange-500" />
                        <h2 class="text-base font-bold text-foreground">
                            Tracking History
                        </h2>
                        <span
                            v-if="timeline.length"
                            class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                        >
                            {{ timeline.length }}
                        </span>
                    </div>

                    <div
                        v-if="timeline.length === 0"
                        class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                    >
                        No tracking records yet.
                    </div>

                    <!-- Timeline -->
                    <div
                        v-else
                        class="relative ml-3 space-y-0 border-l-2 border-border pl-6"
                    >
                        <div
                            v-for="(record, idx) in timeline"
                            :key="record.id"
                            class="relative pb-6 last:pb-0"
                        >
                            <!-- Timeline dot -->
                            <div
                                :class="[
                                    'absolute -left-[31px] flex h-4 w-4 items-center justify-center rounded-full border-2 border-background',
                                    idx === timeline.length - 1
                                        ? 'bg-orange-500'
                                        : 'bg-green-500',
                                ]"
                            >
                                <div
                                    class="h-1.5 w-1.5 rounded-full bg-white"
                                />
                            </div>

                            <!-- Content card -->
                            <div
                                class="rounded-xl border border-border bg-card p-3.5 shadow-xs"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        {{ record.action }}
                                    </p>
                                    <span
                                        v-if="record.step"
                                        :class="[
                                            'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                            getStepBadgeClass(record.step),
                                        ]"
                                    >
                                        {{
                                            record.step
                                                ? stepLabel(record.step)
                                                : ''
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="record.status"
                                        class="inline-flex items-center gap-1"
                                    >
                                        <span
                                            class="font-medium text-foreground"
                                            >Status:</span
                                        >
                                        {{ record.status }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <span
                                            class="font-medium text-foreground"
                                            >By:</span
                                        >
                                        {{
                                            record.updated_by?.name ?? 'System'
                                        }}
                                    </span>
                                    <span>{{
                                        formatDateTime(record.created_at)
                                    }}</span>
                                </div>

                                <p
                                    v-if="record.notes"
                                    class="mt-2 rounded-lg bg-muted/50 px-3 py-2 text-xs text-foreground"
                                >
                                    {{ record.notes }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                <!-- QR Code & Tracking -->
                <section class="rounded-2xl border border-border bg-card p-5">
                    <h3 class="mb-4 text-base font-bold text-foreground">
                        QR Code
                    </h3>
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="rounded-xl border border-border bg-white p-3"
                        >
                            <QrcodeVue
                                :value="trackingUrl"
                                :size="160"
                                level="M"
                            />
                        </div>
                        <p class="text-center text-xs text-muted-foreground">
                            Scan to view public tracking page
                        </p>
                    </div>

                    <div class="my-4 border-t border-border" />

                    <p
                        class="mb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Tracking Link
                    </p>
                    <div class="flex items-center gap-2">
                        <code
                            class="min-w-0 flex-1 truncate rounded-lg bg-muted px-3 py-2 font-mono text-xs text-muted-foreground"
                        >
                            {{ trackingUrl }}
                        </code>
                        <button
                            type="button"
                            @click="copyToClipboard(trackingUrl)"
                            class="shrink-0 rounded-lg border border-border bg-background p-2 text-foreground transition hover:bg-muted"
                        >
                            <ClipboardCopy class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p v-if="copied" class="mt-1 text-xs text-green-600">
                        Copied!
                    </p>
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
                                <span v-for="keyword in paper.keywords.split(',')" :key="keyword.trim()" class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-950/40 dark:text-orange-300">
                                    {{ keyword.trim() }}
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
                </section>

                <!-- Alerts -->
                <section
                    v-if="
                        paper.current_step === 'plagiarism_check' &&
                        (paper.plagiarism_attempts ?? 0) >= 2
                    "
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/20"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle
                            class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <div>
                            <p
                                class="text-sm font-bold text-amber-700 dark:text-amber-300"
                            >
                                Plagiarism Check Warning
                            </p>
                            <p
                                class="mt-1 text-xs text-amber-600 dark:text-amber-400"
                            >
                                You have used {{ paper.plagiarism_attempts }} of
                                3 attempts.
                                <template
                                    v-if="(paper.plagiarism_attempts ?? 0) >= 3"
                                    >No more attempts remaining.</template
                                >
                                <template v-else>1 attempt remaining.</template>
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
