<script setup lang="ts">
import { CalendarDays, Flag, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    canShowOnCalendar,
    slugFromLabel,
    statusButtonClass,
    uniqueSlug,
    WORKFLOW_INPUT_TYPE_LABELS,
    WORKFLOW_INPUT_TYPES,
    WORKFLOW_STATUS_COLOR_LABELS,
    WORKFLOW_STATUS_COLORS,
    WORKFLOW_STATUS_SWATCH_CLASSES
    
    
    
    
    
} from '@/lib/workflow-step-config';
import type {WorkflowInputOption, WorkflowInputType, WorkflowStatusColor, WorkflowStatusOption, WorkflowStepSetup} from '@/lib/workflow-step-config';

const props = defineProps<{
    stepKey: string;
    errors?: Record<string, string>;
}>();

const config = defineModel<WorkflowStepSetup>({ required: true });

const selectedStatus = ref<number | null>(
    config.value.statuses.length > 0 ? 0 : null,
);
const selectedInput = ref<number | null>(
    config.value.inputs.length > 0 ? 0 : null,
);

function errorFor(suffix: string): string | undefined {
    return props.errors?.[suffix];
}

function takenStatusValues(except?: number): string[] {
    return config.value.statuses
        .filter((_, index) => index !== except)
        .map((status) => status.value);
}

function takenInputKeys(except?: number): string[] {
    return config.value.inputs
        .filter((_, index) => index !== except)
        .map((input) => input.key);
}

function addStatus(): void {
    const next: WorkflowStatusOption = {
        value: uniqueSlug('new_status', takenStatusValues(), 'status'),
        label: 'New status',
        color: 'muted',
        completes: config.value.statuses.every((status) => !status.completes),
    };

    config.value = {
        ...config.value,
        statuses: [...config.value.statuses, next],
    };
    selectedStatus.value = config.value.statuses.length - 1;
}

function removeStatus(index: number): void {
    config.value = {
        ...config.value,
        statuses: config.value.statuses.filter((_, i) => i !== index),
    };

    if (selectedStatus.value === index) {
        selectedStatus.value = config.value.statuses.length
            ? Math.min(index, config.value.statuses.length - 1)
            : null;
    } else if (selectedStatus.value !== null && selectedStatus.value > index) {
        selectedStatus.value -= 1;
    }
}

function updateStatusLabel(index: number, label: string): void {
    const status = config.value.statuses[index];

    if (!status) {
        return;
    }

    const previous = slugFromLabel(status.label);
    status.label = label;

    if (status.value === '' || status.value === previous) {
        status.value = uniqueSlug(label, takenStatusValues(index), 'status');
    }
}

function setStatusColor(index: number, color: WorkflowStatusColor): void {
    const status = config.value.statuses[index];

    if (status) {
        status.color = color;
    }
}

function toggleCompletes(index: number): void {
    const status = config.value.statuses[index];

    if (status) {
        status.completes = !status.completes;
    }
}

function addInput(): void {
    const next: WorkflowInputOption = {
        key: uniqueSlug('custom_field', takenInputKeys(), 'field'),
        label: 'Custom field',
        type: 'text',
        show_on_calendar: false,
    };

    config.value = {
        ...config.value,
        inputs: [...config.value.inputs, next],
    };
    selectedInput.value = config.value.inputs.length - 1;
}

function removeInput(index: number): void {
    config.value = {
        ...config.value,
        inputs: config.value.inputs.filter((_, i) => i !== index),
    };

    if (selectedInput.value === index) {
        selectedInput.value = config.value.inputs.length
            ? Math.min(index, config.value.inputs.length - 1)
            : null;
    } else if (selectedInput.value !== null && selectedInput.value > index) {
        selectedInput.value -= 1;
    }
}

function updateInputLabel(index: number, label: string): void {
    const input = config.value.inputs[index];

    if (!input) {
        return;
    }

    const previous = slugFromLabel(input.label);
    input.label = label;

    if (input.key === '' || input.key === previous) {
        input.key = uniqueSlug(label, takenInputKeys(index), 'field');
    }
}

function setInputType(index: number, type: WorkflowInputType): void {
    const input = config.value.inputs[index];

    if (!input) {
        return;
    }

    input.type = type;

    if (!canShowOnCalendar(type)) {
        input.show_on_calendar = false;
    }
}

function toggleShowOnCalendar(index: number): void {
    const input = config.value.inputs[index];

    if (!input || !canShowOnCalendar(input.type)) {
        return;
    }

    input.show_on_calendar = !input.show_on_calendar;
}

function inputPreviewType(type: WorkflowInputType): string {
    if (type === 'datetime') {
        return 'datetime-local';
    }

    if (type === 'number' || type === 'date') {
        return type;
    }

    return 'text';
}
</script>

