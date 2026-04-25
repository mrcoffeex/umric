<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ListChecks, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';

type Criterion = {
    id: string;
    name: string;
    content: string | null;
    section_heading?: string | null;
    max_points: number;
    sort_order: number;
};

const props = defineProps<{
    format: {
        id: string;
        name: string;
        evaluation_type: string;
        use_weights: boolean;
    };
    criteria: Criterion[];
    total_max: number;
    target_total: number;
}>();

const { confirm } = useConfirm();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            {
                title: 'Evaluation formats',
                href: admin.evaluationFormats.index.url(),
            },
            { title: 'Criteria', href: '#' },
        ],
    },
});

const showForm = ref(false);
const editing = ref<Criterion | null>(null);

const form = useForm({
    content: '<p></p>',
    section_heading: '',
    max_points: '' as string | number,
});

const isChecklist = computed(
    () => props.format.evaluation_type === 'checklist',
);

const scoringUsesWeights = computed(
    () => !isChecklist.value && (props.format.use_weights ?? false),
);

const totalOk = computed(() => {
    if (isChecklist.value) {
        return props.criteria.length >= 1;
    }

    if (scoringUsesWeights.value) {
        return props.total_max === props.target_total;
    }

    return props.criteria.length >= 1;
});

function focusCriterionEditor() {
    nextTick(() => {
        document.getElementById('c-content-field')?.focus();
    });
}

function openNew() {
    editing.value = null;
    form.reset();
    form.content = '<p></p>';
    form.section_heading = '';
    form.max_points = '';
    form.clearErrors();
    showForm.value = true;
    focusCriterionEditor();
}

function openEdit(c: Criterion) {
    editing.value = c;
    form.content = c.content?.trim()
        ? c.content
        : `<p>${escapeForRte(c.name)}</p>`;
    form.section_heading = c.section_heading ?? '';

    if (isChecklist.value) {
        form.max_points = 1;
    } else if (scoringUsesWeights.value) {
        form.max_points = c.max_points;
    } else {
        form.max_points = '';
    }

    form.clearErrors();
    showForm.value = true;
    focusCriterionEditor();
}

