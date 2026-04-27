<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    BookCheck,
    CalendarClock,
    Check,
    CheckCircle2,
    ClipboardCopy,
    Clock3,
    FileBarChart2,
    FileSearch,
    Globe,
    GraduationCap,
    Layers,
    Maximize2,
    MessageSquare,
    Pencil,
    Plus,
    ScrollText,
    Send,
    Shield,
    ShieldCheck,
    Target,
    Trophy,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { computed, nextTick, ref, watch } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useConfirm } from '@/composables/useConfirm';
import {
    firstPendingWorkflowStepIndex,
    isWorkflowStepSatisfied,
    workflowFocusStepKey,
    workflowProgressPercent,
} from '@/lib/research-workflow-ui';
import type { WorkflowStepKey } from '@/lib/research-workflow-ui';
import { getStepBadgeClass } from '@/lib/step-colors';
import {
    index as researchIndex,
    updateStep as updateStepRoute,
    assign as assignRoute,
    updateClassifications as updateClassificationsRoute,
    updateProponents as updateProponentsRoute,
    storeComment as storeCommentRoute,
} from '@/routes/admin/research';
import panelDefensesRoutes from '@/routes/admin/research/panel-defenses';
import { search as searchProponentsRoute } from '@/routes/papers/proponents';

interface StepRecord {
    id: string;
    step: string | null;
    action: string;
    status: string | null;
    old_status?: string | null;
    notes?: string | null;
    updated_by?: { id: string; name: string } | null;
    created_at?: string | null;
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
    evaluations_count: number;
    can_edit: boolean;
    evaluation_format: {
        id: string;
        name: string;
        evaluation_type?: 'scoring' | 'checklist';
    } | null;
    created_by: { id: string; name: string } | null;
    created_at: string;
}

interface PaperFile {
    id: string;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    disk: string;
    url?: string | null;
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
    user_id?: number | null;
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
    student?: { id: string; name: string; email?: string } | null;
    adviser?: { id: string; name: string } | null;
    statistician?: { id: string; name: string } | null;
    school_class?: { id: string; name: string; section?: string | null } | null;
    files?: PaperFile[] | null;
}

