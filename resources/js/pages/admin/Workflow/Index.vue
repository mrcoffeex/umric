<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    BookCheck,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    FileBarChart2,
    FileSearch,
    GitBranch,
    GraduationCap,
    Layers,
    Plus,
    ScrollText,
    Send,
    Settings2,
    Shield,
    Trash2,
    Trophy,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import WorkflowStepSetup from '@/components/admin/WorkflowStepSetup.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import { getStepBarClass } from '@/lib/step-colors';
import {
    cloneStepSetup,
    emptyStepSetup,
    uniqueSlug
    
} from '@/lib/workflow-step-config';
import type {WorkflowStepSetup as StepSetup} from '@/lib/workflow-step-config';
import admin from '@/routes/admin';

type WorkflowStep = {
    key: string;
    label: string;
    config: StepSetup;
};

type WorkflowData = {
    id: string;
    version: number;
    paper_count: number;
    older_paper_count: number;
    steps: WorkflowStep[];
};

const props = defineProps<{
    workflow: WorkflowData;
    templates: WorkflowStep[];
    custom_default_config: StepSetup;
    version_count: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Research steps', href: admin.workflowSteps.index.url() },
        ],
    },
});

const { confirm } = useConfirm();

const stepIcons = {
    title_proposal: Send,
    ric_review: Shield,
    outline_defense: BookCheck,
    data_gathering: FileSearch,
    rating: FileBarChart2,
    final_manuscript: ScrollText,
    final_defense: GraduationCap,
    hard_bound: Trophy,
    completed: CheckCircle2,
} as const;

function iconFor(key: string) {
    return stepIcons[key as keyof typeof stepIcons] ?? Layers;
}

function toFormStep(step: WorkflowStep): WorkflowStep {
    return {
        key: step.key,
        label: step.label,
        config: cloneStepSetup(step.config),
    };
}

const form = useForm({
    steps: props.workflow.steps.map(toFormStep),
});

const editingSetup = ref<string | null>(null);

watch(
    () => props.workflow,
    (workflow) => {
        form.steps = workflow.steps.map(toFormStep);
        form.clearErrors();
        showAdd.value = false;
        editingSetup.value = null;
    },
    { deep: true },
);

const showAdd = ref(false);
const newLabel = ref('');

const usedKeys = computed(() => new Set(form.steps.map((step) => step.key)));

const unusedTemplates = computed(() =>
    props.templates.filter((template) => !usedKeys.value.has(template.key)),
);

function moveStep(index: number, direction: -1 | 1): void {
    const target = index + direction;

    if (target < 0 || target >= form.steps.length) {
        return;
    }

    if (
        form.steps[index]?.key === 'completed' ||
        form.steps[target]?.key === 'completed'
    ) {
        return;
    }

    const next = [...form.steps];
    const [item] = next.splice(index, 1);
    next.splice(target, 0, item);
    form.steps = next;
}

async function removeStep(index: number): Promise<void> {
    const step = form.steps[index];

    if (!step || step.key === 'completed') {
        return;
    }

    const ok = await confirm(`Remove “${step.label}” from the next workflow?`, {
        title: 'Remove step',
        confirmLabel: 'Remove',
    });

    if (!ok) {
        return;
    }

    if (editingSetup.value === step.key) {
        editingSetup.value = null;
    }

    form.steps = form.steps.filter((_, i) => i !== index);
}

function addTemplate(template: WorkflowStep): void {
    insertBeforeCompleted({
        key: template.key,
        label: template.label,
        config: cloneStepSetup(template.config),
    });
    showAdd.value = false;
    newLabel.value = '';
}

function addCustom(): void {
    const label = newLabel.value.trim();

    if (!label) {
        return;
    }

    insertBeforeCompleted({
        key: uniqueSlug(label, usedKeys.value, 'step'),
        label,
        config: cloneStepSetup(props.custom_default_config),
    });
    newLabel.value = '';
    showAdd.value = false;
}

function insertBeforeCompleted(step: WorkflowStep): void {
    const next = form.steps.filter((item) => item.key !== step.key);
    const completedIndex = next.findIndex((item) => item.key === 'completed');

    if (completedIndex === -1) {
        next.push(step, {
            key: 'completed',
            label: 'Completed',
            config: emptyStepSetup(),
        });
    } else {
        next.splice(completedIndex, 0, step);
    }

    form.steps = next;
    editingSetup.value = step.key;
}

function submit(): void {
    form.put(admin.workflowSteps.update.url(), { preserveScroll: true });
}

const lastIndex = computed(() => form.steps.length - 1);

function toggleSetup(key: string): void {
    if (key === 'completed') {
        return;
    }

    editingSetup.value = editingSetup.value === key ? null : key;
}

function setupErrors(index: number): Record<string, string> {
    const prefix = `steps.${index}.`;
    const errors: Record<string, string> = {};

    for (const [field, message] of Object.entries(form.errors)) {
        if (field.startsWith(prefix) && message) {
            errors[field.slice(prefix.length)] = message;
        }
    }

    return errors;
}

