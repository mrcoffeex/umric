export const WORKFLOW_STATUS_COLORS = [
    'muted',
    'teal',
    'amber',
    'violet',
    'indigo',
    'emerald',
    'destructive',
] as const;

export type WorkflowStatusColor = (typeof WORKFLOW_STATUS_COLORS)[number];

export const WORKFLOW_INPUT_TYPES = [
    'text',
    'textarea',
    'number',
    'date',
    'datetime',
] as const;

export type WorkflowInputType = (typeof WORKFLOW_INPUT_TYPES)[number];

export type WorkflowStatusOption = {
    value: string;
    label: string;
    color: WorkflowStatusColor;
    completes: boolean;
};

export type WorkflowInputOption = {
    key: string;
    label: string;
    type: WorkflowInputType;
    show_on_calendar: boolean;
};

export type WorkflowStepSetup = {
    statuses: WorkflowStatusOption[];
    inputs: WorkflowInputOption[];
};

export const WORKFLOW_STATUS_COLOR_CLASSES: Record<
    WorkflowStatusColor,
    string
> = {
    muted: 'bg-muted text-foreground',
    teal: 'bg-teal-500 text-white hover:bg-teal-600',
    amber: 'bg-amber-500 text-white hover:bg-amber-600',
    violet: 'bg-violet-600 text-white hover:bg-violet-700',
    indigo: 'bg-indigo-500 text-white hover:bg-indigo-600',
    emerald: 'bg-emerald-500 text-white hover:bg-emerald-600',
    destructive:
        'bg-destructive text-destructive-foreground hover:bg-destructive/90',
};

export const WORKFLOW_STATUS_COLOR_LABELS: Record<WorkflowStatusColor, string> =
    {
        muted: 'Muted',
        teal: 'Teal',
        amber: 'Amber',
        violet: 'Violet',
        indigo: 'Indigo',
        emerald: 'Emerald',
        destructive: 'Red',
    };

export const WORKFLOW_STATUS_SWATCH_CLASSES: Record<
    WorkflowStatusColor,
    string
> = {
    muted: 'bg-muted border-border',
    teal: 'bg-teal-500 border-teal-600',
    amber: 'bg-amber-500 border-amber-600',
    violet: 'bg-violet-600 border-violet-700',
    indigo: 'bg-indigo-500 border-indigo-600',
    emerald: 'bg-emerald-500 border-emerald-600',
    destructive: 'bg-destructive border-destructive',
};

export function slugFromLabel(label: string): string {
    return label
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .slice(0, 64);
}

export function uniqueSlug(
    label: string,
    taken: Iterable<string>,
    fallback = 'item',
): string {
    const used = new Set(taken);
    const base = slugFromLabel(label) || fallback;

    if (!used.has(base)) {
        return base;
    }

    let n = 2;

    while (used.has(`${base}_${n}`)) {
        n++;
    }

    return `${base}_${n}`;
}

export const WORKFLOW_INPUT_TYPE_LABELS: Record<WorkflowInputType, string> = {
    text: 'Text',
    textarea: 'Long text',
    number: 'Number',
    date: 'Date',
    datetime: 'Date and time',
};

export function emptyStepSetup(): WorkflowStepSetup {
    return { statuses: [], inputs: [] };
}

export function cloneStepSetup(
    setup?: WorkflowStepSetup | null,
): WorkflowStepSetup {
    return {
        statuses: (setup?.statuses ?? []).map((status) => ({ ...status })),
        inputs: (setup?.inputs ?? []).map((input) => ({
            ...input,
            show_on_calendar: Boolean(input.show_on_calendar),
        })),
    };
}

export function canShowOnCalendar(type: WorkflowInputType): boolean {
    return type === 'datetime' || type === 'date';
}

export function statusButtonClass(color: string): string {
    if (color in WORKFLOW_STATUS_COLOR_CLASSES) {
        return WORKFLOW_STATUS_COLOR_CLASSES[color as WorkflowStatusColor];
    }

    return WORKFLOW_STATUS_COLOR_CLASSES.muted;
}
