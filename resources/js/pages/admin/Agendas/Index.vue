<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BookMarked, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import admin from '@/routes/admin';

interface Agenda {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
}

const props = defineProps<{
    agendas: Agenda[];
}>();

const { confirm } = useConfirm();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Agendas', href: admin.agendas.index() },
        ],
    },
});

const showForm = ref(false);
const editingAgenda = ref<Agenda | null>(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

function openNew() {
    editingAgenda.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showForm.value = true;
}

function openEdit(agenda: Agenda) {
    editingAgenda.value = agenda;
    form.name = agenda.name;
    form.code = agenda.code;
    form.description = agenda.description ?? '';
    form.is_active = agenda.is_active;
    form.clearErrors();
    showForm.value = true;
}

function submit() {
    if (editingAgenda.value) {
        form.patch(admin.agendas.update.url(editingAgenda.value.id), {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post(admin.agendas.store.url(), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

async function deleteAgenda(agenda: Agenda) {
    const ok = await confirm(`Delete "${agenda.name}"?`, {
        title: 'Delete Agenda',
        confirmLabel: 'Delete',
    });

    if (!ok) {
        return;
    }

    useForm({}).delete(admin.agendas.destroy.url(agenda.id));
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="grid gap-6"
            :class="
                showForm
                    ? 'xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]'
                    : 'grid-cols-1'
            "
        >
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-2xl font-black text-slate-900 dark:text-white"
                        >
                            Agendas
                        </h1>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Manage research agenda categories for institutional
                            planning and tracking.
                        </p>
                    </div>
                    <Button
                        @click="openNew"
                        class="flex items-center gap-2 border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600"
                    >
                        <Plus class="h-4 w-4" />
                        New Agenda
                    </Button>
                </div>

                <div
                    v-if="props.agendas.length === 0"
                    class="rounded-2xl border border-sidebar-border/70 bg-white p-12 text-center dark:bg-sidebar"
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <BookMarked class="h-7 w-7 text-orange-400" />
                    </div>
                    <p class="font-semibold text-slate-700 dark:text-slate-300">
                        No agendas yet
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create your first agenda to get started.
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="agenda in props.agendas"
                        :key="agenda.id"
                        class="flex items-center gap-3 rounded-2xl border border-sidebar-border/70 bg-white px-5 py-4 dark:bg-sidebar"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                        >
                            <BookMarked class="h-5 w-5 text-orange-500" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="truncate font-bold text-slate-900 dark:text-white"
                                    >{{ agenda.name }}</span
                                >
                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {{ agenda.code }}
                                </span>
                                <span
                                    v-if="!agenda.is_active"
                                    class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-950/40 dark:text-red-400"
                                    >Inactive</span
                                >
                            </div>
                            <p
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                            >
                                {{ agenda.description || 'No description' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-orange-500"
                                @click="openEdit(agenda)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-red-500"
                                @click="deleteAgenda(agenda)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="showForm"
                class="rounded-2xl border border-sidebar-border/70 bg-white p-5 dark:bg-sidebar"
            >
                <div class="mb-4">
                    <h2
                        class="text-xl font-black text-slate-900 dark:text-white"
                    >
                        {{ editingAgenda ? 'Edit Agenda' : 'New Agenda' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Fill in the agenda details below.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label for="agenda-name">Name</Label>
                        <Input
                            id="agenda-name"
                            v-model="form.name"
                            type="text"
                            required
                            class="mt-1.5"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <Label for="agenda-code">Code</Label>
                        <Input
                            id="agenda-code"
                            v-model="form.code"
                            type="text"
                            required
                            class="mt-1.5"
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <div>
                        <Label for="agenda-description">Description</Label>
                        <textarea
                            id="agenda-description"
                            v-model="form.description"
                            rows="4"
                            class="mt-1.5 w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded accent-orange-500"
                        />
                        <span
                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                            >Is Active</span
                        >
                    </label>
                    <InputError class="mt-2" :message="form.errors.is_active" />

                    <div class="flex gap-3 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            @click="showForm = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 border-0 bg-orange-500 text-white hover:bg-orange-600"
                        >
                            {{
                                form.processing
                                    ? 'Saving…'
                                    : editingAgenda
                                      ? 'Update'
                                      : 'Create'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