interface Props {
    paper: Paper;
    trackingRecords?: StepRecord[];
    stepLabels: Record<string, string>;
    steps: string[];
    sdgs: Array<{ id: string; name: string; number?: number; color?: string }>;
    agendas: Array<{ id: string; name: string }>;
    facultyUsers: Array<{ id: string; name: string }>;
    staffUsers: Array<{ id: string; name: string }>;
    comments?: Comment[];
    panelDefenses?: PanelDefenseRecord[];
    evaluationFormatOptions?: Array<{
        id: string;
        name: string;
        evaluation_type: 'scoring' | 'checklist';
        is_ready: boolean;
        total_max: number;
    }>;
    default_panel_evaluation_format_id?: string | null;
    panelScheduleTimeOptions?: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const copied = ref(false);
const qrModalOpen = ref(false);
const previewingFileId = ref<string | null>(null);

function togglePreview(fileId: string) {
    previewingFileId.value = previewingFileId.value === fileId ? null : fileId;
}

const trackingUrl = computed(() => {
    return `${window.location.origin}/track/${props.paper.tracking_id}`;
});

const workflowFocusIndex = computed(() =>
    firstPendingWorkflowStepIndex(props.paper),
);

const focusStepKey = computed(() => workflowFocusStepKey(props.paper));

const progressPercent = computed(() => workflowProgressPercent(props.paper));

interface ProponentSlot {
    id: string;
    name: string;
}

function initialProponentsFromPaper(p: Paper): ProponentSlot[] {
    if (
        p.proponents &&
        Array.isArray(p.proponents) &&
        p.proponents.length > 0
    ) {
        return p.proponents.map((x) => {
            if (typeof x === 'string') {
                return {
                    id: p.student ? String(p.student.id) : '',
                    name: x,
                };
            }

            return { id: String(x.id), name: x.name };
        });
    }

    if (p.student) {
        return [{ id: String(p.student.id), name: p.student.name }];
    }

    return [];
}

const proponentsForm = useForm({
    proponents: initialProponentsFromPaper(props.paper),
});

const activeProponentSearchSlot = ref<number | null>(null);
const proponentSearchQuery = ref('');
const proponentSearchResults = ref<ProponentSlot[]>([]);
const proponentSearchInput = ref<HTMLInputElement[]>([]);
let proponentSearchDebounce: ReturnType<typeof setTimeout>;

const canAddProponentSlot = computed(
    () => proponentsForm.proponents.length < 3,
);

function openProponentSearch(slotIndex: number) {
    activeProponentSearchSlot.value = slotIndex;
    proponentSearchQuery.value = '';
    proponentSearchResults.value = [];
    void nextTick(() => proponentSearchInput.value[0]?.focus());
}

function closeProponentSearch() {
    activeProponentSearchSlot.value = null;
    proponentSearchQuery.value = '';
    proponentSearchResults.value = [];
}

async function onProponentSearchInput(value: string) {
    proponentSearchQuery.value = value;
    clearTimeout(proponentSearchDebounce);

    if (value.trim().length < 2) {
        proponentSearchResults.value = [];

        return;
    }

    proponentSearchDebounce = setTimeout(async () => {
        try {
            const res = await fetch(
                searchProponentsRoute.url({ query: { q: value.trim() } }),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!res.ok) {
                proponentSearchResults.value = [];

                return;
            }

            const data: ProponentSlot[] = await res.json();
            const active = activeProponentSearchSlot.value;
            proponentSearchResults.value = data.filter((u) => {
                for (let i = 0; i < proponentsForm.proponents.length; i++) {
                    if (i === active) {
                        continue;
                    }

                    if (proponentsForm.proponents[i]?.id === u.id) {
                        return false;
                    }
                }

                return true;
            });
        } catch {
            proponentSearchResults.value = [];
        }
    }, 300);
}

function selectProponent(slot: number, result: ProponentSlot) {
    proponentsForm.proponents[slot] = {
        id: result.id,
        name: result.name,
    };
    closeProponentSearch();
}

function addProponentSlot() {
    if (proponentsForm.proponents.length >= 3) {
        return;
    }

    proponentsForm.proponents.push({ id: '', name: '' });
    openProponentSearch(proponentsForm.proponents.length - 1);
}

function removeProponent(index: number) {
    if (index === 0) {
        return;
    }

    proponentsForm.proponents.splice(index, 1);
    closeProponentSearch();
}

function submitProponents() {
    proponentsForm
        .transform((data) => ({
            proponents: data.proponents.filter((p) => p.id !== ''),
        }))
        .patch(updateProponentsRoute.url({ paper: props.paper.id }), {
            preserveScroll: true,
            onSuccess: () => {
                proponentsForm.clearErrors();
                const p = (usePage().props as unknown as { paper: Paper })
                    .paper;
                proponentsForm.proponents = initialProponentsFromPaper(p);
            },
        });
}

watch(
    () => props.paper.id,
    () => {
        proponentsForm.proponents = initialProponentsFromPaper(props.paper);
        proponentsForm.clearErrors();
        closeProponentSearch();
    },
);

const timeline = computed(() => {
    return [...(props.trackingRecords ?? [])].sort((a, b) => {
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

// --- Per-step status options ---
const stepStatusOptions: Record<
    string,
    Array<{ value: string; label: string; color: string }>
> = {
    ric_review: [
        {
            value: 'pending',
            label: 'Pending',
            color: 'bg-muted text-foreground',
        },
        {
            value: 'approved',
            label: 'Approved',
            color: 'bg-teal-500 text-white hover:bg-teal-600',
        },
        {
            value: 'returned',
            label: 'Return to student',
            color: 'bg-amber-600 text-white hover:bg-amber-700',
        },
        {
            value: 'rejected',
            label: 'Rejected',
            color: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        },
    ],
    outline_defense: [
        {
            value: 'pending',
            label: 'Pending',
            color: 'bg-muted text-foreground',
        },
        {
            value: 'passed',
            label: 'Passed',
            color: 'bg-teal-500 text-white hover:bg-teal-600',
        },
        {
            value: 're_defense',
            label: 'Re-Defense',
            color: 'bg-amber-500 text-white hover:bg-amber-600',
        },
    ],
    data_gathering: [
        {
            value: 'pending',
            label: 'Pending',
            color: 'bg-muted text-foreground',
        },
        {
            value: 'completed',
            label: 'Completed',
            color: 'bg-violet-600 text-white hover:bg-violet-700',
        },
    ],
    rating: [
        {
            value: 'pending',
            label: 'Pending',
            color: 'bg-muted text-foreground',
        },
        {
            value: 'rated',
            label: 'Rated',
            color: 'bg-amber-500 text-white hover:bg-amber-600',
        },
    ],
    final_manuscript: [
        {
            value: 'pending',
            label: 'Pending',
            color: 'bg-muted text-foreground',
        },
        {
            value: 'submitted',
            label: 'Submitted',
            color: 'bg-indigo-500 text-white hover:bg-indigo-600',
        },
    ],
    final_defense: [
        {
            value: 'pending',
            label: 'Pending',
            color: 'bg-muted text-foreground',
        },
        {
            value: 'passed',
            label: 'Passed',
            color: 'bg-teal-500 text-white hover:bg-teal-600',
        },
        {
            value: 're_defense',
            label: 'Re-Defense',
            color: 'bg-amber-500 text-white hover:bg-amber-600',
        },
    ],
    hard_bound: [
        {
            value: 'ongoing',
            label: 'Ongoing',
            color: 'bg-muted text-foreground',
        },
        {
            value: 'submitted',
            label: 'Submitted',
            color: 'bg-emerald-500 text-white hover:bg-emerald-600',
        },
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
});

const assignmentForm = useForm({
    adviser_id: props.paper.adviser?.id ?? '',
    statistician_id: props.paper.statistician?.id ?? '',
});

const commentForm = useForm({ body: '' });

const classificationsForm = useForm({
    sdg_ids: [...(props.paper.sdg_ids ?? [])],
    agenda_ids: [...(props.paper.agenda_ids ?? [])],
});

const sdgSelectOptions = computed(() =>
    props.sdgs.map((s) => ({
        value: s.id,
        label: s.number ? `SDG ${s.number}: ${s.name}` : s.name,
    })),
);

const agendaSelectOptions = computed(() =>
    props.agendas.map((a) => ({ value: a.id, label: a.name })),
);

function submitClassifications(): void {
    classificationsForm.patch(
        updateClassificationsRoute.url({ paper: props.paper.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                const p = (usePage().props as unknown as { paper: Paper })
                    .paper;
                classificationsForm.sdg_ids = [...(p.sdg_ids ?? [])];
                classificationsForm.agenda_ids = [...(p.agenda_ids ?? [])];
            },
        },
    );
}

watch(
    () => props.paper.id,
    () => {
        classificationsForm.sdg_ids = [...(props.paper.sdg_ids ?? [])];
        classificationsForm.agenda_ids = [...(props.paper.agenda_ids ?? [])];
        classificationsForm.clearErrors();
    },
);

const { confirm } = useConfirm();

const page = usePage();

const editingPanelDefenseId = ref<string | null>(null);

const panelForm = useForm({
    defense_type: 'title' as 'title' | 'outline' | 'final',
    evaluation_format_id:
        props.default_panel_evaluation_format_id ?? ('' as string),
    panel_member_ids: [] as string[],
    schedule_date: '',
    schedule_time: '',
    notes: '',
    acknowledge_schedule_conflict: false,
    /** Placeholder so Inertia maps `schedule_conflict` validation errors onto the form. */
    schedule_conflict: '',
});

function normalizeName(s: string): string {
    return s.trim().toLowerCase();
}

function panelMemberNamesToIds(names: string[]): string[] {
    const seen = new Set<string>();
    const out: string[] = [];

    for (const name of names) {
        const u = props.facultyUsers.find(
            (f) => normalizeName(f.name) === normalizeName(name),
        );

        if (u && !seen.has(u.id)) {
            seen.add(u.id);
            out.push(u.id);
        }
    }

    return out;
}

/** Parse DB `toDateTimeString()` into date + time-slot value (H:i). */
function parsePanelScheduleForForm(schedule: string | null): {
    schedule_date: string;
    schedule_time: string;
} {
    if (!schedule?.trim()) {
        return { schedule_date: '', schedule_time: '' };
    }

    const m = schedule.trim().match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})/);

    if (!m) {
        return { schedule_date: '', schedule_time: '' };
    }

    return { schedule_date: m[1], schedule_time: `${m[2]}:${m[3]}` };
}

function resetPanelFormForAdd(): void {
    editingPanelDefenseId.value = null;
    panelForm.reset();
    panelForm.defense_type = 'title';
    panelForm.evaluation_format_id =
        props.default_panel_evaluation_format_id ?? '';
    panelForm.acknowledge_schedule_conflict = false;
    panelForm.clearErrors();
}

function startEditPanel(pd: PanelDefenseRecord): void {
    if (!pd.can_edit) {
        return;
    }

    editingPanelDefenseId.value = pd.id;
    panelForm.clearErrors();
    panelForm.acknowledge_schedule_conflict = false;
    panelForm.defense_type = pd.defense_type;
    panelForm.evaluation_format_id = pd.evaluation_format?.id ?? '';
    panelForm.panel_member_ids = panelMemberNamesToIds(pd.panel_members);
    const parsed = parsePanelScheduleForForm(pd.schedule);
    panelForm.schedule_date = parsed.schedule_date;
    panelForm.schedule_time = parsed.schedule_time;
    panelForm.notes = pd.notes ?? '';
}

function cancelEditPanel(): void {
    resetPanelFormForAdd();
}

const facultyOptions = computed(() =>
    props.facultyUsers.map((u) => ({ value: u.id, label: u.name })),
);

function panelServerError(
    key:
        | 'panel_members'
        | 'schedule_conflict'
        | 'schedule_date'
        | 'schedule_time'
        | 'evaluation_format_id',
): string | undefined {
    return (panelForm.errors as Record<string, string | undefined>)[key];
}

function firstScheduleConflictError(
    err: string | string[] | undefined,
): string {
    if (err === undefined) {
        return 'This time slot is already in use. Add this record anyway?';
    }

    if (Array.isArray(err)) {
        return (
            err[0] ??
            'This time slot is already in use. Add this record anyway?'
        );
    }

    return err;
}

function formatEvaluationFormatOptionLabel(
    opt: NonNullable<Props['evaluationFormatOptions']>[number],
): string {
    if (opt.evaluation_type === 'checklist') {
        if (opt.is_ready) {
            return `${opt.name} (Checklist · ${opt.total_max} item${
                opt.total_max === 1 ? '' : 's'
            })`;
        }

        return `${opt.name} (Checklist — add items — not ready)`;
    }

    if (opt.is_ready) {
        return `${opt.name} (Scoring · ${opt.total_max}/100)`;
    }

    return `${opt.name} (Scoring — weights ${opt.total_max}/100 — not ready)`;
}

function submitPanelDefense(): void {
    const isEdit = editingPanelDefenseId.value !== null;
    const url = isEdit
        ? panelDefensesRoutes.update.url({
              paper: props.paper.id,
              panelDefense: editingPanelDefenseId.value!,
          })
        : panelDefensesRoutes.store.url({ paper: props.paper.id });

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetPanelFormForAdd();
        },
        onError: (errors: unknown) => {
            const fromVisit = errors as Record<
                string,
                string | string[] | undefined
            >;
            const fromPage = (page.props.errors ?? {}) as Record<
                string,
                string | string[] | undefined
            >;
            const raw =
                fromVisit.schedule_conflict ?? fromPage.schedule_conflict;

            if (raw === undefined || raw === null || raw === '') {
                return;
            }

            void (async () => {
                const ok = await confirm(
                    firstScheduleConflictError(
                        Array.isArray(raw) ? raw[0] : raw,
                    ),
                    {
                        title: 'Schedule time already in use',
                        confirmLabel: isEdit ? 'Save anyway' : 'Add anyway',
                        cancelLabel: 'Change schedule',
                        destructive: false,
                    },
                );

                panelForm.clearErrors();

                if (ok) {
                    panelForm.acknowledge_schedule_conflict = true;
                    submitPanelDefense();
                } else {
                    panelForm.acknowledge_schedule_conflict = false;
                }
            })();
        },
    };

