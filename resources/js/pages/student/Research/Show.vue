<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import {
    BookCheck,
    Check,
    MessageSquare,
    CheckCircle2,
    ClipboardCopy,
    Clock3,
    FileBarChart2,
    FileSearch,
    GraduationCap,
    Maximize2,
    Pencil,
    ScrollText,
    Paperclip,
    Send,
    Shield,
    Trophy,
    Users,
    X,
} from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, ref } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    firstPendingWorkflowStepIndex,
    isWorkflowStepSatisfied,
    workflowProgressPercent,
    workflowFocusStepKey,
} from '@/lib/research-workflow-ui';
import type { WorkflowStepKey } from '@/lib/research-workflow-ui';
import { getStepBadgeClass } from '@/lib/step-colors';
import student from '@/routes/student';

interface StepRecord {
    id: string;
    step: string | null;
    action: string;
    status: string | null;
    notes?: string | null;
    updated_by?: { id: string; name: string } | null;
    created_at?: string | null;
}

interface PaperFile {
    id: string;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    file_category?: string | null;
    disk: string;
    url?: string | null;
    created_at?: string;
}

interface Paper {
    id: string;
    title: string;
    abstract?: string | null;
    tracking_id: string;
    status: string;
    current_step: string;
    step_label?: string;
    submission_date?: string | null;
    created_at: string;
    keywords?: string | null;
    sdg_ids?: string[] | null;
    agenda_ids?: string[] | null;
    proponents?: string[] | Array<{ id: string; name: string }> | string | null;
    step_ric_review?: string | null;
    step_outline_defense?: string | null;
    outline_defense_schedule?: string | null;
    step_data_gathering?: string | null;
    step_rating?: string | null;
    grade?: number | null;
    step_final_manuscript?: string | null;
    step_final_defense?: string | null;
    final_defense_schedule?: string | null;
    step_hard_bound?: string | null;
    user_id?: string | null;
    adviser?: { id: string; name: string } | null;
    statistician?: { id: string; name: string } | null;
    school_class?: { id: string; name: string; section?: string | null } | null;
    files?: PaperFile[] | null;
}

interface DefenseDocumentUpload {
    mayManage: boolean;
    ricReturned: boolean;
    outline: boolean;
    final: boolean;
    /** True once a student-submitted file already exists for the matching defense type. */
    outlineUploaded: boolean;
    finalUploaded: boolean;
}

interface Props {
    paper: Paper;
    /** Absolute public tracking link (Laravel `url()`); used instead of `window` for SSR. */
    trackingPublicUrl?: string;
    /** Mirrors ResearchPaper::mayStudentUploadDefenseDocument (RIC returned, or matching defense step). */
    defenseDocumentUpload?: DefenseDocumentUpload;
    trackingLog?: StepRecord[];
    stepLabels: Record<string, string>;
    steps: string[];
    sdgs: Array<{ id: string; name: string; number?: number }>;
    agendas: Array<{ id: string; name: string }>;
    panelDefenses?: PanelDefenseRecord[];
    comments?: Comment[];
}

interface Comment {
    id: string;
    body: string;
    user: { id: string; name: string } | null;
    created_at: string;
}

interface PanelDefenseRecord {
    id: string;
    defense_type: 'title' | 'outline' | 'final';
    defense_type_label: string;
    panel_members: string[];
    schedule: string | null;
    notes: string | null;
    created_by: { id: string; name: string } | null;
    created_at: string;
}

const props = defineProps<Props>();

const page = usePage();
const authUserId = computed(() => {
    const auth = page.props.auth as
        | { user?: { id?: string | number } }
        | undefined;
    const id = auth?.user?.id;

    if (id == null) {
        return '';
    }

    return String(id);
});
const canEdit = computed(() => {
    if (props.paper.current_step !== 'title_proposal') {
        return false;
    }

    if (props.paper.user_id === authUserId.value) {
        return true;
    }

    const proponents = props.paper.proponents;

    if (!Array.isArray(proponents)) {
        return false;
    }

    return proponents.some(
        (p) => typeof p === 'object' && p.id === authUserId.value,
    );
});

/** Set on the server (same rules as the upload endpoint) so the UI can’t get out of sync. */
const defenseUpload = computed((): DefenseDocumentUpload => {
    return (
        props.defenseDocumentUpload ?? {
            mayManage: false,
            ricReturned: false,
            outline: false,
            final: false,
            outlineUploaded: false,
            finalUploaded: false,
        }
    );
});