function escapeForRte(text: string): string {
    return text
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function submit() {
    if (editing.value) {
        form.patch(
            admin.evaluationFormats.criteria.update.url({
                evaluationFormat: props.format.id,
                evaluation_criterion: editing.value.id,
            }),
            {
                onSuccess: () => {
                    showForm.value = false;
                },
            },
        );
    } else {
        form.post(
            admin.evaluationFormats.criteria.store.url({
                evaluationFormat: props.format.id,
            }),
            {
                onSuccess: () => {
                    showForm.value = false;
                    form.reset();
                    form.content = '<p></p>';
                    form.section_heading = '';
                },
            },
        );
    }
}

async function deleteRow(c: Criterion) {
    const ok = await confirm(`Remove “${c.name}”?`, {
        title: 'Remove criterion',
        confirmLabel: 'Remove',
    });

    if (!ok) {
        return;
    }

    useForm({}).delete(
        admin.evaluationFormats.criteria.destroy.url({
            evaluationFormat: props.format.id,
            evaluation_criterion: c.id,
        }),
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`${format.name} — criteria`" />

        <div
            class="grid gap-6"
            :class="
                showForm
                    ? 'xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]'
                    : 'grid-cols-1'
            "
        >
            <div class="space-y-4">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">
                            {{ format.name }}
                        </h1>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            <template v-if="isChecklist">
                                Add checklist items. Panelists will mark each
                                Yes or No.
                            </template>
                            <template v-else-if="scoringUsesWeights">
                                Set a name and a percentage weight for each
                                line. Weights must total 100% before the rubric
                                is ready.
                            </template>
                            <template v-else>
                                Add one or more criteria. Each is scored 1–100;
                                the evaluation total is the
                                <strong class="text-foreground">average</strong
                                >.
                            </template>
                        </p>
                    </div>
                    <Button class="shrink-0" @click="openNew">
                        <Plus class="mr-1.5 h-4 w-4" />
                        {{ isChecklist ? 'Add item' : 'Add criterion' }}
                    </Button>
                </div>

                <div
                    v-if="isChecklist"
                    class="flex flex-wrap items-center gap-2 rounded-xl border border-border bg-card px-4 py-3 text-sm"
                >
                    <span class="text-muted-foreground">Checklist items</span>
                    <span
                        :class="[
                            'font-bold tabular-nums',
                            totalOk ? 'text-primary' : 'text-destructive',
                        ]"
                        >{{ props.criteria.length }}</span
                    >
                    <span v-if="!totalOk" class="text-destructive"
                        >— add at least one item.</span
                    >
                </div>
                <div
                    v-else-if="scoringUsesWeights"
                    class="flex flex-wrap items-center gap-2 rounded-xl border border-border bg-card px-4 py-3 text-sm"
                >
                    <span class="text-muted-foreground">Total weight</span>
                    <span
                        :class="[
                            'font-bold tabular-nums',
                            totalOk ? 'text-primary' : 'text-destructive',
                        ]"
                        >{{ total_max }} / {{ target_total }}</span
                    >
                    <span v-if="!totalOk" class="text-destructive"
                        >— adjust weights so the sum is 100.</span
                    >
                </div>
                <div
                    v-else
                    class="flex flex-wrap items-center gap-2 rounded-xl border border-border bg-card px-4 py-3 text-sm"
                >
                    <span class="text-muted-foreground">Criteria</span>
                    <span
                        :class="[
                            'font-bold tabular-nums',
                            totalOk ? 'text-primary' : 'text-destructive',
                        ]"
                        >{{ props.criteria.length }}</span
                    >
                    <span v-if="!totalOk" class="text-destructive"
                        >— add at least one.</span
                    >
                </div>

                <div
                    v-if="props.criteria.length === 0"
                    class="rounded-2xl border border-dashed border-border bg-muted/20 p-12 text-center"
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-border bg-muted/50 text-primary"
                    >
                        <ListChecks class="h-7 w-7" />
                    </div>
                    <p class="font-medium text-foreground">No criteria yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        <template v-if="isChecklist"
                            >Add at least one yes / no line.</template
                        >
                        <template v-else-if="scoringUsesWeights"
                            >Add one or more rows, then set weights to total
                            100.</template
                        >
                        <template v-else>Add at least one criterion.</template>
                    </p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="c in props.criteria"
                        :key="c.id"
                        class="flex items-start gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40"
                        >
                            <ListChecks class="h-5 w-5 text-primary" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-foreground">
                                {{ c.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                <template v-if="isChecklist"
                                    >Response: Yes or No</template
                                >
                                <template v-else-if="scoringUsesWeights"
                                    >Weight: {{ c.max_points }}%</template
                                >
                                <template v-else
                                    >Scored 1–100 (part of average)</template
                                >
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <Button
                                variant="secondary"
                                size="icon"
                                @click="openEdit(c)"
                            >
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="secondary"
                                size="icon"
                                @click="deleteRow(c)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="showForm"
                class="h-fit space-y-4 rounded-2xl border border-border bg-card p-5 shadow-sm"
            >
                <h2 class="text-sm font-semibold text-foreground">
                    {{ editing ? 'Edit' : 'New' }}
                    {{ isChecklist ? 'item' : 'criterion' }}
                </h2>
                <form class="space-y-3" @submit.prevent="submit">
                    <div>
                        <Label for="c-content-field">Criterion</Label>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Use formatting to explain what panelists should look
                            for.
                        </p>
                        <div class="mt-1.5">
                            <RichTextEditor
                                v-model="form.content"
                                :disabled="form.processing"
                                input-id="c-content-field"
                            />
                        </div>
                        <InputError
                            v-if="form.errors.content"
                            :message="form.errors.content"
                        />
                    </div>
                    <div>
                        <Label for="c-section"
                            >PDF section heading (optional)</Label
                        >
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            e.g. &ldquo;I. THE PROBLEM&rdquo; — printed as a
                            section row before this criterion in the PDF.
                        </p>
                        <Input
                            id="c-section"
                            v-model="form.section_heading"
                            class="mt-1.5 bg-background"
                            placeholder="Leave blank to continue the same section"
                            autocomplete="off"
                        />
                        <InputError
                            v-if="form.errors.section_heading"
                            :message="form.errors.section_heading"
                        />
                    </div>
                    <div v-if="!isChecklist && scoringUsesWeights">
                        <Label for="c-pts">Weight (%)</Label>
                        <Input
                            id="c-pts"
                            v-model="form.max_points"
                            class="mt-1.5 bg-background"
                            type="number"
                            min="1"
                            max="100"
                            required
                        />
                        <InputError
                            v-if="form.errors.max_points"
                            :message="form.errors.max_points"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            @click="showForm = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="form.processing"
                            >Save</Button
                        >
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
