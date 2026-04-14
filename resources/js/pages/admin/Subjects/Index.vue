<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BookOpen, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

interface Program {
    id: number;
    name: string;
    code: string;
}

interface Subject {
    id: number;
    name: string;
    code: string;
    program_id: number | null;
    year_level: number | null;
    program: Program | null;
    description: string | null;
    is_active: boolean;
}

const props = defineProps<{
    subjects: Subject[];
    programs: Program[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Subjects', href: admin.subjects.index() },
        ],
    },
});

const showForm = ref(false);
const editingSubject = ref<Subject | null>(null);

const form = useForm({
    name: '',
    code: '',
    program_id: '' as string | number,
    year_level: '' as string | number,
    description: '',
    is_active: true,
});

function openNew() {
    editingSubject.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showForm.value = true;
}

function openEdit(subject: Subject) {
    editingSubject.value = subject;
    form.name = subject.name;
    form.code = subject.code;
    form.program_id = subject.program_id ?? '';
    form.year_level = subject.year_level ?? '';
    form.description = subject.description ?? '';
    form.is_active = subject.is_active;
    form.clearErrors();
    showForm.value = true;
}

function submit() {
    if (editingSubject.value) {
        form.patch(admin.subjects.update.url(editingSubject.value.id), {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post(admin.subjects.store.url(), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

function deleteSubject(subject: Subject) {
    if (!confirm(`Delete "${subject.name}"?`)) {
        return;
    }

    useForm({}).delete(admin.subjects.destroy.url(subject.id));
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
                            Subjects
                        </h1>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Manage the subject catalog for your academic
                            programs.
                        </p>
                    </div>
                    <Button
                        @click="openNew"
                        class="flex items-center gap-2 border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600"
                    >
                        <Plus class="h-4 w-4" />
                        New Subject
                    </Button>
                </div>

                <div
                    v-if="subjects.length === 0"
                    class="rounded-2xl border border-sidebar-border/70 bg-white p-12 text-center dark:bg-sidebar"
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <BookOpen class="h-7 w-7 text-orange-400" />
                    </div>
                    <p class="font-semibold text-slate-700 dark:text-slate-300">
                        No subjects yet
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create your first subject to get started.
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="subject in subjects"
                        :key="subject.id"
                        class="flex items-center gap-3 rounded-2xl border border-sidebar-border/70 bg-white px-5 py-4 dark:bg-sidebar"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                        >
                            <BookOpen class="h-5 w-5 text-orange-500" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="truncate font-bold text-slate-900 dark:text-white"
                                    >{{ subject.name }}</span
                                >
                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >{{ subject.code }}</span
                                >
                                <span
                                    class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-semibold text-teal-700 dark:bg-teal-950/50 dark:text-teal-400"
                                >
                                    {{ subject.program?.code || '—' }}
                                </span>
                                <span
                                    v-if="subject.year_level"
                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-400"
                                    >Year {{ subject.year_level }}</span
                                >
                                <span
                                    v-if="!subject.is_active"
                                    class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-950/40 dark:text-red-400"
                                    >Inactive</span
                                >
                            </div>
                            <p
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                            >
                                {{ subject.description || 'No description' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-orange-500"
                                @click="openEdit(subject)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-red-500"
                                @click="deleteSubject(subject)"
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
                        {{ editingSubject ? 'Edit Subject' : 'New Subject' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Fill in the subject details below.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label for="subject-name">Name</Label>
                        <Input
                            id="subject-name"
                            v-model="form.name"
                            type="text"
                            required
                            class="mt-1.5"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <Label for="subject-code">Code</Label>
                        <Input
                            id="subject-code"
                            v-model="form.code"
                            type="text"
                            required
                            class="mt-1.5"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Short identifier e.g. RM101
                        </p>
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <div>
                        <Label for="subject-program">Program</Label>
                        <select
                            id="subject-program"
                            v-model="form.program_id"
                            class="mt-1.5 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">— None —</option>
                            <option
                                v-for="program in props.programs"
                                :key="program.id"
                                :value="program.id"
                            >
                                {{ program.code }} - {{ program.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.program_id"
                        />
                    </div>

                    <div>
                        <Label for="s-year">Year Level</Label>
                        <select
                            id="s-year"
                            v-model="form.year_level"
                            class="mt-1.5 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">— None —</option>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                            <option value="5">Year 5</option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.year_level"
                        />
                    </div>

                    <div>
                        <Label for="subject-description">Description</Label>
                        <textarea
                            id="subject-description"
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
                                    : editingSubject
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