/** Whether the outline-defense upload window is currently open (status-wise). */
const outlineWindowActive = computed(
    () =>
        defenseUpload.value.ricReturned ||
        props.paper.current_step === 'outline_defense',
);

/** Whether the final-defense upload window is currently open (status-wise). */
const finalWindowActive = computed(
    () =>
        defenseUpload.value.ricReturned ||
        props.paper.current_step === 'final_defense',
);

/**
 * Defense file uploads live in the Files tab, never on the read-only Step details rows.
 * Visible whenever a defense window is active, whether the slot is still open or already used —
 * the latter shows a “Submitted” acknowledgement so the student isn’t left wondering.
 */
const showDefenseUploadBanner = computed(
    () =>
        defenseUpload.value.mayManage &&
        (outlineWindowActive.value || finalWindowActive.value),
);

/** Files tab is visible if there are existing files OR the upload is currently allowed. */
const filesTabVisible = computed(
    () =>
        Boolean((props.paper.files ?? []).length) ||
        showDefenseUploadBanner.value,
);

/** Reason chip shown next to the upload area so the student knows why it’s open. */
const defenseUploadReason = computed(() => {
    if (defenseUpload.value.ricReturned) {
        return 'RIC review returned';
    }

    if (props.paper.current_step === 'outline_defense') {
        return 'Outline defense step';
    }

    if (props.paper.current_step === 'final_defense') {
        return 'Final defense step';
    }

    return 'Defense documents';
});

const hasPanelDefenses = computed(() => (props.panelDefenses ?? []).length > 0);

const rightSidebarDefaultTab = computed(() =>
    hasPanelDefenses.value ? 'panels' : 'paper',
);

const defenseDocForm = useForm<{
    defense: 'outline' | 'final';
    file: File | null;
}>({
    defense: 'outline',
    file: null,
});

function uploadDefenseFile(which: 'outline' | 'final', e: Event): void {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    defenseDocForm.clearErrors();
    defenseDocForm.defense = which;
    defenseDocForm.file = file;
    defenseDocForm.post(
        student.research.defenseDocuments.store.url({ paper: props.paper.id }),
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                input.value = '';
            },
        },
    );
}

function fileCategoryLabel(category?: string | null): string {
    if (!category || category === 'paper') {
        return 'Title proposal document';
    }

    if (category === 'outline_defense') {
        return 'Research document — outline defense';
    }

    if (category === 'final_defense') {
        return 'Research document — final defense';
    }

    return category.replace(/_/g, ' ');
}

const sdgMap = computed(() =>
    Object.fromEntries(props.sdgs.map((s) => [s.id, s])),
);
const agendaMap = computed(() =>
    Object.fromEntries(props.agendas.map((a) => [a.id, a])),
);

const copied = ref(false);
const qrModalOpen = ref(false);
const previewingFileId = ref<string | null>(null);

function togglePreview(fileId: string) {
    previewingFileId.value = previewingFileId.value === fileId ? null : fileId;
}

const trackingUrl = computed(() => {
    if (props.trackingPublicUrl) {
        return props.trackingPublicUrl;
    }

    if (typeof window === 'undefined') {
        return '';
    }

    return `${window.location.origin}/track/${props.paper.tracking_id}`;
});

/** First step that is still waiting (pending) — drives “current” and progress. */
const workflowFocusIndex = computed(() =>
    firstPendingWorkflowStepIndex(props.paper),
);

const focusStepKey = computed(() => workflowFocusStepKey(props.paper));

const progressPercent = computed(() => workflowProgressPercent(props.paper));

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
        return (
            new Date(b.created_at ?? '').getTime() -
            new Date(a.created_at ?? '').getTime()
        );
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
                ['rejected', 'returned'],
            ),
            info: null,
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
            key: 'data_gathering',
            icon: FileSearch,
            status: statusLabel(p.step_data_gathering),
            statusType: statusType(p.step_data_gathering, ['completed'], []),
            info: null,
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

function fileUrl(file: PaperFile): string {
    return file.disk === 's3' ? (file.url ?? '') : `/storage/${file.file_path}`;
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) {
        return '0 Bytes';
    }

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

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

