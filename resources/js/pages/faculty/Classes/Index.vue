<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    GraduationCap,
    Users,
    Link2,
    Copy,
    Plus,
    Pencil,
    Trash2,
    Search,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import SchoolClassController from '@/actions/App/Http/Controllers/Faculty/SchoolClassController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as classesIndex,
    show as classesShow,
} from '@/routes/faculty/classes';

type SubjectItem = {
    id: number;
    name: string;
    code: string;
    program: { id: number; name: string; code: string } | null;
};

type SchoolClassItem = {
    id: number;
    name: string;
    class_code: string | null;
    section: string | null;
    school_year: string | null;
    semester: number | null;
    description: string | null;
    is_active: boolean;
    join_code: string | null;
    members_count: number;
    subjects: SubjectItem[];
};

type Props = {
    classes: SchoolClassItem[];
    subjects: SubjectItem[];
};

const showForm = ref(false);
const editingClass = ref<SchoolClassItem | null>(null);
const classSearch = ref('');

const form = useForm({
    name: '',
    subject_id: '' as number | string,
    class_code: '',
    school_year: '',
    semester: '' as string | number,
    term: '',
    section: '',
    description: '',
    is_active: true,
});

const props = defineProps<Props>();

const filteredClasses = computed(() => {
    const q = classSearch.value.toLowerCase().trim();

    if (!q) {
        return props.classes;
    }

    return props.classes.filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            (c.class_code?.toLowerCase().includes(q) ?? false) ||
            (c.section?.toLowerCase().includes(q) ?? false) ||
            c.subjects.some((s) => s.code.toLowerCase().includes(q) || s.name.toLowerCase().includes(q)),
    );
});

const subjectSearch = ref('');

const filteredSubjects = computed(() => {
    const q = subjectSearch.value.toLowerCase().trim();

    if (!q) {
        return props.subjects;
    }

    return props.subjects.filter(
        (s) =>
            s.code.toLowerCase().includes(q) ||
            s.name.toLowerCase().includes(q) ||
            (s.program?.code.toLowerCase().includes(q) ?? false),
    );
});

watch(
    () => form.subject_id,
    (id) => {
        if (editingClass.value) {
            return;
        }

        const found = props.subjects.find((s) => s.id === Number(id));

        if (found) {
            form.name = found.name;
            form.class_code =
                found.code + (form.section ? '-' + form.section : '');
        } else {
            form.name = '';
            form.class_code = '';
        }
    },
);

watch(
    () => form.section,
    (section) => {
        if (editingClass.value) {
            return;
        }

        const found = props.subjects.find(
            (s) => s.id === Number(form.subject_id),
        );

        if (found) {
            form.class_code = found.code + (section ? '-' + section : '');
        }
    },
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My Classes', href: classesIndex() }],
    },
});

function openNew() {
    subjectSearch.value = '';
    editingClass.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showForm.value = true;
}

function openEdit(schoolClass: SchoolClassItem) {
    subjectSearch.value = '';
    editingClass.value = schoolClass;
    form.name = schoolClass.name;
    form.subject_id = schoolClass.subjects[0]?.id ?? '';
    form.class_code = schoolClass.class_code ?? '';
    form.school_year = schoolClass.school_year ?? '';
    form.semester = schoolClass.semester ?? '';
    form.section = schoolClass.section ?? '';
    form.term = '';
    form.description = schoolClass.description ?? '';
    form.is_active = schoolClass.is_active;
    form.clearErrors();
    showForm.value = true;
}