function statusSummary(step: WorkflowStep): string {
    const finishing = step.config.statuses.find((status) => status.completes);

    if (finishing) {
        return `Finishes on ${finishing.label}`;
    }

    if (step.key === 'completed') {
        return 'Final milestone';
    }

    if (step.config.statuses.length === 0) {
        return 'No status buttons';
    }

    return `${step.config.statuses.length} status buttons`;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Research steps" />

        <section
            class="scroll-mt-24 rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-5"
        >
            <div
                class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30"
                    >
                        <GitBranch
                            class="h-5 w-5 text-orange-600 dark:text-orange-400"
                        />
                    </div>
                    <div class="min-w-0">
                        <h1
                            class="text-base font-bold text-foreground sm:text-lg"
                        >
                            Research steps
                        </h1>
                        <p
                            class="mt-0.5 text-xs text-muted-foreground sm:text-sm"
                        >
                            Publish a new sequence for papers submitted after
                            you save. Existing papers keep the steps they were
                            created with.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span
                        class="rounded-full bg-muted px-2.5 py-1 font-semibold text-foreground"
                    >
                        Version {{ workflow.version }}
                    </span>
                    <span
                        class="rounded-full bg-muted px-2.5 py-1 text-muted-foreground"
                    >
                        {{ workflow.paper_count }}
                        {{ workflow.paper_count === 1 ? 'paper' : 'papers' }}
                        on this version
                    </span>
                    <span
                        v-if="workflow.older_paper_count > 0"
                        class="rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-800 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        {{ workflow.older_paper_count }} on earlier versions
                    </span>
                </div>
            </div>

            <div
                class="mb-5 rounded-xl border border-orange-200/80 bg-orange-50/70 px-4 py-3 text-sm text-orange-900 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-200"
            >
                Saving always publishes a new version. Papers already in the
                system are not moved, renamed, or reordered.
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <InputError :message="form.errors.steps" />

                <ol
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
                    aria-label="Research workflow steps"
                >
                    <template
                        v-for="(step, index) in form.steps"
                        :key="`${step.key}-${index}`"
                    >
                        <li
                            v-if="step.key === 'completed'"
                            :class="
                                showAdd
                                    ? 'relative overflow-hidden rounded-2xl border border-dashed border-orange-300 bg-orange-50/40 p-4 sm:col-span-2 xl:col-span-3 dark:border-orange-800 dark:bg-orange-950/20'
                                    : 'relative flex min-h-[9.5rem] flex-col overflow-hidden rounded-2xl border border-dashed border-border bg-card'
                            "
                        >
                            <button
                                v-if="!showAdd"
                                type="button"
                                class="flex min-h-[9.5rem] flex-1 flex-col items-center justify-center gap-2 p-4 text-muted-foreground transition hover:bg-muted/40 hover:text-foreground"
                                @click="showAdd = true"
                            >
                                <span
                                    class="flex size-11 items-center justify-center rounded-xl bg-muted"
                                >
                                    <Plus class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-semibold"
                                    >Add step</span
                                >
                            </button>
                            <div v-else>
                                <h2 class="text-sm font-bold text-foreground">
                                    Add a step
                                </h2>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Pick a built-in stage or type a name. A key
                                    is created automatically.
                                </p>
                                <div
                                    v-if="unusedTemplates.length"
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    <button
                                        v-for="template in unusedTemplates"
                                        :key="template.key"
                                        type="button"
                                        class="rounded-full border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:border-orange-300 hover:bg-orange-50 dark:hover:bg-orange-950/30"
                                        @click="addTemplate(template)"
                                    >
                                        {{ template.label }}
                                    </button>
                                </div>
                                <div class="mt-4">
                                    <Label for="new-step-label"
                                        >Custom name</Label
                                    >
                                    <Input
                                        id="new-step-label"
                                        v-model="newLabel"
                                        class="mt-1.5"
                                        placeholder="Ethics review"
                                        @keydown.enter.prevent="addCustom"
                                    />
                                </div>
                                <div
                                    class="mt-3 flex flex-col gap-2 sm:flex-row"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="min-h-11"
                                        @click="showAdd = false"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="button"
                                        class="min-h-11 border-0 bg-orange-500 text-white hover:bg-orange-600"
                                        :disabled="!newLabel.trim()"
                                        @click="addCustom"
                                    >
                                        Add step
                                    </Button>
                                </div>
                            </div>
                        </li>
                        <li
                            :class="[
                                'relative flex min-h-[9.5rem] flex-col overflow-hidden rounded-2xl border transition-all duration-200',
                                editingSetup === step.key
                                    ? 'border-primary bg-card ring-2 ring-primary/25 sm:col-span-2 xl:col-span-3'
                                    : step.key === 'completed'
                                      ? 'border-green-300/80 bg-green-50/50 dark:border-green-800/70 dark:bg-green-950/25'
                                      : 'border-amber-300/80 bg-amber-50/50 dark:border-amber-800/70 dark:bg-amber-950/25',
                            ]"
                        >
                            <div
                                :class="[
                                    'absolute inset-y-0 left-0 w-1',
                                    getStepBarClass(step.key),
                                ]"
                                aria-hidden="true"
                            />

                            <div class="flex flex-1 flex-col gap-3 p-4 pl-5">
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span
                                            :class="[
                                                'flex size-11 shrink-0 items-center justify-center rounded-xl text-white shadow-sm',
                                                step.key === 'completed'
                                                    ? 'bg-green-500 shadow-green-500/20'
                                                    : editingSetup === step.key
                                                      ? 'bg-primary shadow-primary/25'
                                                      : 'bg-amber-500 shadow-amber-500/20',
                                            ]"
                                        >
                                            <component
                                                :is="iconFor(step.key)"
                                                class="h-5 w-5"
                                            />
                                        </span>
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <p
                                                class="text-[10px] font-bold tracking-[0.14em] text-muted-foreground uppercase"
                                            >
                                                Step
                                                {{
                                                    String(index + 1).padStart(
                                                        2,
                                                        '0',
                                                    )
                                                }}
                                            </p>
                                            <Label
                                                :for="`step-label-${index}`"
                                                class="sr-only"
                                            >
                                                Label
                                            </Label>
                                            <Input
                                                :id="`step-label-${index}`"
                                                v-model="step.label"
                                                maxlength="100"
                                                required
                                                :disabled="
                                                    step.key === 'completed'
                                                "
                                                class="h-8 border-transparent bg-transparent px-0 text-sm font-bold tracking-tight text-foreground shadow-none focus-visible:border-input focus-visible:bg-background focus-visible:px-2 sm:text-[15px]"
                                            />
                                            <InputError
                                                :message="
                                                    form.errors[
                                                        `steps.${index}.label`
                                                    ] ??
                                                    form.errors[
                                                        `steps.${index}.key`
                                                    ]
                                                "
                                            />
                                        </div>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-1"
                                    >
                                        <Button
                                            v-if="step.key !== 'completed'"
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-9 min-h-9 min-w-9"
                                            :class="
                                                editingSetup === step.key
                                                    ? 'border border-orange-300 bg-orange-100 text-orange-600 dark:border-orange-700 dark:bg-orange-950/40 dark:text-orange-400'
                                                    : 'border border-border bg-background text-muted-foreground hover:bg-muted hover:text-foreground'
                                            "
                                            :title="
                                                editingSetup === step.key
                                                    ? 'Close setup'
                                                    : 'Edit setup'
                                            "
                                            @click="toggleSetup(step.key)"
                                        >
                                            <X
                                                v-if="editingSetup === step.key"
                                                class="h-3.5 w-3.5"
                                            />
                                            <Settings2
                                                v-else
                                                class="h-3.5 w-3.5"
                                            />
                                            <span class="sr-only"
                                                >Edit setup</span
                                            >
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-9"
                                            :disabled="
                                                index === 0 ||
                                                step.key === 'completed'
                                            "
                                            @click="moveStep(index, -1)"
                                        >
                                            <ChevronUp class="h-4 w-4" />
                                            <span class="sr-only">Move up</span>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-9"
                                            :disabled="
                                                index >= lastIndex - 1 ||
                                                step.key === 'completed'
                                            "
                                            @click="moveStep(index, 1)"
                                        >
                                            <ChevronDown class="h-4 w-4" />
                                            <span class="sr-only"
                                                >Move down</span
                                            >
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-9 text-muted-foreground hover:text-destructive"
                                            :disabled="step.key === 'completed'"
                                            @click="removeStep(index)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            <span class="sr-only">Remove</span>
                                        </Button>
                                    </div>
                                </div>

                                <div class="mt-auto space-y-2">
                                    <span
                                        :class="[
                                            'inline-flex max-w-full items-center truncate rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                            step.key === 'completed'
                                                ? 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300'
                                                : editingSetup === step.key
                                                  ? 'bg-primary/15 text-primary'
                                                  : 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
                                        ]"
                                    >
                                        {{ statusSummary(step) }}
                                    </span>
                                    <p
                                        class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                                    >
                                        {{
                                            step.config.inputs.length
                                                ? `${step.config.inputs.length} custom ${step.config.inputs.length === 1 ? 'input' : 'inputs'}`
                                                : 'Notes only — no extra fields'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="
                                    editingSetup === step.key &&
                                    step.key !== 'completed'
                                "
                                class="border-t border-border bg-muted/30 p-4"
                            >
                                <WorkflowStepSetup
                                    v-model="step.config"
                                    :step-key="step.key"
                                    :errors="setupErrors(index)"
                                />
                            </div>
                        </li>
                    </template>
                </ol>

                <div class="flex justify-end">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-11 border-0 bg-orange-500 text-white hover:bg-orange-600"
                    >
                        {{
                            form.processing
                                ? 'Publishing…'
                                : 'Publish for new papers'
                        }}
                    </Button>
                </div>
            </form>
        </section>
    </div>
</template>