    const chain = panelForm.transform((data) => ({
        defense_type: data.defense_type,
        evaluation_format_id: data.evaluation_format_id,
        panel_members: data.panel_member_ids.map(
            (id) =>
                props.facultyUsers.find((u) => u.id === id)?.name ?? String(id),
        ),
        schedule_date: data.schedule_date,
        schedule_time: data.schedule_time,
        notes: data.notes || null,
        acknowledge_schedule_conflict: data.acknowledge_schedule_conflict
            ? true
            : undefined,
    }));

    if (isEdit) {
        chain.patch(url, options);
    } else {
        chain.post(url, options);
    }
}

async function deletePanelDefense(pd: PanelDefenseRecord): Promise<void> {
    if (!pd.can_edit) {
        return;
    }

    const ok = await confirm(
        `Remove the ${pd.defense_type_label} panel defense record?`,
        { title: 'Delete Panel Defense', confirmLabel: 'Delete' },
    );

    if (!ok) {
        return;
    }

    useForm({}).delete(
        panelDefensesRoutes.destroy.url({
            paper: props.paper.id,
            panelDefense: pd.id,
        }),
        { preserveScroll: true },
    );
}

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

async function copyToClipboard(text: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        /* clipboard API not available */
    }
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
        <section
            id="research-show-overview"
            class="scroll-mt-20 overflow-hidden rounded-2xl border border-border bg-card"
        >
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="flex flex-wrap items-start justify-between gap-4 p-5">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold text-foreground md:text-2xl">
                        {{ paper.title }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ paper.student?.name ?? 'Unknown student' }}
                        <span v-if="paper.school_class">
                            · {{ paper.school_class.name }}</span
                        >
                        · Submitted
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
                <span
                    class="shrink-0 rounded-full border border-border bg-muted px-3 py-1 font-mono text-xs font-bold text-foreground"
                >
                    {{ paper.tracking_id }}
                </span>
            </div>
        </section>

        <!-- QR Code Panel (public tracking only) -->
        <section
            id="research-show-qr"
            class="scroll-mt-24 overflow-hidden rounded-2xl border border-border bg-card"
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
        <div
            class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(16rem,22rem)]"
        >
            <div class="min-w-0">
                <Tabs
                    default-value="steps"
                    class="w-full"
                    :unmount-on-hide="false"
                >
                    <TabsList
                        class="mb-3 flex flex-wrap gap-1"
                        aria-label="Admin research"
                    >
                        <TabsTrigger value="steps">Step management</TabsTrigger>
                        <TabsTrigger value="paper"> Paper details </TabsTrigger>
                        <TabsTrigger
                            v-if="paper.files && paper.files.length"
                            value="files"
                        >
                            Files
                            <span
                                class="ml-0.5 rounded-full bg-muted px-1.5 py-0 text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ paper.files.length }}
                            </span>
                        </TabsTrigger>
                        <TabsTrigger value="classifications">
                            SDG &amp; Agenda
                        </TabsTrigger>
                        <TabsTrigger value="team">
                            Adviser &amp; Statistician
                        </TabsTrigger>
                        <TabsTrigger value="panels">
                            Panels
                            <span
                                v-if="(panelDefenses ?? []).length"
                                class="ml-0.5 rounded-full bg-muted px-1.5 py-0 text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ (panelDefenses ?? []).length }}
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
                    </TabsList>
                    <TabsContent value="steps" class="mt-0">
                        <section
                            id="research-show-steps"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
                        >
                            <div class="mb-4 flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950/30"
                                >
                                    <ShieldCheck
                                        class="h-4 w-4 text-orange-500"
                                    />
                                </div>
                                <div>
                                    <h2
                                        class="text-base font-bold text-foreground"
                                    >
                                        Step Management
                                    </h2>
                                    <p class="text-xs text-muted-foreground">
                                        Click
                                        <Pencil class="inline h-3 w-3" /> to
                                        manage any step
                                    </p>
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
                                              : isCompletedStepForKey(
                                                      detail.key,
                                                  )
                                                ? 'border-green-200 bg-green-50/30 dark:border-green-900 dark:bg-green-950/10'
                                                : 'border-border bg-card',
                                    ]"
                                >
                                    <div
                                        v-if="isCurrentStep(idx)"
                                        class="absolute top-0 right-0 rounded-bl-lg bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white uppercase"
                                    >
                                        Current
                                    </div>

                                    <!-- Step header row -->
                                    <div class="flex items-start gap-3 p-4">
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
                                        </div>
                                        <!-- Manage button (not for title_proposal or completed) -->
                                        <button
                                            v-if="
                                                detail.key !==
                                                    'title_proposal' &&
                                                detail.key !== 'completed'
                                            "
                                            type="button"
                                            @click="
                                                toggleManageStep(detail.key)
                                            "
                                            :class="[
                                                'shrink-0 rounded-lg border p-1.5 transition',
                                                managingStep === detail.key
                                                    ? 'border-orange-300 bg-orange-100 text-orange-600 dark:border-orange-700 dark:bg-orange-950/40 dark:text-orange-400'
                                                    : 'border-border bg-background text-muted-foreground hover:bg-muted hover:text-foreground',
                                            ]"
                                            :title="
                                                managingStep === detail.key
                                                    ? 'Close'
                                                    : 'Manage step'
                                            "
                                        >
                                            <X
                                                v-if="
                                                    managingStep === detail.key
                                                "
                                                class="h-3.5 w-3.5"
                                            />
                                            <Pencil
                                                v-else
                                                class="h-3.5 w-3.5"
                                            />
                                        </button>
                                    </div>

                                    <!-- Expandable manage panel -->
                                    <div
                                        v-if="managingStep === detail.key"
                                        class="border-t border-border bg-muted/30 p-4"
                                    >
                                        <div class="space-y-3">
                                            <!-- Notes -->
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                                    >Notes</label
                                                >
                                                <textarea
                                                    v-model="stepForm.notes"
                                                    rows="2"
                                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                                    placeholder="Optional notes for tracking record..."
                                                />
                                            </div>

                                            <!-- Grade (rating) -->
                                            <div v-if="detail.key === 'rating'">
                                                <label
                                                    class="mb-1 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                                    >Grade</label
                                                >
                                                <input
                                                    v-model="stepForm.grade"
                                                    type="number"
                                                    min="1"
                                                    max="100"
                                                    step="0.01"
                                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                                />
                                            </div>

                                            <!-- Status action buttons -->
                                            <div>
                                                <label
                                                    class="mb-1.5 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                                    >Set Status</label
                                                >
                                                <div
                                                    class="flex flex-wrap gap-2"
                                                >
                                                    <button
                                                        v-for="opt in stepStatusOptions[
                                                            detail.key
                                                        ] ?? []"
                                                        :key="opt.value"
                                                        type="button"
                                                        @click="
                                                            submitStepFor(
                                                                detail.key,
                                                                opt.value,
                                                            )
                                                        "
                                                        :disabled="
                                                            stepForm.processing
                                                        "
                                                        :class="[
                                                            'inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition disabled:opacity-50',
                                                            opt.color,
                                                        ]"
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
                    </TabsContent>

                    <TabsContent value="comments" class="mt-0">
                        <section
                            id="research-show-comments"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
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

                            <form class="mt-2" @submit.prevent="submitComment">
                                <textarea
                                    v-model="commentForm.body"
                                    rows="3"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                    placeholder="Write a comment..."
                                />
                                <p
                                    v-if="commentForm.errors.body"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ commentForm.errors.body }}
                                </p>
                                <div class="mt-2 flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="
                                            commentForm.processing ||
                                            !commentForm.body.trim()
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 disabled:opacity-50"
                                    >
                                        <Send class="h-3.5 w-3.5" /> Post
                                        Comment
                                    </button>
                                </div>
                            </form>

                            <div
                                v-if="(comments ?? []).length === 0"
                                class="mt-3 rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                            >
                                No comments yet. Be the first to comment.
                            </div>

                            <div v-else class="mt-4 space-y-3">
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
                        v-if="paper.files && paper.files.length > 0"
                        value="files"
                        class="mt-0"
                    >
                        <section
                            id="research-show-documents"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
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
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                    >{{ paper.files.length }}</span
                                >
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="file in paper.files"
                                    :key="file.id"
                                    class="overflow-hidden rounded-xl bg-muted/50 transition hover:bg-muted"
                                >
                                    <div
                                        class="flex items-start gap-3 px-4 py-3"
                                    >
                                        <svg
                                            class="mt-0.5 h-8 w-8 shrink-0 text-red-500"
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
                                                class="min-w-0 text-sm leading-snug font-medium [overflow-wrap:anywhere] break-words text-foreground"
                                            >
                                                {{ file.file_name }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    formatFileSize(
                                                        file.file_size,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex shrink-0 items-center gap-2 self-center"
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

                    <TabsContent value="team" class="mt-0">
                        <section
                            id="research-show-assignment"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
                        >
                            <h3
                                class="mb-3 flex items-center gap-2 text-base font-bold text-foreground"
                            >
                                <UserPlus class="h-5 w-5 text-orange-500" />
                                Adviser & Statistician
                            </h3>
                            <form
                                class="space-y-3"
                                @submit.prevent="submitAssignment"
                            >
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                        >Adviser</label
                                    >
                                    <FormSelect
                                        v-model="assignmentForm.adviser_id"
                                    >
                                        <option value="">None</option>
                                        <option
                                            v-for="faculty in facultyUsers"
                                            :key="faculty.id"
                                            :value="faculty.id"
                                        >
                                            {{ faculty.name }}
                                        </option>
                                    </FormSelect>
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                        >Statistician</label
                                    >
                                    <FormSelect
                                        v-model="assignmentForm.statistician_id"
                                    >
                                        <option value="">None</option>
                                        <option
                                            v-for="faculty in facultyUsers"
                                            :key="faculty.id"
                                            :value="faculty.id"
                                        >
                                            {{ faculty.name }}
                                        </option>
                                    </FormSelect>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="assignmentForm.processing"
                                    class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 disabled:opacity-50"
                                >
                                    Save Assignments
                                </button>
                            </form>
                        </section>
                    </TabsContent>

                    <TabsContent value="panels" class="mt-0">
                        <section
                            id="research-show-panels"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
                        >
                            <div class="mb-4 flex items-center gap-2">
                                <Users class="h-4 w-4 text-indigo-500" />
                                <h2 class="text-base font-bold text-foreground">
                                    Panel Management
                                </h2>
                                <span
                                    v-if="(panelDefenses ?? []).length"
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                >
                                    {{ (panelDefenses ?? []).length }}
                                </span>
                            </div>

                            <!-- Add / edit panel form -->
                            <div
                                class="mb-5 rounded-xl border border-border bg-muted/30 p-4"
                            >
                                <p
                                    class="mb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    {{
                                        editingPanelDefenseId
                                            ? 'Edit panel defense'
                                            : 'Add panel defense record'
                                    }}
                                </p>
                                <div class="space-y-3">
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-semibold text-muted-foreground"
                                            >Defense Type</label
                                        >
                                        <FormSelect
                                            v-model="panelForm.defense_type"
                                        >
                                            <option value="title">
                                                Title Evaluation
                                            </option>
                                            <option value="outline">
                                                Outline Defense
                                            </option>
                                            <option value="final">
                                                Final Defense
                                            </option>
                                        </FormSelect>
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-semibold text-muted-foreground"
                                            >Evaluation format</label
                                        >
                                        <FormSelect
                                            v-model="
                                                panelForm.evaluation_format_id
                                            "
                                            required
                                        >
                                            <option disabled value="">
                                                Select a rubric…
                                            </option>
                                            <option
                                                v-for="opt in evaluationFormatOptions ??
                                                []"
                                                :key="opt.id"
                                                :value="opt.id"
                                                :disabled="!opt.is_ready"
                                            >
                                                {{
                                                    formatEvaluationFormatOptionLabel(
                                                        opt,
                                                    )
                                                }}
                                            </option>
                                        </FormSelect>
                                        <p
                                            v-if="
                                                panelServerError(
                                                    'evaluation_format_id',
                                                )
                                            "
                                            class="mt-1 text-xs text-destructive"
                                        >
                                            {{
                                                panelServerError(
                                                    'evaluation_format_id',
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-semibold text-muted-foreground"
                                            >Panel Members</label
                                        >
                                        <MultiSelect
                                            v-model="panelForm.panel_member_ids"
                                            :options="facultyOptions"
                                            placeholder="Select panelists…"
                                            search-placeholder="Search faculty…"
                                        />
                                        <p
                                            v-if="
                                                panelServerError(
                                                    'panel_members',
                                                )
                                            "
                                            class="mt-1 text-xs text-destructive"
                                        >
                                            {{
                                                panelServerError(
                                                    'panel_members',
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-semibold text-muted-foreground"
                                                >Defense date</label
                                            >
                                            <input
                                                v-model="
                                                    panelForm.schedule_date
                                                "
                                                type="date"
                                                required
                                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                            />
                                            <p
                                                v-if="
                                                    panelForm.errors
                                                        .schedule_date
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    panelForm.errors
                                                        .schedule_date
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-semibold text-muted-foreground"
                                                >Time slot</label
                                            >
                                            <FormSelect
                                                v-model="
                                                    panelForm.schedule_time
                                                "
                                                required
                                            >
                                                <option disabled value="">
                                                    Select a time…
                                                </option>
                                                <option
                                                    v-for="opt in panelScheduleTimeOptions ??
                                                    []"
                                                    :key="opt.value"
                                                    :value="opt.value"
                                                >
                                                    {{ opt.label }}
                                                </option>
                                            </FormSelect>
                                            <p
                                                v-if="
                                                    panelForm.errors
                                                        .schedule_time
                                                "
                                                class="mt-1 text-xs text-destructive"
                                            >
                                                {{
                                                    panelForm.errors
                                                        .schedule_time
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-semibold text-muted-foreground"
                                            >Notes (optional)</label
                                        >
                                        <textarea
                                            v-model="panelForm.notes"
                                            rows="2"
                                            class="w-full resize-none rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                                            placeholder="Any remarks…"
                                        />
                                    </div>
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <button
                                            v-if="editingPanelDefenseId"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm transition hover:bg-muted"
                                            @click="cancelEditPanel"
                                        >
                                            Cancel edit
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="
                                                !panelForm.panel_member_ids
                                                    .length ||
                                                !panelForm.evaluation_format_id ||
                                                !panelForm.schedule_date ||
                                                !panelForm.schedule_time ||
                                                panelForm.processing
                                            "
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-600 disabled:opacity-50"
                                            @click="submitPanelDefense"
                                        >
                                            <Check class="h-3.5 w-3.5" />
                                            {{
                                                editingPanelDefenseId
                                                    ? 'Update record'
                                                    : 'Save record'
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel list -->
                            <div
                                v-if="!(panelDefenses ?? []).length"
                                class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
                            >
                                No panel defense records yet.
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="pd in panelDefenses"
                                    :key="pd.id"
                                    class="rounded-xl border border-border/60 bg-muted/20 p-4"
                                >
                                    <div
                                        class="flex items-start justify-between gap-2"
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
                                            >
                                                {{
                                                    formatDateTime(pd.schedule)
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex shrink-0 items-center gap-0.5"
                                        >
                                            <button
                                                v-if="pd.can_edit"
                                                type="button"
                                                class="rounded-lg p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                                title="Edit"
                                                @click="startEditPanel(pd)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                v-if="pd.can_edit"
                                                type="button"
                                                class="rounded-lg p-1 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                                                title="Delete"
                                                @click="deletePanelDefense(pd)"
                                            >
                                                <X class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <p
                                        v-if="pd.evaluation_format"
                                        class="mt-1.5 text-xs text-muted-foreground"
                                    >
                                        Rubric:
                                        <span
                                            class="font-medium text-foreground"
                                            >{{ pd.evaluation_format.name
                                            }}{{
                                                pd.evaluation_format
                                                    .evaluation_type ===
                                                'checklist'
                                                    ? ' · Checklist'
                                                    : pd.evaluation_format
                                                            .evaluation_type ===
                                                        'scoring'
                                                      ? ' · Scoring'
                                                      : ''
                                            }}</span
                                        >
                                    </p>
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
                                    <p
                                        class="mt-2 text-[11px] text-muted-foreground"
                                    >
                                        Added by
                                        {{ pd.created_by?.name ?? 'System' }} ·
                                        {{ timeAgo(pd.created_at) }}
                                        <span
                                            v-if="
                                                !pd.can_edit &&
                                                pd.evaluations_count > 0
                                            "
                                            class="mt-1 block text-amber-700 dark:text-amber-400"
                                        >
                                            Locked: {{ pd.evaluations_count }}
                                            evaluation(s) on file — edit and
                                            delete are disabled.
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </section>
                    </TabsContent>

                    <TabsContent value="paper" class="mt-0">
                        <section
                            id="research-show-paper"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
                        >
                            <h3
                                class="mb-4 flex items-center gap-2 text-base font-bold text-foreground"
                            >
                                <FileSearch class="h-5 w-5 text-orange-500" />
                                Paper details
                            </h3>

                            <!-- Rationale — separate visual block -->
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
                                    v-if="
                                        paper.student ||
                                        proponentsForm.proponents.length
                                    "
                                    class="flex flex-col gap-2 py-3 first:pt-0 last:pb-0"
                                >
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-2"
                                    >
                                        <p
                                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                        >
                                            Proponents
                                        </p>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            class="shrink-0"
                                            :disabled="
                                                proponentsForm.processing
                                            "
                                            @click="submitProponents"
                                        >
                                            Save proponents
                                        </Button>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(
                                                proponent, idx
                                            ) in proponentsForm.proponents"
                                            :key="idx"
                                            class="flex items-center gap-2"
                                        >
                                            <span
                                                :class="[
                                                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                                    idx === 0
                                                        ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400'
                                                        : 'bg-muted text-muted-foreground',
                                                ]"
                                                >{{ idx + 1 }}</span
                                            >

                                            <template
                                                v-if="
                                                    activeProponentSearchSlot !==
                                                    idx
                                                "
                                            >
                                                <div
                                                    :class="[
                                                        'flex h-10 min-w-0 flex-1 cursor-pointer items-center rounded-lg border px-3 py-2 text-sm transition',
                                                        proponent.id
                                                            ? 'border-input bg-background text-foreground hover:border-orange-400'
                                                            : 'border-dashed border-orange-300 bg-orange-50/50 text-muted-foreground hover:border-orange-400 hover:bg-orange-50 dark:border-orange-800 dark:bg-orange-950/10 dark:hover:bg-orange-950/20',
                                                    ]"
                                                    @click="
                                                        openProponentSearch(idx)
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            idx === 0 &&
                                                            proponent.id
                                                        "
                                                        class="mr-2 shrink-0 rounded bg-orange-100 px-1.5 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-500/20 dark:text-orange-400"
                                                        >Lead</span
                                                    >
                                                    <span
                                                        v-if="proponent.id"
                                                        class="min-w-0 truncate text-foreground"
                                                        >{{
                                                            proponent.name
                                                        }}</span
                                                    >
                                                    <span
                                                        v-else
                                                        class="flex items-center gap-1.5 text-orange-600 dark:text-orange-400"
                                                    >
                                                        <Plus
                                                            class="h-3.5 w-3.5 shrink-0"
                                                        />
                                                        {{
                                                            idx === 0
                                                                ? 'Search lead student'
                                                                : 'Search co-researcher'
                                                        }}
                                                    </span>
                                                </div>
                                            </template>

                                            <template v-else>
                                                <div
                                                    class="relative min-w-0 flex-1"
                                                >
                                                    <input
                                                        ref="proponentSearchInput"
                                                        :value="
                                                            proponentSearchQuery
                                                        "
                                                        type="text"
                                                        placeholder="Name or email…"
                                                        class="h-10 w-full rounded-lg border border-orange-400 bg-background px-3 py-2 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100 dark:focus:ring-orange-500/20"
                                                        @input="
                                                            onProponentSearchInput(
                                                                (
                                                                    $event.target as HTMLInputElement
                                                                ).value,
                                                            )
                                                        "
                                                        @keydown.escape="
                                                            closeProponentSearch
                                                        "
                                                    />
                                                    <ul
                                                        v-if="
                                                            proponentSearchResults.length
                                                        "
                                                        class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-border bg-card shadow-lg"
                                                    >
                                                        <li
                                                            v-for="result in proponentSearchResults"
                                                            :key="result.id"
                                                            class="cursor-pointer px-3 py-2.5 text-sm text-foreground hover:bg-muted"
                                                            @mousedown.prevent="
                                                                selectProponent(
                                                                    idx,
                                                                    result,
                                                                )
                                                            "
                                                        >
                                                            {{ result.name }}
                                                        </li>
                                                    </ul>
                                                    <div
                                                        v-else-if="
                                                            proponentSearchQuery.trim()
                                                                .length >= 2
                                                        "
                                                        class="absolute z-20 mt-1 w-full rounded-lg border border-border bg-card px-3 py-2.5 text-xs text-muted-foreground shadow-lg"
                                                    >
                                                        No students found.
                                                    </div>
                                                </div>
                                            </template>

                                            <button
                                                v-if="idx > 0"
                                                type="button"
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-950/20"
                                                title="Remove proponent"
                                                @click="removeProponent(idx)"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                            <span
                                                v-else
                                                class="h-8 w-8 shrink-0"
                                                aria-hidden="true"
                                            />
                                        </div>

                                        <button
                                            v-if="canAddProponentSlot"
                                            type="button"
                                            class="mt-1 flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-orange-600 transition hover:bg-orange-50 hover:text-orange-700 dark:hover:bg-orange-950/20"
                                            @click="addProponentSlot"
                                        >
                                            <Plus class="h-4 w-4" />
                                            Add co-researcher
                                        </button>

                                        <InputError
                                            v-if="
                                                proponentsForm.errors.proponents
                                            "
                                            :message="
                                                proponentsForm.errors
                                                    .proponents as string
                                            "
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="paper.student"
                                    class="flex items-start justify-between gap-2 py-3 first:pt-0 last:pb-0"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                                    >
                                        Student
                                    </p>
                                    <p
                                        class="text-right text-sm text-foreground"
                                    >
                                        {{ paper.student.name }}
                                        <span
                                            v-if="paper.student.email"
                                            class="block text-xs text-muted-foreground"
                                            >{{ paper.student.email }}</span
                                        >
                                    </p>
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
                                            v-for="keyword in typeof paper.keywords ===
                                            'string'
                                                ? paper.keywords.split(',')
                                                : paper.keywords"
                                            :key="
                                                typeof keyword === 'string'
                                                    ? keyword.trim()
                                                    : keyword
                                            "
                                            class="rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-950/40 dark:text-orange-300"
                                        >
                                            {{
                                                typeof keyword === 'string'
                                                    ? keyword.trim()
                                                    : keyword
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedules — visually distinct callout -->
                            <div
                                v-if="
                                    paper.outline_defense_schedule ||
                                    paper.final_defense_schedule
                                "
                                class="mt-4 rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 dark:border-blue-900/40 dark:bg-blue-950/20"
                            >
                                <p
                                    class="mb-2 text-[11px] font-semibold tracking-wide text-blue-600 uppercase dark:text-blue-400"
                                >
                                    Schedules
                                </p>
                                <div class="space-y-1.5">
                                    <p
                                        v-if="paper.outline_defense_schedule"
                                        class="flex items-center gap-2 text-xs text-foreground"
                                    >
                                        <Clock3
                                            class="h-3.5 w-3.5 shrink-0 text-blue-500"
                                        />
                                        <span class="font-medium"
                                            >Outline Defense:</span
                                        >
                                        {{
                                            formatDateTime(
                                                paper.outline_defense_schedule,
                                            )
                                        }}
                                    </p>
                                    <p
                                        v-if="paper.final_defense_schedule"
                                        class="flex items-center gap-2 text-xs text-foreground"
                                    >
                                        <CalendarClock
                                            class="h-3.5 w-3.5 shrink-0 text-cyan-500"
                                        />
                                        <span class="font-medium"
                                            >Final Defense:</span
                                        >
                                        {{
                                            formatDateTime(
                                                paper.final_defense_schedule,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </section>
                    </TabsContent>

                    <TabsContent value="classifications" class="mt-0">
                        <section
                            id="research-show-classifications"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
                        >
                            <h3
                                class="mb-1 flex items-center gap-2 text-base font-bold text-foreground"
                            >
                                <Layers
                                    class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                                />
                                SDG &amp; research agendas
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-muted-foreground"
                            >
                                Set official tags on the research record.
                                Students do not change these on the title
                                proposal.
                            </p>
                            <div
                                class="space-y-4 rounded-xl border border-border bg-muted/20 p-4 sm:p-5"
                            >
                                <div class="min-w-0 space-y-4">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <Globe
                                                class="h-3.5 w-3.5 shrink-0 text-green-600 dark:text-green-400"
                                            />
                                            <span
                                                class="text-sm font-medium text-foreground"
                                                >SDGs</span
                                            >
                                        </div>
                                        <MultiSelect
                                            v-model="
                                                classificationsForm.sdg_ids
                                            "
                                            :options="sdgSelectOptions"
                                            search-placeholder="Search SDGs…"
                                            checkbox-accent-class="accent-orange-500"
                                            :disabled="
                                                classificationsForm.processing
                                            "
                                        />
                                        <InputError
                                            :message="
                                                classificationsForm.errors
                                                    .sdg_ids
                                            "
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <Target
                                                class="h-3.5 w-3.5 shrink-0 text-teal-600 dark:text-teal-400"
                                            />
                                            <span
                                                class="text-sm font-medium text-foreground"
                                                >Research agendas</span
                                            >
                                        </div>
                                        <MultiSelect
                                            v-model="
                                                classificationsForm.agenda_ids
                                            "
                                            :options="agendaSelectOptions"
                                            search-placeholder="Search agendas…"
                                            checkbox-accent-class="accent-orange-500"
                                            :disabled="
                                                classificationsForm.processing
                                            "
                                        />
                                        <InputError
                                            :message="
                                                classificationsForm.errors
                                                    .agenda_ids
                                            "
                                        />
                                    </div>
                                    <div class="pt-1">
                                        <Button
                                            type="button"
                                            size="sm"
                                            class="w-full sm:w-auto"
                                            :disabled="
                                                classificationsForm.processing
                                            "
                                            @click="submitClassifications"
                                        >
                                            Save SDG &amp; agendas
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </TabsContent>
                </Tabs>
            </div>

            <div class="min-w-0 xl:max-w-[22rem]">
                <Tabs
                    class="w-full"
                    default-value="history"
                    :unmount-on-hide="false"
                >
                    <TabsList class="mb-3 w-full" aria-label="Tracking history">
                        <TabsTrigger class="flex-1" value="history">
                            History
                            <span
                                v-if="timeline.length"
                                class="ml-0.5 rounded-full bg-muted px-1.5 py-0 text-[10px] font-semibold text-muted-foreground"
                            >
                                {{ timeline.length }}
                            </span>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="history" class="mt-0">
                        <section
                            id="research-show-history"
                            class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"
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

                            <div
                                v-else
                                class="relative ml-3 space-y-0 border-l-2 border-border pl-6"
                            >
                                <div
                                    v-for="(record, idx) in timeline"
                                    :key="record.id"
                                    class="relative pb-6 last:pb-0"
                                >
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