function submit() {
    if (editingClass.value) {
        form.patch(
            SchoolClassController.update.url({ class: editingClass.value.id }),
            {
                onSuccess: () => {
                    showForm.value = false;
                },
            },
        );
    } else {
        form.post(SchoolClassController.store.url(), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

function deleteClass(schoolClass: SchoolClassItem) {
    if (!confirm(`Delete "${schoolClass.name}"?`)) {
        return;
    }

    useForm({}).delete(
        SchoolClassController.destroy.url({ class: schoolClass.id }),
    );
}

function copyJoinLink(joinCode: string) {
    const url = `${window.location.origin}/classes/join/${joinCode}`;
    navigator.clipboard.writeText(url);
}
</script>

<template>
    <Head title="My Classes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="grid gap-6"
            :class="
                showForm
                    ? 'xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]'
                    : 'grid-cols-1'
            "
        >
            <!-- Left: list -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1
                            class="text-2xl font-bold text-foreground"
                        >
                            My Classes
                        </h1>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Create and manage your classes.
                        </p>
                    </div>
                    <Button
                        @click="openNew"
                        class="flex items-center gap-2 border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600"
                    >
                        <Plus class="h-4 w-4" />
                        New Class
                    </Button>
                </div>

                <div class="relative">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <input
                        v-model="classSearch"
                        type="text"
                        placeholder="Search classes…"
                        class="w-full rounded-xl border border-input bg-background py-2 pl-9 pr-3 text-sm text-foreground outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>

                <!-- Empty state -->
                <div
                    v-if="classes.length === 0"
                    class="rounded-2xl border border-border bg-card p-12 text-center"
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <GraduationCap class="h-7 w-7 text-orange-400" />
                    </div>
                    <p class="font-semibold text-foreground">
                        No classes yet
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create your first class to get started.
                    </p>
                </div>

                <div v-else-if="filteredClasses.length === 0" class="rounded-2xl border border-border bg-card p-8 text-center">
                    <p class="text-sm font-medium text-foreground">No classes match your search.</p>
                    <button @click="classSearch = ''" class="mt-2 text-xs text-orange-500 hover:underline">Clear search</button>
                </div>

                <!-- Class list -->
                <div v-else class="space-y-3">
                    <div
                        v-for="item in filteredClasses"
                        :key="item.id"
                        class="flex items-center gap-3 rounded-2xl border border-border bg-card px-5 py-4 hover:bg-muted/50"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                        >
                            <GraduationCap class="h-5 w-5 text-orange-500" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link
                                    :href="classesShow({ class: item.id })"
                                    class="truncate font-bold text-foreground hover:text-orange-500"
                                >
                                    {{ item.name }}
                                </Link>
                                <span
                                    v-if="item.section"
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                    >Section {{ item.section }}</span
                                >
                                <span
                                    v-for="subject in item.subjects.slice(0, 3)"
                                    :key="subject.id"
                                    class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-950/30 dark:text-violet-300"
                                    >{{ subject.code }}</span
                                >
                                <span
                                    v-if="item.school_year"
                                    class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                    >{{ item.school_year }}</span
                                >
                                <span
                                    v-if="item.semester"
                                    class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                    >{{
                                        item.semester === 1
                                            ? '1st Sem'
                                            : '2nd Sem'
                                    }}</span
                                >
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="
                                        item.is_active
                                            ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300'
                                            : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300'
                                    "
                                >
                                    {{ item.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center gap-3">
                                <div
                                    class="flex items-center gap-1 text-xs text-muted-foreground"
                                >
                                    <Users class="h-3 w-3" />
                                    {{ item.members_count }} students
                                </div>
                                <div
                                    v-if="item.join_code"
                                    class="flex items-center gap-1"
                                >
                                    <Link2 class="h-3 w-3 text-green-500" />
                                    <code
                                        class="rounded bg-muted px-1 py-0.5 font-mono text-[10px] text-muted-foreground"
                                        >{{ item.join_code }}</code
                                    >
                                    <button
                                        @click="copyJoinLink(item.join_code!)"
                                        class="text-muted-foreground hover:text-foreground"
                                    >
                                        <Copy class="h-3 w-3" />
                                    </button>
                                </div>
                                <span
                                    v-else
                                    class="text-[10px] text-muted-foreground"
                                    >No join link</span
                                >
                            </div>
                            <p
                                v-if="item.description"
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                            >
                                {{ item.description }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-orange-500"
                                @click="openEdit(item)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-red-500"
                                @click="deleteClass(item)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Create/Edit form panel -->
            <div
                v-if="showForm"
                class="rounded-2xl border border-border bg-card p-5"
            >
                <div class="mb-4">
                    <h2
                        class="text-base font-bold text-foreground"
                    >
                        {{ editingClass ? 'Edit Class' : 'New Class' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Fill in the class details below.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label
                            >Subject <span class="text-red-500">*</span></Label
                        >
                        <div
                            class="mt-1.5 overflow-hidden rounded-xl border border-input"
                        >
                            <div class="relative border-b border-input bg-background">
                                <Search
                                    class="absolute top-2.5 left-2.5 h-3.5 w-3.5 text-muted-foreground"
                                />
                                <input
                                    v-model="subjectSearch"
                                    type="text"
                                    placeholder="Search subjects…"
                                    class="w-full bg-background py-2 pr-3 pl-8 text-sm text-foreground outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
                                />
                            </div>
                            <div
                                class="max-h-40 space-y-0.5 overflow-y-auto bg-background p-1.5"
                            >
                                <label
                                    v-for="subject in filteredSubjects"
                                    :key="subject.id"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-muted"
                                >
                                    <input
                                        v-model="form.subject_id"
                                        type="radio"
                                        :value="subject.id"
                                        class="accent-orange-500"
                                    />
                                    <span class="flex-1 text-sm">
                                        <span class="font-medium">{{
                                            subject.code
                                        }}</span>
                                        <span class="text-muted-foreground">
                                            — {{ subject.name }}</span
                                        >
                                    </span>
                                    <span
                                        v-if="subject.program"
                                        class="rounded-full bg-teal-50 px-1.5 py-0.5 text-[10px] font-semibold text-teal-700 dark:bg-teal-950/30 dark:text-teal-300"
                                        >{{ subject.program.code }}</span
                                    >
                                </label>
                                <p
                                    v-if="filteredSubjects.length === 0"
                                    class="py-2 text-center text-xs text-muted-foreground"
                                >
                                    No subjects found.
                                </p>
                            </div>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.subject_id"
                        />
                    </div>

                    <div>
                        <Label for="f-name">Class Name</Label>
                        <Input
                            id="f-name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Auto-filled from subject"
                            class="mt-1.5"
                            :readonly="!editingClass"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <Label for="f-section">Section</Label>
                        <Input
                            id="f-section"
                            v-model="form.section"
                            type="text"
                            maxlength="5"
                            placeholder="e.g. A"
                            class="mt-1.5"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.section"
                        />
                    </div>

                    <div>
                        <Label for="f-school-year">School Year</Label>
                        <Input
                            id="f-school-year"
                            v-model="form.school_year"
                            type="text"
                            maxlength="9"
                            placeholder="2025-2026"
                            class="mt-1.5"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.school_year"
                        />
                    </div>
                    <div>
                        <Label for="f-semester">Semester</Label>
                        <select
                            id="f-semester"
                            v-model="form.semester"
                            class="mt-1.5 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                        >
                            <option value="">—</option>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.semester"
                        />
                    </div>

                    <div>
                        <Label for="f-term">Term</Label>
                        <select
                            id="f-term"
                            v-model="form.term"
                            class="mt-1.5 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                        >
                            <option value="">—</option>
                            <option value="Midterm">Midterm</option>
                            <option value="Finals">Finals</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.term" />
                    </div>

                    <div>
                        <Label for="f-class-code">Class Code</Label>
                        <Input
                            id="f-class-code"
                            v-model="form.class_code"
                            type="text"
                            maxlength="30"
                            placeholder="e.g. BSIT3A-S1"
                            class="mt-1.5"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.class_code"
                        />
                    </div>

                    <div>
                        <Label for="f-description">Description</Label>
                        <textarea
                            id="f-description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1.5 w-full resize-none rounded-xl border border-input bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
                    </div>

                    <label
                        v-if="editingClass"
                        class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-border px-3 py-2 text-sm text-foreground"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded accent-orange-500"
                        />
                        <span class="font-medium text-foreground">Active</span>
                    </label>

                    <div class="flex gap-3 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            @click="showForm = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 border-0 bg-orange-500 text-white hover:bg-orange-600"
                        >
                            {{
                                form.processing
                                    ? 'Saving…'
                                    : editingClass
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
