<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    GraduationCap,
    Link2,
    Pencil,
    Plus,
    Search,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import { show as classesShow } from '@/routes/admin/classes';

interface FacultyUser {
    id: number;
    name: string;
}

interface SubjectItem {
    id: number;
    name: string;
    code: string;
    program: { id: number; name: string; code: string } | null;
}

interface SchoolClass {
    id: number;
    name: string;
    class_code: string | null;
    school_year: string | null;
    semester: number | null;
    term: string | null;
    section: string;
    subjects: SubjectItem[];
    subject_id: number | null;
    faculty_id: number | null;
    faculty: { name: string } | null;
    description: string | null;
    is_active: boolean;
    join_code: string | null;
    members_count: number;
}

const props = defineProps<{
    classes: SchoolClass[];
    facultyUsers: FacultyUser[];
    subjects: SubjectItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Classes', href: admin.classes.index() },
        ],
    },
});

const showForm = ref(false);
const editingClass = ref<SchoolClass | null>(null);
const classSearch = ref('');

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
            c.subjects.some(
                (s) =>
                    s.code.toLowerCase().includes(q) ||
                    s.name.toLowerCase().includes(q),
            ),
    );
});

const form = useForm({
    faculty_id: '' as string | number,
    subject_id: '' as number | string,
    name: '',
    class_code: '',
    school_year: '',
    semester: '' as string | number,
    section: '',
    term: '',
    description: '',
    is_active: true,
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
        const found = props.subjects.find(
            (s) => s.id === Number(form.subject_id),
        );

        if (found) {
            form.class_code = found.code + (section ? '-' + section : '');
        }
    },
);

function openNew() {
    subjectSearch.value = '';
    editingClass.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showForm.value = true;
}

function openEdit(schoolClass: SchoolClass) {
    subjectSearch.value = '';
    editingClass.value = schoolClass;
    form.faculty_id = schoolClass.faculty_id ?? '';
    form.subject_id = schoolClass.subject_id ?? '';
    form.name = schoolClass.name;
    form.class_code = schoolClass.class_code ?? '';
    form.school_year = schoolClass.school_year ?? '';
    form.semester = schoolClass.semester ?? '';
    form.section = schoolClass.section;
    form.term = schoolClass.term ?? '';
    form.description = schoolClass.description ?? '';
    form.is_active = schoolClass.is_active;
    form.clearErrors();
    showForm.value = true;
}