<template>
    <div class="space-y-6">
        <p class="text-xs text-muted-foreground">
            Notes stay on every manage panel. Status buttons and fields below
            are what staff see when they update this step.
        </p>

        <div>
            <div class="mb-3 flex items-center justify-between gap-2">
                <div>
                    <h3
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Status buttons
                    </h3>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Tap a button to edit it. Flag the one that finishes this
                        step.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="min-h-9"
                    @click="addStatus"
                >
                    <Plus class="h-3.5 w-3.5" />
                    Add status
                </Button>
            </div>
            <InputError :message="errorFor('config.statuses')" />

            <div
                v-if="config.statuses.length === 0"
                class="rounded-xl border border-dashed border-border px-3 py-6 text-center text-sm text-muted-foreground"
            >
                No status buttons yet. Add Approved, Pending, or any label you
                need.
            </div>

            <div v-else class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(status, statusIndex) in config.statuses"
                        :key="`preview-${status.value}-${statusIndex}`"
                        type="button"
                        :class="[
                            'inline-flex min-h-10 items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition',
                            statusButtonClass(status.color),
                            selectedStatus === statusIndex
                                ? 'ring-2 ring-orange-400 ring-offset-2 ring-offset-background'
                                : 'opacity-90 hover:opacity-100',
                        ]"
                        @click="selectedStatus = statusIndex"
                    >
                        {{ status.label || 'Untitled' }}
                        <Flag
                            v-if="status.completes"
                            class="h-3.5 w-3.5"
                            aria-hidden="true"
                        />
                    </button>
                </div>

                <div
                    v-if="
                        selectedStatus !== null &&
                        config.statuses[selectedStatus]
                    "
                    class="rounded-xl border border-border bg-background p-3"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <Label
                                :for="`${stepKey}-status-label-${selectedStatus}`"
                            >
                                Button label
                            </Label>
                            <Input
                                :id="`${stepKey}-status-label-${selectedStatus}`"
                                class="mt-1.5"
                                :model-value="
                                    config.statuses[selectedStatus].label
                                "
                                maxlength="80"
                                @update:model-value="
                                    (value) =>
                                        updateStatusLabel(
                                            selectedStatus!,
                                            String(value),
                                        )
                                "
                            />
                            <InputError
                                :message="
                                    errorFor(
                                        `config.statuses.${selectedStatus}.label`,
                                    ) ??
                                    errorFor(
                                        `config.statuses.${selectedStatus}.value`,
                                    )
                                "
                            />
                        </div>
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium transition"
                            :class="
                                config.statuses[selectedStatus].completes
                                    ? 'border-teal-300 bg-teal-50 text-teal-800 dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-200'
                                    : 'border-border bg-muted/40 text-muted-foreground'
                            "
                            @click="toggleCompletes(selectedStatus)"
                        >
                            <Flag class="h-3.5 w-3.5" />
                            {{
                                config.statuses[selectedStatus].completes
                                    ? 'Finishes this step'
                                    : 'Does not finish'
                            }}
                        </button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="h-11 w-11 text-muted-foreground hover:text-destructive"
                            @click="removeStatus(selectedStatus)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span class="sr-only">Remove status</span>
                        </Button>
                    </div>
                    <div class="mt-3">
                        <p
                            class="mb-2 text-xs font-medium text-muted-foreground"
                        >
                            Color
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="color in WORKFLOW_STATUS_COLORS"
                                :key="color"
                                type="button"
                                :title="WORKFLOW_STATUS_COLOR_LABELS[color]"
                                :class="[
                                    'size-8 rounded-full border-2 transition',
                                    WORKFLOW_STATUS_SWATCH_CLASSES[color],
                                    config.statuses[selectedStatus].color ===
                                    color
                                        ? 'ring-2 ring-orange-400 ring-offset-2 ring-offset-background'
                                        : 'opacity-70 hover:opacity-100',
                                ]"
                                @click="setStatusColor(selectedStatus, color)"
                            >
                                <span class="sr-only">{{
                                    WORKFLOW_STATUS_COLOR_LABELS[color]
                                }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between gap-2">
                <div>
                    <h3
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Custom inputs
                    </h3>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Extra fields on the manage panel, such as a grade or
                        reviewer name.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="min-h-9"
                    @click="addInput"
                >
                    <Plus class="h-3.5 w-3.5" />
                    Add input
                </Button>
            </div>
            <InputError :message="errorFor('config.inputs')" />

            <div
                v-if="config.inputs.length === 0"
                class="rounded-xl border border-dashed border-border px-3 py-6 text-center text-sm text-muted-foreground"
            >
                No extra fields. Add one if this step needs more than notes and
                a status.
            </div>

            <div v-else class="space-y-2">
                <button
                    v-for="(input, inputIndex) in config.inputs"
                    :key="`input-chip-${input.key}-${inputIndex}`"
                    type="button"
                    :class="[
                        'flex w-full items-center justify-between gap-3 rounded-xl border px-3 py-2.5 text-left transition',
                        selectedInput === inputIndex
                            ? 'border-orange-300 bg-orange-50/70 ring-2 ring-orange-400/20 dark:border-orange-800 dark:bg-orange-950/30'
                            : 'border-border bg-background hover:bg-muted/40',
                    ]"
                    @click="selectedInput = inputIndex"
                >
                    <span class="min-w-0">
                        <span
                            class="block truncate text-sm font-semibold text-foreground"
                        >
                            {{ input.label || 'Untitled field' }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ WORKFLOW_INPUT_TYPE_LABELS[input.type] }}
                            <span v-if="input.show_on_calendar">
                                · Calendar
                            </span>
                        </span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <CalendarDays
                            v-if="input.show_on_calendar"
                            class="h-3.5 w-3.5 text-teal-600 dark:text-teal-400"
                            aria-hidden="true"
                        />
                        <span
                            class="rounded-md bg-muted px-2 py-0.5 text-[11px] font-semibold text-muted-foreground"
                        >
                            {{ WORKFLOW_INPUT_TYPE_LABELS[input.type] }}
                        </span>
                    </span>
                </button>

                <div
                    v-if="
                        selectedInput !== null && config.inputs[selectedInput]
                    "
                    class="rounded-xl border border-border bg-background p-3"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <Label
                                :for="`${stepKey}-input-label-${selectedInput}`"
                            >
                                Field label
                            </Label>
                            <Input
                                :id="`${stepKey}-input-label-${selectedInput}`"
                                class="mt-1.5"
                                :model-value="
                                    config.inputs[selectedInput].label
                                "
                                maxlength="80"
                                @update:model-value="
                                    (value) =>
                                        updateInputLabel(
                                            selectedInput!,
                                            String(value),
                                        )
                                "
                            />
                            <InputError
                                :message="
                                    errorFor(
                                        `config.inputs.${selectedInput}.label`,
                                    ) ??
                                    errorFor(
                                        `config.inputs.${selectedInput}.key`,
                                    )
                                "
                            />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="h-11 w-11 text-muted-foreground hover:text-destructive"
                            @click="removeInput(selectedInput)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span class="sr-only">Remove input</span>
                        </Button>
                    </div>

                    <div class="mt-3">
                        <p
                            class="mb-2 text-xs font-medium text-muted-foreground"
                        >
                            Type
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="type in WORKFLOW_INPUT_TYPES"
                                :key="type"
                                type="button"
                                :class="[
                                    'min-h-9 rounded-lg border px-3 text-xs font-semibold transition',
                                    config.inputs[selectedInput].type === type
                                        ? 'border-orange-300 bg-orange-50 text-orange-800 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-200'
                                        : 'border-border bg-muted/40 text-muted-foreground hover:text-foreground',
                                ]"
                                @click="setInputType(selectedInput, type)"
                            >
                                {{ WORKFLOW_INPUT_TYPE_LABELS[type] }}
                            </button>
                        </div>
                    </div>

                    <button
                        v-if="
                            canShowOnCalendar(config.inputs[selectedInput].type)
                        "
                        type="button"
                        class="mt-3 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium transition sm:w-auto"
                        :class="
                            config.inputs[selectedInput].show_on_calendar
                                ? 'border-teal-300 bg-teal-50 text-teal-800 dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-200'
                                : 'border-border bg-muted/40 text-muted-foreground'
                        "
                        @click="toggleShowOnCalendar(selectedInput)"
                    >
                        <CalendarDays class="h-3.5 w-3.5" />
                        {{
                            config.inputs[selectedInput].show_on_calendar
                                ? 'Shown on calendar'
                                : 'Not on calendar'
                        }}
                    </button>

                    <div class="mt-3">
                        <Label class="text-muted-foreground">Preview</Label>
                        <textarea
                            v-if="
                                config.inputs[selectedInput].type === 'textarea'
                            "
                            disabled
                            rows="2"
                            class="mt-1.5 w-full rounded-xl border border-input bg-muted/30 px-3 py-2 text-sm text-muted-foreground"
                            :placeholder="config.inputs[selectedInput].label"
                        />
                        <input
                            v-else
                            disabled
                            :type="
                                inputPreviewType(
                                    config.inputs[selectedInput].type,
                                )
                            "
                            class="mt-1.5 w-full rounded-xl border border-input bg-muted/30 px-3 py-2 text-sm text-muted-foreground"
                            :placeholder="config.inputs[selectedInput].label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
