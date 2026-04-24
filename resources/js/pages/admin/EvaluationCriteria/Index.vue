<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ListChecks, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';

type Criterion = {
    id: string;
    name: string;
    max_points: number;
    sort_order: number;
};

const props = defineProps<{
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
                title: 'Evaluation criteria',
                href: admin.evaluationCriteria.index.url(),
            },
        ],
    },
});

const showForm = ref(false);
const editing = ref<Criterion | null>(null);

const form = useForm({
    name: '',
    max_points: '' as string | number,
});

const totalOk = computed(() => props.total_max === props.target_total);

function openNew() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
}

function openEdit(c: Criterion) {
    editing.value = c;
    form.name = c.name;
    form.max_points = c.max_points;
    form.clearErrors();
    showForm.value = true;
}

function submit() {
    if (editing.value) {
        form.patch(
            admin.evaluationCriteria.update.url({
                evaluation_criterion: editing.value.id,
            }),
            {
                onSuccess: () => {
                    showForm.value = false;
                },
            },
        );
    } else {
        form.post(admin.evaluationCriteria.store.url(), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
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
        admin.evaluationCriteria.destroy.url({
            evaluation_criterion: c.id,
        }),
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Evaluation criteria" />

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
                            Evaluation criteria
                        </h1>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Names and point caps for each line. The sum of caps
                            must be 100 before panelists can submit scores.
                        </p>
                    </div>
                    <Button class="shrink-0" @click="openNew">
                        <Plus class="mr-1.5 h-4 w-4" />
                        Add criterion
                    </Button>
                </div>

                <div
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
                        >— adjust caps so the sum is 100.</span
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
                        Add one or more rows, then set weights to total 100.
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
                                Max points: {{ c.max_points }}
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
                    {{ editing ? 'Edit' : 'New' }} criterion
                </h2>
                <form class="space-y-3" @submit.prevent="submit">
                    <div>
                        <Label for="c-name">Name</Label>
                        <Input
                            id="c-name"
                            v-model="form.name"
                            class="mt-1.5 bg-background"
                            required
                        />
                        <InputError
                            v-if="form.errors.name"
                            :message="form.errors.name"
                        />
                    </div>
                    <div>
                        <Label for="c-pts">Max points (weight)</Label>
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