function submit() {
    if (editingClass.value) {
        form.patch(admin.classes.update.url(editingClass.value.id), {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post(admin.classes.store.url(), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

function deleteClass(schoolClass: SchoolClass) {
    if (!confirm(`Delete "${schoolClass.name}"?`)) {
        return;
    }

    useForm({}).delete(admin.classes.destroy.url(schoolClass.id));
}

const copiedId = ref<number | null>(null);

function joinUrl(schoolClass: SchoolClass): string {
    return schoolClass.join_code
        ? `${window.location.origin}/classes/join/${schoolClass.join_code}`
        : '';
}

function copyJoinLink(schoolClass: SchoolClass) {
    const url = joinUrl(schoolClass);

    if (url) {
        navigator.clipboard.writeText(url);
        copiedId.value = schoolClass.id;
        setTimeout(() => {
            copiedId.value = null;
        }, 2000);
    }
}

const totalStudents = computed(() =>
    props.classes.reduce((sum, c) => sum + (c.members_count || 0), 0),
);
const activeCount = computed(
    () => props.classes.filter((c) => c.is_active).length,
);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-foreground">Classes</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Manage class sections and year levels by program.
                </p>
            </div>
            <Button
                @click="openNew"
                class="flex items-center gap-2 border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600"
            >
                <Plus class="h-4 w-4" /> New Class
            </Button>
        </div>

        <!-- Quick Stats -->
        <div class="grid gap-3 sm:grid-cols-3">
            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950/30"
                >
                    <GraduationCap class="h-5 w-5 text-orange-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Total Classes
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ classes.length }}
                    </p>
                </div>
            </div>
            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950/30"
                >
                    <GraduationCap class="h-5 w-5 text-green-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Active
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ activeCount }}
                    </p>
                </div>
            </div>
            <div
                class="flex items-center gap-3 rounded-xl border border-border bg-card p-4"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-950/30"
                >
                    <Users class="h-5 w-5 text-teal-500" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Total Students
                    </p>
                    <p class="text-lg font-bold text-foreground">
                        {{ totalStudents }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="grid gap-6"
            :class="
                showForm
                    ? 'xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]'
                    : 'grid-cols-1'
            "
        >
            <div class="space-y-4">
                <!-- Search -->
                <div class="relative">
                    <Search
                        class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground"
                    />
                    <input
                        v-model="classSearch"
                        type="text"
                        placeholder="Search classes…"
                        class="w-full rounded-xl border border-input bg-background py-2 pr-3 pl-9 text-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </div>

                <!-- Empty State -->
                <div
                    v-if="classes.length === 0"
                    class="rounded-2xl border border-border bg-card p-12 text-center"
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <GraduationCap class="h-7 w-7 text-orange-400" />
                    </div>
                    <p class="font-semibold text-foreground">No classes yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create your first class to get started.
                    </p>
                </div>

                <div
                    v-else-if="filteredClasses.length === 0"
                    class="rounded-2xl border border-border bg-card p-8 text-center"
                >
                    <p class="text-sm font-medium text-foreground">
                        No classes match your search.
                    </p>
                    <button
                        @click="classSearch = ''"
                        class="mt-2 text-xs text-orange-500 hover:underline"
                    >
                        Clear search
                    </button>
                </div>

                <!-- Class Cards -->
                <div v-else class="space-y-3">
                    <div
                        v-for="schoolClass in filteredClasses"
                        :key="schoolClass.id"
                        class="overflow-hidden rounded-2xl border border-border bg-card transition-shadow hover:shadow-md"
                    >
                        <!-- Top row: name, badges, actions -->
                        <div class="flex items-start gap-3 p-5">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                            >
                                <GraduationCap
                                    class="h-5 w-5 text-orange-500"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        :href="
                                            classesShow.url({
                                                class: schoolClass.id,
                                            })
                                        "
                                        class="truncate font-bold text-foreground hover:text-orange-500"
                                        >{{ schoolClass.name }}</a
                                    >
                                    <span
                                        class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                        >Sec {{ schoolClass.section }}</span
                                    >
                                    <span
                                        v-if="schoolClass.school_year"
                                        class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                        >{{ schoolClass.school_year }}</span
                                    >
                                    <span
                                        v-if="schoolClass.semester"
                                        class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-950/30 dark:text-blue-400"
                                        >{{
                                            schoolClass.semester === 1
                                                ? '1st Sem'
                                                : '2nd Sem'
                                        }}</span
                                    >
                                    <span
                                        v-if="schoolClass.term"
                                        class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400"
                                        >{{ schoolClass.term }}</span
                                    >
                                    <span
                                        v-for="subject in schoolClass.subjects?.slice(
                                            0,
                                            3,
                                        )"
                                        :key="subject.id"
                                        class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-950/30 dark:text-violet-400"
                                        >{{ subject.code }}</span
                                    >
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                            schoolClass.is_active
                                                ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400'
                                                : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400',
                                        ]"
                                    >
                                        {{
                                            schoolClass.is_active
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </span>
                                </div>
                                <p
                                    class="mt-1 truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        schoolClass.description ||
                                        'No description'
                                    }}
                                </p>
                                <p
                                    v-if="schoolClass.faculty?.name"
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    <span class="font-medium text-foreground"
                                        >Faculty:</span
                                    >
                                    {{ schoolClass.faculty.name }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-muted-foreground hover:text-orange-500"
                                    @click="openEdit(schoolClass)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-muted-foreground hover:text-red-500"
                                    @click="deleteClass(schoolClass)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>

                        <!-- Bottom row: members + join link -->
                        <div
                            class="flex flex-wrap items-center gap-4 border-t border-border bg-muted/30 px-5 py-3 text-xs"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 font-medium text-muted-foreground"
                            >
                                <Users class="h-3.5 w-3.5" />
                                {{ schoolClass.members_count }}
                                {{
                                    schoolClass.members_count === 1
                                        ? 'student'
                                        : 'students'
                                }}
                            </span>
                            <span
                                v-if="schoolClass.join_code"
                                class="inline-flex items-center gap-1.5"
                            >
                                <Link2 class="h-3.5 w-3.5 text-green-500" />
                                <span
                                    class="font-mono font-semibold text-foreground"
                                    >{{ schoolClass.join_code }}</span
                                >
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md bg-card px-1.5 py-0.5 font-semibold text-muted-foreground transition hover:text-foreground"
                                    @click.stop="copyJoinLink(schoolClass)"
                                >
                                    <Check
                                        v-if="copiedId === schoolClass.id"
                                        class="h-3 w-3 text-green-500"
                                    />
                                    <Copy v-else class="h-3 w-3" />
                                    {{
                                        copiedId === schoolClass.id
                                            ? 'Copied!'
                                            : 'Copy link'
                                    }}
                                </button>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 text-muted-foreground"
                            >
                                <Link2 class="h-3.5 w-3.5" />
                                No join link
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Panel -->
            <div
                v-if="showForm"
                class="rounded-2xl border border-border bg-card p-5"
            >
                <div class="mb-4">
                    <h2 class="text-xl font-black text-foreground">
                        {{ editingClass ? 'Edit Class' : 'New Class' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Fill in the class details below.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label for="class-faculty">Assigned Faculty</Label>
                        <select
                            id="class-faculty"
                            v-model="form.faculty_id"
                            class="mt-1.5 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">— Unassigned —</option>
                            <option
                                v-for="faculty in props.facultyUsers"
                                :key="faculty.id"
                                :value="faculty.id"
                            >
                                {{ faculty.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.faculty_id"
                        />
                    </div>

                    <div>
                        <Label
                            >Subject <span class="text-red-500">*</span></Label
                        >
                        <div
                            class="mt-1.5 overflow-hidden rounded-md border border-input"
                        >
                            <div class="relative border-b border-input">
                                <Search
                                    class="absolute top-2.5 left-2.5 h-3.5 w-3.5 text-muted-foreground"
                                />
                                <input
                                    v-model="subjectSearch"
                                    type="text"
                                    placeholder="Search subjects…"
                                    class="w-full bg-transparent py-2 pr-3 pl-8 text-sm outline-none placeholder:text-muted-foreground"
                                />
                            </div>
                            <div
                                class="max-h-40 space-y-0.5 overflow-y-auto p-1.5"
                            >
                                <label
                                    v-for="subject in filteredSubjects"
                                    :key="subject.id"
                                    class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-800"
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
                                        class="rounded-full bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-700 dark:bg-teal-950/50 dark:text-teal-400"
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
                        <Label for="class-name">Class Name</Label>
                        <Input
                            id="class-name"
                            v-model="form.name"
                            type="text"
                            required
                            readonly
                            placeholder="Auto-filled from subject"
                            class="mt-1.5"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <Label for="class-section">Section</Label>
                        <Input
                            id="class-section"
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
                        <Label for="class-code">Class Code</Label>
                        <Input
                            id="class-code"
                            v-model="form.class_code"
                            type="text"
                            maxlength="30"
                            placeholder="e.g. BSIT3A-S1-2526"
                            class="mt-1.5"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.class_code"
                        />
                    </div>

                    <div>
                        <Label for="class-school-year">School Year</Label>
                        <Input
                            id="class-school-year"
                            v-model="form.school_year"
                            type="text"
                            maxlength="9"
                            placeholder="e.g. 2025-2026"
                            class="mt-1.5"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.school_year"
                        />
                    </div>

                    <div>
                        <Label for="class-semester">Semester</Label>
                        <select
                            id="class-semester"
                            v-model="form.semester"
                            class="mt-1.5 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">&mdash; Select &mdash;</option>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.semester"
                        />
                    </div>

                    <div>
                        <Label for="class-term">Term</Label>
                        <select
                            id="class-term"
                            v-model="form.term"
                            class="mt-1.5 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">&mdash; Select &mdash;</option>
                            <option value="Midterm">Midterm</option>
                            <option value="Finals">Finals</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.term" />
                    </div>

                    <div>
                        <Label for="class-description">Description</Label>
                        <textarea
                            id="class-description"
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
                        <span class="text-sm font-medium text-foreground"
                            >Is Active</span
                        >
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
