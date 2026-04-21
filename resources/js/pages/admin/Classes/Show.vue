<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ChevronLeft,
    Copy,
    GraduationCap,
    Link2,
    RefreshCw,
    ScrollText,
    Unlink,
    UserMinus,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminSchoolClassController from '@/actions/App/Http/Controllers/Admin/SchoolClassController';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

type Props = {
    schoolClass: {
        id: string;
        name: string;
        class_code: string | null;
        section: string;
        school_year: string | null;
        semester: number | null;
        term: string | null;
        description: string | null;
        is_active: boolean;
        join_code: string | null;
        faculty: { id: string; name: string } | null;
        subjects: Array<{
            id: string;
            name: string;
            code: string;
            program: { name: string; code: string } | null;
        }>;
    };
    students: Array<{
        id: string;
        name: string;
        email: string;
        avatar_url: string | null;
        joined_at: string;
    }>;
    researchPapersCount: number;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Classes', href: admin.classes.index() },
            { title: 'Class Details', href: '' },
        ],
    },
});

const joinUrl = computed(() =>
    props.schoolClass.join_code
        ? `${window.location.origin}/classes/join/${props.schoolClass.join_code}`
        : '',
);

const copied = ref(false);
const copyJoinLink = () => {
    if (joinUrl.value) {
        navigator.clipboard.writeText(joinUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    }
};

const generateJoinCodeForm = useForm({});
const revokeJoinCodeForm = useForm({});
const removeStudentForm = useForm({});

const generateJoinCode = () => {
    generateJoinCodeForm.post(
        AdminSchoolClassController.generateJoinCode.url({
            class: props.schoolClass.id,
        }),
        {
            preserveScroll: true,
        },
    );
};

const revokeJoinCode = () => {
    if (
        confirm(
            'Revoke this join link? Students will no longer be able to use it.',
        )
    ) {
        revokeJoinCodeForm.delete(
            AdminSchoolClassController.revokeJoinCode.url({
                class: props.schoolClass.id,
            }),
            {
                preserveScroll: true,
            },
        );
    }
};

const removeStudent = (studentId: string) => {
    if (confirm('Remove this student from the class?')) {
        removeStudentForm.delete(
            AdminSchoolClassController.removeStudent.url({
                class: props.schoolClass.id,
                student: studentId,
            }),
            {
                preserveScroll: true,
            },
        );
    }
};

const getInitials = (name: string) =>
    name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
</script>

<template>
    <Head :title="`Class: ${props.schoolClass.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Back nav -->
        <div>
            <Link
                :href="admin.classes.index()"
                class="flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ChevronLeft class="h-4 w-4" /> Back to Classes
            </Link>
        </div>

        <!-- Class header -->
        <div class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="p-5 md:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/30"
                            >
                                <GraduationCap
                                    class="h-5 w-5 text-orange-500"
                                />
                            </div>
                            <div class="min-w-0">
                                <h1
                                    class="truncate text-xl font-bold text-foreground"
                                >
                                    {{ props.schoolClass.name }}
                                </h1>
                                <p
                                    v-if="props.schoolClass.class_code"
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ props.schoolClass.class_code }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span
                                class="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-semibold text-muted-foreground uppercase"
                                >Sec {{ props.schoolClass.section }}</span
                            >
                            <span
                                v-if="props.schoolClass.school_year"
                                class="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-semibold text-muted-foreground"
                                >{{ props.schoolClass.school_year }}</span
                            >
                            <span
                                v-if="props.schoolClass.semester"
                                class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                >{{
                                    props.schoolClass.semester === 1
                                        ? '1st Sem'
                                        : '2nd Sem'
                                }}</span
                            >
                            <span
                                v-if="props.schoolClass.term"
                                class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300"
                                >{{ props.schoolClass.term }}</span
                            >
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                :class="
                                    props.schoolClass.is_active
                                        ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300'
                                        : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300'
                                "
                            >
                                {{
                                    props.schoolClass.is_active
                                        ? 'Active'
                                        : 'Inactive'
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Subjects -->
                <div
                    v-if="props.schoolClass.subjects.length > 0"
                    class="mt-4 flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="subject in props.schoolClass.subjects"
                        :key="subject.id"
                        class="rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950/30 dark:text-purple-300"
                        :title="subject.name"
                    >
                        {{ subject.code }}
                        <span
                            v-if="subject.program"
                            class="font-normal opacity-60"
                            >· {{ subject.program.code }}</span
                        >
                    </span>
                </div>

                <p
                    v-if="props.schoolClass.faculty"
                    class="mt-4 text-sm text-muted-foreground"
                >
                    <span class="font-medium text-foreground">Faculty:</span>
                    {{ props.schoolClass.faculty.name }}
                </p>
                <p
                    v-if="props.schoolClass.description"
                    class="mt-4 text-sm leading-relaxed text-muted-foreground"
                >
                    {{ props.schoolClass.description }}
                </p>
            </div>
        </div>

        <!-- Stat cards row -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-border bg-card p-4">
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Students
                </p>
                <p class="mt-1 text-2xl font-bold text-foreground">
                    {{ props.students.length }}
                </p>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Research Papers
                </p>
                <p class="mt-1 text-2xl font-bold text-foreground">
                    {{ props.researchPapersCount }}
                </p>
            </div>
            <div
                class="col-span-2 rounded-2xl border border-border bg-card p-4 lg:col-span-1"
            >
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Join Link
                </p>
                <p
                    class="mt-1 text-2xl font-bold"
                    :class="
                        props.schoolClass.join_code
                            ? 'text-green-700 dark:text-green-300'
                            : 'text-muted-foreground/40'
                    "
                >
                    {{ props.schoolClass.join_code ? 'Active' : 'None' }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <!-- Left column: Join link + Research -->
            <div class="space-y-6 lg:col-span-3">
                <!-- Join link card -->
                <div
                    class="overflow-hidden rounded-2xl border border-border bg-card"
                >
                    <div
                        class="flex items-center gap-2 border-b border-border px-5 py-4"
                    >
                        <Link2 class="h-4 w-4 text-teal-500" />
                        <h2 class="text-sm font-bold text-foreground">
                            Student Join Link
                        </h2>
                    </div>

                    <div class="p-5">
                        <div
                            v-if="props.schoolClass.join_code"
                            class="space-y-4"
                        >
                            <div
                                class="overflow-hidden rounded-xl border border-border bg-muted"
                            >
                                <div class="flex items-center gap-3 p-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 dark:bg-teal-950/30"
                                    >
                                        <Link2 class="h-4 w-4 text-teal-500" />
                                    </div>
                                    <p
                                        class="min-w-0 flex-1 cursor-text truncate font-mono text-sm text-muted-foreground select-all"
                                        :title="joinUrl"
                                    >
                                        {{ joinUrl }}
                                    </p>
                                    <Button
                                        size="sm"
                                        :variant="
                                            copied ? 'default' : 'outline'
                                        "
                                        :class="
                                            copied
                                                ? 'border-0 bg-green-500 text-white hover:bg-green-600'
                                                : ''
                                        "
                                        @click="copyJoinLink"
                                        type="button"
                                    >
                                        <CheckCircle2
                                            v-if="copied"
                                            class="mr-1.5 h-3.5 w-3.5"
                                        />
                                        <Copy
                                            v-else
                                            class="mr-1.5 h-3.5 w-3.5"
                                        />
                                        {{ copied ? 'Copied!' : 'Copy' }}
                                    </Button>
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Share this link with students so they can join
                                this class.
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="generateJoinCode"
                                    :disabled="generateJoinCodeForm.processing"
                                >
                                    <RefreshCw
                                        class="mr-1.5 h-3.5 w-3.5"
                                        :class="{
                                            'animate-spin':
                                                generateJoinCodeForm.processing,
                                        }"
                                    />
                                    Regenerate
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="revokeJoinCode"
                                    :disabled="revokeJoinCodeForm.processing"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                >
                                    <Unlink class="mr-1.5 h-3.5 w-3.5" /> Revoke
                                    Link
                                </Button>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center rounded-xl border border-dashed border-border p-8 text-center"
                        >
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 dark:bg-orange-950/30"
                            >
                                <Link2 class="h-6 w-6 text-orange-400" />
                            </div>
                            <p class="text-sm font-semibold text-foreground">
                                No join link generated yet
                            </p>
                            <p
                                class="mt-1 max-w-xs text-xs text-muted-foreground"
                            >
                                Generate a link so students can enroll
                                themselves into this class.
                            </p>
                            <Button
                                @click="generateJoinCode"
                                :disabled="generateJoinCodeForm.processing"
                                class="mt-4 border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600"
                            >
                                Generate Join Link
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Research papers card -->
                <div
                    class="overflow-hidden rounded-2xl border border-border bg-card"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b border-border px-5 py-4"
                    >
                        <div class="flex items-center gap-2">
                            <ScrollText class="h-4 w-4 text-orange-500" />
                            <h2 class="text-sm font-bold text-foreground">
                                Research Papers
                            </h2>
                        </div>
                        <span
                            class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-bold text-orange-600 dark:bg-orange-950/30 dark:text-orange-400"
                        >
                            {{ props.researchPapersCount }}
                        </span>
                    </div>
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 p-5"
                    >
                        <p class="text-sm text-muted-foreground">
                            {{ props.researchPapersCount }} paper{{
                                props.researchPapersCount === 1 ? '' : 's'
                            }}
                            currently in this class.
                        </p>
                        <Button
                            as-child
                            size="sm"
                            class="border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600"
                        >
                            <Link :href="admin.research.index()">
                                <ScrollText class="mr-1.5 h-3.5 w-3.5" /> View
                                Papers
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Right column: Students list -->
            <div
                class="flex max-h-[700px] flex-col overflow-hidden rounded-2xl border border-border bg-card lg:col-span-2"
            >
                <div
                    class="flex shrink-0 items-center justify-between gap-2 border-b border-border px-5 py-4"
                >
                    <div class="flex items-center gap-2">
                        <Users class="h-4 w-4 text-teal-500" />
                        <h2 class="text-sm font-bold text-foreground">
                            Students
                        </h2>
                    </div>
                    <span
                        class="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-bold text-teal-700 dark:bg-teal-950/30 dark:text-teal-300"
                    >
                        {{ props.students.length }}
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div
                        v-if="props.students.length === 0"
                        class="flex h-full min-h-[200px] flex-col items-center justify-center p-8 text-center"
                    >
                        <div
                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-teal-950/30"
                        >
                            <Users class="h-7 w-7 text-teal-400" />
                        </div>
                        <h3 class="text-sm font-bold text-foreground">
                            No students yet
                        </h3>
                        <p
                            class="mt-1 max-w-[200px] text-xs text-muted-foreground"
                        >
                            Share the join link to get students enrolled.
                        </p>
                    </div>

                    <ul v-else class="divide-y divide-border/50">
                        <li
                            v-for="student in props.students"
                            :key="student.id"
                            class="flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-muted/50"
                        >
                            <div
                                class="h-9 w-9 shrink-0 overflow-hidden rounded-full"
                            >
                                <img
                                    v-if="student.avatar_url"
                                    :src="student.avatar_url"
                                    :alt="student.name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-orange-400 to-teal-400 text-[11px] font-bold text-white"
                                >
                                    {{ getInitials(student.name) }}
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium text-foreground"
                                >
                                    {{ student.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ student.email }}
                                </p>
                            </div>
                            <span
                                class="hidden text-[10px] whitespace-nowrap text-muted-foreground sm:block"
                                >{{ formatDate(student.joined_at) }}</span
                            >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-7 w-7 shrink-0 text-muted-foreground hover:text-red-500"
                                @click="removeStudent(student.id)"
                                :disabled="removeStudentForm.processing"
                                title="Remove student"
                            >
                                <UserMinus class="h-3.5 w-3.5" />
                            </Button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
