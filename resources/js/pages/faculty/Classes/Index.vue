<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BookOpen,
    Calendar,
    Check,
    Copy,
    ExternalLink,
    GraduationCap,
    Link2,
    Pencil,
    Plus,
    Search,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SchoolClassController from '@/actions/App/Http/Controllers/Faculty/SchoolClassController';
import FormSelect from '@/components/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useConfirm } from '@/composables/useConfirm';
import {
    index as classesIndex,
    show as classesShow,
} from '@/routes/faculty/classes';

type SubjectItem = {
    id: string;
    name: string;
    code: string;
    program: { id: string; name: string; code: string } | null;
};

type SchoolClassItem = {
    id: string;
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
const { confirm } = useConfirm();
const editingClass = ref<SchoolClassItem | null>(null);
const classSearch = ref('');

const form = useForm({
    name: '',
    subject_id: '' as string | null,
    class_code: '',
    school_year: '',
    semester: '' as string | number,
    term: '',
    section: '',
    description: '',
    is_active: true,
});

const props = defineProps<Props>();

const activeCount = computed(
    () => props.classes.filter((c) => c.is_active).length,
);

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

const visibleClassCount = ref(10);
const visibleClasses = computed(() =>
    filteredClasses.value.slice(0, visibleClassCount.value),
);

watch(classSearch, () => {
    visibleClassCount.value = 10;
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

        const found = props.subjects.find((s) => s.id === id);

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

        const found = props.subjects.find((s) => s.id === form.subject_id);

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

async function deleteClass(schoolClass: SchoolClassItem) {
    const ok = await confirm(`Delete "${schoolClass.name}"?`, {
        title: 'Delete Class',
        confirmLabel: 'Delete',
    });

    if (!ok) {
        return;
    }

    useForm({}).delete(
        SchoolClassController.destroy.url({ class: schoolClass.id }),
    );
}

const copiedId = ref<string | null>(null);

function copyJoinLink(classId: string, joinCode: string) {
    const url = `${window.location.origin}/classes/join/${joinCode}`;
    navigator.clipboard.writeText(url);
    copiedId.value = classId;
    setTimeout(() => (copiedId.value = null), 2000);
}

function semesterLabel(semester: number | null): string {
    if (!semester) {
        return '-';
    }

    const labels: Record<number, string> = {
        1: '1st Sem',
        2: '2nd Sem',
        3: 'Summer',
    };

    return labels[semester] ?? `Sem ${semester}`;
}
</script>

<template>
    <Head title="My Classes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Hero Header -->
        <section
            class="overflow-hidden rounded-2xl border border-border bg-card"
        >
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        My Classes
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Create and manage your research classes.
                    </p>
                </div>
                <Button
                    class="flex items-center gap-2 border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600"
                    @click="openNew"
                >
                    <Plus class="h-4 w-4" />
                    New Class
                </Button>
            </div>
        </section>

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
                        {{ classes.reduce((s, c) => s + c.members_count, 0) }}
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
            <!-- Left: list -->
            <div class="space-y-4">
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="classSearch"
                        type="text"
                        placeholder="Search classes…"
                        class="w-full rounded-xl border border-border bg-card py-2.5 pr-4 pl-10 text-sm text-foreground outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-400/30"
                    />
                </div>

                <!-- Empty state: no classes -->
                <div
                    v-if="classes.length === 0"
                    class="flex flex-col items-center rounded-2xl border border-dashed border-border bg-card py-16"
                >
                    <div
                        class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                    >
                        <GraduationCap class="h-7 w-7 text-orange-400" />
                    </div>
                    <p class="font-semibold text-foreground">No classes yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create your first class to get started.
                    </p>
                </div>

                <!-- Empty state: search no results -->
                <div
                    v-else-if="filteredClasses.length === 0"
                    class="rounded-2xl border border-dashed border-border bg-card p-8 text-center"
                >
                    <p class="text-sm font-medium text-foreground">
                        No classes match your search.
                    </p>
                    <button
                        class="mt-2 text-xs text-orange-500 hover:underline"
                        @click="classSearch = ''"
                    >
                        Clear search
                    </button>
                </div>

                <!-- Class cards -->
                <TooltipProvider v-else :delay-duration="300">
                    <div class="space-y-3">
                        <div
                            v-for="item in visibleClasses"
                            :key="item.id"
                            class="group relative overflow-hidden rounded-2xl border border-border bg-card transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                            :class="
                                item.is_active
                                    ? 'hover:border-orange-300 dark:hover:border-orange-800'
                                    : 'opacity-75 hover:border-border hover:opacity-100'
                            "
                        >
                            <!-- Active accent bar -->
                            <div
                                class="absolute inset-y-0 left-0 w-1 transition-colors"
                                :class="
                                    item.is_active
                                        ? 'bg-orange-500'
                                        : 'bg-muted-foreground/20'
                                "
                            />

                            <Link
                                :href="classesShow.url({ class: item.id })"
                                class="flex items-start gap-4 py-5 pr-5 pl-6"
                            >
                                <!-- Avatar with student count ring -->
                                <div class="relative shrink-0">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-xl transition-colors"
                                        :class="
                                            item.is_active
                                                ? 'bg-orange-50 dark:bg-orange-950/30'
                                                : 'bg-muted'
                                        "
                                    >
                                        <GraduationCap
                                            class="h-6 w-6"
                                            :class="
                                                item.is_active
                                                    ? 'text-orange-500'
                                                    : 'text-muted-foreground'
                                            "
                                        />
                                    </div>
                                    <span
                                        v-if="item.members_count > 0"
                                        class="absolute -right-1.5 -bottom-1.5 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-card px-1 text-[10px] font-bold text-white"
                                        :class="
                                            item.is_active
                                                ? 'bg-teal-500'
                                                : 'bg-muted-foreground'
                                        "
                                    >
                                        {{ item.members_count }}
                                    </span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <!-- Title row -->
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="truncate text-base font-bold text-foreground group-hover:text-orange-600 dark:group-hover:text-orange-400"
                                        >
                                            {{ item.name }}
                                        </span>
                                        <span
                                            v-for="subject in item.subjects.slice(
                                                0,
                                                3,
                                            )"
                                            :key="subject.id"
                                            class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                        >
                                            {{ subject.code }}
                                        </span>
                                        <span
                                            v-if="item.subjects.length > 3"
                                            class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                        >
                                            +{{ item.subjects.length - 3 }}
                                        </span>
                                        <span
                                            v-if="item.section"
                                            class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                        >
                                            Sec {{ item.section }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="
                                                item.is_active
                                                    ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300'
                                                    : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300'
                                            "
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="
                                                    item.is_active
                                                        ? 'bg-green-500'
                                                        : 'bg-red-500'
                                                "
                                            />
                                            {{
                                                item.is_active
                                                    ? 'Active'
                                                    : 'Inactive'
                                            }}
                                        </span>
                                    </div>

                                    <!-- Class code -->
                                    <p
                                        v-if="item.class_code"
                                        class="mt-0.5 text-xs font-medium text-muted-foreground/70"
                                    >
                                        {{ item.class_code }}
                                    </p>

                                    <!-- Metadata chips -->
                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                    >
                                        <span
                                            v-if="item.school_year"
                                            class="inline-flex items-center gap-1"
                                        >
                                            <Calendar class="h-3 w-3" />
                                            {{ item.school_year }}
                                        </span>
                                        <span
                                            v-if="item.semester"
                                            class="inline-flex items-center gap-1"
                                        >
                                            <BookOpen class="h-3 w-3" />
                                            {{ semesterLabel(item.semester) }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1"
                                        >
                                            <Users class="h-3 w-3" />
                                            {{ item.members_count }} student{{
                                                item.members_count === 1
                                                    ? ''
                                                    : 's'
                                            }}
                                        </span>
                                        <span
                                            v-if="item.join_code"
                                            class="inline-flex items-center gap-1 text-green-600 dark:text-green-400"
                                        >
                                            <Link2 class="h-3 w-3" />
                                            Join link active
                                        </span>
                                    </div>

                                    <p
                                        v-if="item.description"
                                        class="mt-2 line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>

                                <!-- Hover arrow -->
                                <ExternalLink
                                    class="mt-1 h-4 w-4 shrink-0 text-muted-foreground/0 transition-all group-hover:text-muted-foreground"
                                />
                            </Link>

                            <!-- Action bar -->
                            <div
                                class="flex items-center justify-between border-t border-border/50 px-4 py-1.5"
                            >
                                <span
                                    class="text-[10px] font-medium tracking-wide text-muted-foreground/50 uppercase"
                                >
                                    {{ item.subjects.length }} subject{{
                                        item.subjects.length === 1 ? '' : 's'
                                    }}
                                </span>
                                <div class="flex items-center gap-0.5">
                                    <Tooltip v-if="item.join_code">
                                        <TooltipTrigger as-child>
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition"
                                                :class="
                                                    copiedId === item.id
                                                        ? 'text-green-600 dark:text-green-400'
                                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                                "
                                                @click.stop="
                                                    copyJoinLink(
                                                        item.id,
                                                        item.join_code!,
                                                    )
                                                "
                                            >
                                                <Check
                                                    v-if="copiedId === item.id"
                                                    class="h-3 w-3"
                                                />
                                                <Copy v-else class="h-3 w-3" />
                                                {{
                                                    copiedId === item.id
                                                        ? 'Copied!'
                                                        : 'Copy link'
                                                }}
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="bottom">
                                            <p>Copy join invitation link</p>
                                        </TooltipContent>
                                    </Tooltip>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7 text-muted-foreground hover:text-orange-500"
                                                @click.stop="openEdit(item)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent side="bottom">
                                            <p>Edit class</p>
                                        </TooltipContent>
                                    </Tooltip>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7 text-muted-foreground hover:text-red-500"
                                                @click.stop="deleteClass(item)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent side="bottom">
                                            <p>Delete class</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Show more -->
                    <div
                        v-if="visibleClassCount < filteredClasses.length"
                        class="pt-1 text-center"
                    >
                        <button
                            type="button"
                            class="text-xs font-semibold text-orange-500 hover:underline"
                            @click="visibleClassCount += 10"
                        >
                            Show more ({{
                                filteredClasses.length - visibleClassCount
                            }}
                            remaining)
                        </button>
                    </div>
                </TooltipProvider>
            </div>

            <!-- Right: Create/Edit form panel -->
            <div
                v-if="showForm"
                class="rounded-2xl border border-border bg-card p-5"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-foreground">
                            {{ editingClass ? 'Edit Class' : 'New Class' }}
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Fill in the class details below.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                        @click="showForm = false"
                    >
                        ✕
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <Label
                            >Subject <span class="text-red-500">*</span></Label
                        >
                        <div
                            class="mt-1.5 overflow-hidden rounded-xl border border-input"
                        >
                            <div
                                class="relative border-b border-input bg-background"
                            >
                                <Search
                                    class="absolute top-2.5 left-2.5 h-3.5 w-3.5 text-muted-foreground"
                                />
                                <input
                                    v-model="subjectSearch"
                                    type="text"
                                    placeholder="Search subjects…"
                                    class="w-full bg-background py-2 pr-3 pl-8 text-sm text-foreground outline-none"
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
                                    >
                                        {{ subject.program.code }}
                                    </span>
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

                    <div class="grid grid-cols-2 gap-3">
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
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label for="f-semester">Semester</Label>
                            <FormSelect
                                id="f-semester"
                                v-model="form.semester"
                                class="mt-1.5"
                            >
                                <option value="">—</option>
                                <option value="1">1st Semester</option>
                                <option value="2">2nd Semester</option>
                                <option value="3">3rd Semester</option>
                            </FormSelect>
                            <InputError
                                class="mt-2"
                                :message="form.errors.semester"
                            />
                        </div>
                        <div>
                            <Label for="f-term">Term</Label>
                            <FormSelect
                                id="f-term"
                                v-model="form.term"
                                class="mt-1.5"
                            >
                                <option value="">—</option>
                                <option value="1st">1st</option>
                                <option value="2nd">2nd</option>
                                <option value="3rd">3rd</option>
                            </FormSelect>
                            <InputError
                                class="mt-2"
                                :message="form.errors.term"
                            />
                        </div>
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

                    <div class="flex gap-2 pt-1">
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