function isCompletedStepForKey(key: string): boolean {
    return isWorkflowStepSatisfied(props.paper, key as WorkflowStepKey);
}

function isCurrentStep(idx: number): boolean {
    return idx === workflowFocusIndex.value;
}

function isFutureStep(idx: number): boolean {
    return idx > workflowFocusIndex.value;
}

function timeAgo(value: string): string {
    const seconds = Math.floor((Date.now() - new Date(value).getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days}d ago`;
    }

    return formatDateTime(value);
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
        {
            title: props.paper.title,
            href: student.research.show(props.paper.id),
        },
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
                            {{ stepLabel(focusStepKey) }}
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

        <!-- QR Code Panel (public tracking only) -->
        <section
            class="overflow-hidden rounded-2xl border border-border bg-card"
        >
            <div class="flex items-start gap-3 p-4">
                <div
                    class="relative shrink-0 cursor-pointer rounded-xl border-2 border-border bg-white p-2 transition hover:border-orange-400"
                    @click="qrModalOpen = true"
                    title="Click to enlarge"
                >
                    <QrcodeVue :value="trackingUrl" :size="72" level="M" />
                    <span
                        class="absolute right-1 bottom-1 rounded bg-black/30 p-0.5"
                    >
                        <Maximize2 class="h-2.5 w-2.5 text-white" />
                    </span>
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <span class="text-sm font-semibold text-foreground">
                            Tracking QR
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <code
                            class="min-w-0 flex-1 truncate rounded-lg bg-muted px-3 py-1.5 font-mono text-xs text-muted-foreground"
                        >
                            {{ trackingUrl }}
                        </code>
                        <button
                            type="button"
                            @click="copyToClipboard(trackingUrl)"
                            class="shrink-0 rounded-lg border border-border bg-background p-1.5 text-foreground transition hover:bg-muted"
                        >
                            <ClipboardCopy class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p v-if="copied" class="text-xs text-green-600">Copied!</p>
                </div>
            </div>
        </section>

        <!-- Body Grid -->
        <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <div>
                <Tabs default-value="steps" class="w-full">
                    <TabsList class="mb-3" aria-label="Research content">
                        <TabsTrigger value="steps">Step details</TabsTrigger>
                        <TabsTrigger value="history">
                            History
                            <span
                                v-if="timeline.length"
                                class="ml-0.5 rounded-full bg-muted px-1.5 py-0 text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ timeline.length }}
                            </span>
                        </TabsTrigger>
                        <TabsTrigger value="comments">
                            Comments
                            <span
                                v-if="(comments ?? []).length"
                                class="ml-0.5 rounded-full bg-muted px-1.5 py-0 text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ (comments ?? []).length }}
                            </span>
                        </TabsTrigger>
                        <TabsTrigger v-if="filesTabVisible" value="files">
                            Files
                            <span
                                v-if="(paper.files ?? []).length"
                                class="ml-0.5 rounded-full bg-muted px-1.5 py-0 text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ paper.files?.length }}
                            </span>
                            <span
                                v-if="showDefenseUploadBanner"
                                class="ml-0.5 rounded-full bg-amber-100 px-1.5 py-0 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                            >
                                Upload
                            </span>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="steps" class="mt-0">
                        <section
                            class="rounded-2xl border border-border bg-card p-5"
                        >
                            <h2
                                class="mb-4 text-base font-bold text-foreground"
                            >
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
                                            : isCompletedStepForKey(detail.key)
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
                                                isCompletedStepForKey(
                                                    detail.key,
                                                )
                                                    ? 'bg-green-100 text-green-600 dark:bg-green-950/40 dark:text-green-400'
                                                    : isCurrentStep(idx)
                                                      ? 'bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400'
                                                      : 'bg-muted text-muted-foreground',
                                            ]"
                                        >
                                            <Check
                                                v-if="
                                                    isCompletedStepForKey(
                                                        detail.key,
                                                    )
                                                "
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
                                                    >{{
                                                        stepLabel(detail.key)
                                                    }}</span
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
                                                v-if="
                                                    detail.info &&
                                                    !isFutureStep(idx)
                                                "
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ detail.info }}
                                            </p>

                                            <p
                                                v-if="isFutureStep(idx)"
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                Waiting for previous steps to
                                                complete.
                                            </p>

                                            <!-- Defense document uploads live in the Files tab when the rule allows -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </TabsContent>

                    <TabsContent value="history" class="mt-0">
                        <section
                            class="rounded-2xl border border-border bg-card p-5"
                            id="tracking-history"
                        >
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
                                            idx === 0
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
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <p
                                                class="text-sm font-semibold text-foreground"
                                            >
                                                {{ record.action }}
                                            </p>
                                            <span
                                                v-if="record.step"
                                                :class="[
                                                    'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                    getStepBadgeClass(
                                                        record.step,
                                                    ),
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
                                                    record.updated_by?.name ??
                                                    'System'
                                                }}
                                            </span>
                                            <span>{{
                                                formatDateTime(
                                                    record.created_at,
                                                )
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
                    </TabsContent>

                    <TabsContent value="comments" class="mt-0">
                        <section
                            class="rounded-2xl border border-border bg-card p-5"
                        >
                            <div class="mb-4 flex items-center gap-2">
                                <MessageSquare
                                    class="h-4 w-4 text-violet-500"
                                />
                                <h2 class="text-base font-bold text-foreground">
                                    Comments
                                </h2>
                                <span
                                    v-if="(comments ?? []).length"
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                >
                                    {{ (comments ?? []).length }}
                                </span>
                            </div>

                            <div
                                v-if="(comments ?? []).length === 0"
                                class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                            >
                                No comments yet.
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="comment in comments"
                                    :key="comment.id"
                                    class="rounded-xl border border-border/60 bg-muted/30 px-4 py-3"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-[10px] font-bold text-orange-600 dark:bg-orange-950/40 dark:text-orange-300"
                                            >
                                                {{
                                                    comment.user?.name
                                                        .charAt(0)
                                                        .toUpperCase() ?? '?'
                                                }}
                                            </div>
                                            <p
                                                class="text-sm font-semibold text-foreground"
                                            >
                                                {{
                                                    comment.user?.name ??
                                                    'Unknown'
                                                }}
                                            </p>
                                        </div>
                                        <span
                                            class="text-[11px] text-muted-foreground"
                                            >{{
                                                timeAgo(comment.created_at)
                                            }}</span
                                        >
                                    </div>
                                    <p
                                        class="mt-1.5 text-sm leading-relaxed whitespace-pre-line text-foreground"
                                    >
                                        {{ comment.body }}
                                    </p>
                                </div>
                            </div>
                        </section>
                    </TabsContent>

                    <TabsContent
                        v-if="filesTabVisible"
                        value="files"
                        class="mt-0"
                    >
                        <section
                            class="rounded-2xl border border-border bg-card p-5"
                        >
                            <div class="mb-4 flex items-center gap-2">
                                <svg
                                    class="h-4 w-4 shrink-0 text-orange-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
                                    />
                                </svg>
                                <h2 class="text-base font-bold text-foreground">
                                    Documents
                                </h2>
                                <span
                                    v-if="(paper.files ?? []).length"
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                    >{{ paper.files?.length }}</span
                                >
                            </div>

                            <!-- Upload research document(s) — visible when the rule allows (RIC returned, outline defense, final defense) -->
                            <div
                                v-if="showDefenseUploadBanner"
                                class="mb-5 rounded-xl border border-amber-200/80 bg-amber-50/50 p-4 dark:border-amber-800/50 dark:bg-amber-950/25"
                            >
                                <div
                                    class="mb-1 flex flex-wrap items-center gap-2"
                                >
                                    <p
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        Upload research document
                                    </p>
                                    <span
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                    >
                                        {{ defenseUploadReason }}
                                    </span>
                                </div>
                                <p class="mb-3 text-xs text-muted-foreground">
                                    PDF only. You can upload one document per
                                    defense type while the status allows it.
                                </p>
                                <div
                                    class="grid gap-4 sm:grid-cols-1 md:grid-cols-2"
                                >
                                    <div
                                        v-if="outlineWindowActive"
                                        class="rounded-lg border border-dashed border-orange-200 bg-orange-50/40 p-3 dark:border-orange-800/50 dark:bg-orange-950/20"
                                    >
                                        <p
                                            class="mb-2 text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Outline defense
                                        </p>
                                        <template
                                            v-if="defenseUpload.outlineUploaded"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 text-xs font-semibold text-foreground"
                                            >
                                                <Check
                                                    class="h-4 w-4 text-green-600 dark:text-green-400"
                                                />
                                                Submitted
                                            </div>
                                            <p
                                                class="mt-1.5 text-xs text-muted-foreground"
                                            >
                                                Your outline defense document is
                                                in this paper’s files. Only one
                                                upload is allowed per status.
                                            </p>
                                        </template>
                                        <template v-else>
                                            <div
                                                class="mb-2 flex items-center gap-1.5 text-xs font-semibold text-foreground"
                                            >
                                                <Paperclip
                                                    class="h-4 w-4 text-orange-600 dark:text-orange-400"
                                                />
                                                Upload file
                                            </div>
                                            <input
                                                :id="`files-tab-outline-attach-${paper.id}`"
                                                type="file"
                                                accept=".pdf,application/pdf"
                                                class="block w-full max-w-md cursor-pointer text-xs file:mr-2 file:cursor-pointer file:rounded-md file:border-0 file:bg-background file:px-3 file:py-2 file:font-medium file:text-foreground hover:file:bg-muted"
                                                :disabled="
                                                    defenseDocForm.processing
                                                "
                                                @change="
                                                    uploadDefenseFile(
                                                        'outline',
                                                        $event,
                                                    )
                                                "
                                            />
                                            <p
                                                v-if="
                                                    defenseDocForm.processing &&
                                                    defenseDocForm.defense ===
                                                        'outline'
                                                "
                                                class="mt-1.5 text-xs text-muted-foreground"
                                            >
                                                Uploading…
                                            </p>
                                        </template>
                                    </div>
                                    <div
                                        v-if="finalWindowActive"
                                        class="rounded-lg border border-dashed border-teal-200 bg-teal-50/40 p-3 dark:border-teal-800/50 dark:bg-teal-950/20"
                                    >
                                        <p
                                            class="mb-2 text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Final defense
                                        </p>
                                        <template
                                            v-if="defenseUpload.finalUploaded"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 text-xs font-semibold text-foreground"
                                            >
                                                <Check
                                                    class="h-4 w-4 text-green-600 dark:text-green-400"
                                                />
                                                Submitted
                                            </div>
                                            <p
                                                class="mt-1.5 text-xs text-muted-foreground"
                                            >
                                                Your final defense document is
                                                in this paper’s files. Only one
                                                upload is allowed per status.
                                            </p>
                                        </template>
                                        <template v-else>
                                            <div
                                                class="mb-2 flex items-center gap-1.5 text-xs font-semibold text-foreground"
                                            >
                                                <Paperclip
                                                    class="h-4 w-4 text-teal-600 dark:text-teal-400"
                                                />
                                                Upload file
                                            </div>
                                            <input
                                                :id="`files-tab-final-attach-${paper.id}`"
                                                type="file"
                                                accept=".pdf,application/pdf"
                                                class="block w-full max-w-md cursor-pointer text-xs file:mr-2 file:cursor-pointer file:rounded-md file:border-0 file:bg-background file:px-3 file:py-2 file:font-medium file:text-foreground hover:file:bg-muted"
                                                :disabled="
                                                    defenseDocForm.processing
                                                "
                                                @change="
                                                    uploadDefenseFile(
                                                        'final',
                                                        $event,
                                                    )
                                                "
                                            />
                                            <p
                                                v-if="
                                                    defenseDocForm.processing &&
                                                    defenseDocForm.defense ===
                                                        'final'
                                                "
                                                class="mt-1.5 text-xs text-muted-foreground"
                                            >
                                                Uploading…
                                            </p>
                                        </template>
                                    </div>
                                </div>
                                <p
                                    v-if="defenseDocForm.errors.file"
                                    class="mt-3 text-xs text-destructive"
                                >
                                    {{ defenseDocForm.errors.file }}
                                </p>
                            </div>

                            <p
                                v-if="!(paper.files ?? []).length"
                                class="text-xs text-muted-foreground"
                            >
                                No files uploaded yet.
                            </p>

                            <div v-else class="space-y-2">
                                <div
                                    v-for="file in paper.files"
                                    :key="file.id"
                                    class="overflow-hidden rounded-xl bg-muted/50 transition hover:bg-muted"
                                >
                                    <div
                                        class="flex items-center gap-3 px-4 py-3"
                                    >
                                        <svg
                                            class="h-8 w-8 shrink-0 text-red-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
                                            />
                                        </svg>
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-medium text-foreground"
                                            >
                                                {{ file.file_name }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    fileCategoryLabel(
                                                        file.file_category,
                                                    )
                                                }}
                                                ·
                                                {{
                                                    formatFileSize(
                                                        file.file_size,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex shrink-0 items-center gap-2"
                                        >
                                            <button
                                                v-if="
                                                    file.file_type ===
                                                    'application/pdf'
                                                "
                                                type="button"
                                                class="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                                :aria-expanded="
                                                    previewingFileId === file.id
                                                "
                                                :aria-controls="`file-preview-${file.id}`"
                                                @click="togglePreview(file.id)"
                                            >
                                                {{
                                                    previewingFileId === file.id
                                                        ? 'Hide preview'
                                                        : 'Preview'
                                                }}
                                            </button>
                                            <a
                                                v-if="
                                                    file.file_type ===
                                                    'application/pdf'
                                                "
                                                :href="fileUrl(file)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                            >
                                                Open
                                            </a>
                                            <a
                                                :href="fileUrl(file)"
                                                download
                                                class="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted"
                                            >
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                    <div
                                        v-if="
                                            file.file_type ===
                                                'application/pdf' &&
                                            previewingFileId === file.id
                                        "
                                        :id="`file-preview-${file.id}`"
                                        class="border-t border-border bg-background"
                                    >
                                        <iframe
                                            :src="fileUrl(file)"
                                            :title="`Preview of ${file.file_name}`"
                                            class="block h-[70vh] min-h-[420px] w-full"
                                            loading="lazy"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>
                    </TabsContent>
                </Tabs>
            </div>

            <!-- Right Sidebar -->
            <div>
                <Tabs class="w-full" :default-value="rightSidebarDefaultTab">
                    <TabsList
                        class="mb-3"
                        aria-label="Paper details and panels"
                    >
                        <TabsTrigger v-if="hasPanelDefenses" value="panels">
                            Panel management
                            <span
                                class="ml-0.5 rounded-full bg-muted px-1.5 py-0 text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ (panelDefenses ?? []).length }}
                            </span>
                        </TabsTrigger>
                        <TabsTrigger value="paper"> Paper </TabsTrigger>
                    </TabsList>

                    <TabsContent
                        v-if="hasPanelDefenses"
                        value="panels"
                        class="mt-0"
                    >
                        <section
                            class="rounded-2xl border border-border bg-card p-5"
                        >
                            <div class="mb-4 flex items-center gap-2">
                                <Users class="h-4 w-4 text-indigo-500" />
                                <h2 class="text-base font-bold text-foreground">
                                    Panel management
                                </h2>
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                >
                                    {{ (panelDefenses ?? []).length }} panel{{
                                        (panelDefenses ?? []).length === 1
                                            ? ''
                                            : 's'
                                    }}
                                </span>
                            </div>

                            <p
                                class="mb-3 rounded-lg border border-dashed border-border bg-muted/20 px-3 py-2 text-xs text-muted-foreground"
                            >
                                You can attach multiple outline or final defense
                                files under
                                <strong class="text-foreground"
                                    >Step details</strong
                                >
                                when you are on that step; each is kept on this
                                paper.
                            </p>

                            <div class="space-y-3">
                                <div
                                    v-for="pd in panelDefenses"
                                    :key="pd.id"
                                    class="rounded-xl border border-border/60 bg-muted/20 p-4"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="[
                                                'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                pd.defense_type === 'title'
                                                    ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300'
                                                    : pd.defense_type ===
                                                        'outline'
                                                      ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300'
                                                      : 'bg-teal-100 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300',
                                            ]"
                                        >
                                            {{ pd.defense_type_label }}
                                        </span>
                                        <span
                                            v-if="pd.schedule"
                                            class="text-xs text-muted-foreground"
                                            >{{
                                                formatDateTime(pd.schedule)
                                            }}</span
                                        >
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="member in pd.panel_members"
                                            :key="member"
                                            class="inline-flex items-center rounded-full border border-border bg-background px-2.5 py-0.5 text-xs font-medium text-foreground"
                                        >
                                            {{ member }}
                                        </span>
                                    </div>
                                    <p
                                        v-if="pd.notes"
                                        class="mt-2 rounded-lg bg-muted/50 px-3 py-2 text-xs text-foreground"
                                    >
                                        {{ pd.notes }}
                                    </p>
                                </div>
                            </div>
                        </section>
                    </TabsContent>

                    <TabsContent value="paper" class="mt-0">
                        <section
                            class="rounded-2xl border border-border bg-card p-5"
                        >
                            <h3
                                class="mb-4 flex items-center gap-2 text-base font-bold text-foreground"
                            >
                                <FileSearch class="h-5 w-5 text-orange-500" />
                                Paper Info
                            </h3>

                            <!-- Rationale (title proposal) — separate visual block -->
                            <div
                                v-if="paper.abstract"
                                class="mb-4 rounded-xl border border-border bg-muted/30 p-4"
                            >
                                <p
                                    class="mb-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Rationale
                                </p>
                                <p
                                    class="text-justify text-sm leading-relaxed text-foreground"
                                >
                                    {{ paper.abstract }}
                                </p>
                            </div>

                            <!-- Key details in a definition-list style grid -->
                            <div class="divide-y divide-border">
                                <div
                                    v-if="proponents.length"
                                    class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        Proponents
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="name in proponents"
                                            :key="name"
                                            class="inline-flex items-center rounded-full border border-border bg-muted/50 px-2.5 py-0.5 text-xs font-medium text-foreground"
                                            >{{ name }}</span
                                        >
                                    </div>
                                </div>

                                <div
                                    v-if="paper.adviser"
                                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        Adviser
                                    </p>
                                    <p class="text-sm text-foreground">
                                        {{ paper.adviser.name }}
                                    </p>
                                </div>

                                <div
                                    v-if="paper.statistician"
                                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        Statistician
                                    </p>
                                    <p class="text-sm text-foreground">
                                        {{ paper.statistician.name }}
                                    </p>
                                </div>

                                <div
                                    v-if="paper.school_class"
                                    class="flex items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        Class
                                    </p>
                                    <p class="text-sm text-foreground">
                                        {{ paper.school_class.name }}
                                        <span
                                            v-if="paper.school_class.section"
                                            class="text-muted-foreground"
                                        >
                                            · Section
                                            {{
                                                paper.school_class.section
                                            }}</span
                                        >
                                    </p>
                                </div>

                                <div
                                    v-if="paper.keywords"
                                    class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        Keywords
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="keyword in paper.keywords.split(
                                                ',',
                                            )"
                                            :key="keyword.trim()"
                                            class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-950/40 dark:text-orange-300"
                                        >
                                            {{ keyword.trim() }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-if="paper.sdg_ids?.length"
                                    class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        SDGs
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="id in paper.sdg_ids"
                                            :key="id"
                                            class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/40 dark:text-blue-400"
                                        >
                                            {{
                                                sdgMap[id]
                                                    ? sdgMap[id].number
                                                        ? `SDG ${sdgMap[id].number}: ${sdgMap[id].name}`
                                                        : sdgMap[id].name
                                                    : `SDG ${id}`
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-if="paper.agenda_ids?.length"
                                    class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        Research Agendas
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="id in paper.agenda_ids"
                                            :key="id"
                                            class="rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-950/40 dark:text-violet-400"
                                        >
                                            {{
                                                agendaMap[id]?.name ??
                                                `Agenda ${id}`
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </TabsContent>
                </Tabs>
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
                @click.self="qrModalOpen = false"
            >
                <div
                    class="relative flex w-full max-w-sm flex-col items-center gap-6 rounded-2xl border border-border bg-card p-8 shadow-2xl"
                >
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
                            Tracking QR Code
                        </h2>
                        <p
                            class="mt-0.5 font-mono text-xs text-muted-foreground"
                        >
                            {{ paper.tracking_id }}
                        </p>
                    </div>

                    <!-- QR -->
                    <div
                        class="rounded-2xl border-4 border-orange-400 bg-white p-4"
                    >
                        <QrcodeVue :value="trackingUrl" :size="220" level="H" />
                    </div>

                    <!-- URL + copy -->
                    <div class="flex w-full items-center gap-2">
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
                            <ClipboardCopy class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="copied" class="text-xs text-green-600">Copied!</p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
