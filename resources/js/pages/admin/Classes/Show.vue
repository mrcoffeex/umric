<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Users, Copy, RefreshCw, Unlink, UserMinus, ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import AdminSchoolClassController from '@/actions/App/Http/Controllers/Admin/SchoolClassController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import admin from '@/routes/admin';

type Props = {
    class: {
        id: number;
        name: string;
        class_code: string | null;
        section: string;
        school_year: string | null;
        semester: number | null;
        term: string | null;
        description: string | null;
        is_active: boolean;
        join_code: string | null;
        faculty: { id: number; name: string } | null;
        subjects: Array<{ id: number; name: string; code: string; program: { name: string; code: string } | null }>;
    };
    students: Array<{
        id: number;
        name: string;
        email: string;
        avatar_url: string | null;
        joined_at: string;
    }>;
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
    props.class.join_code ? `${window.location.origin}/classes/join/${props.class.join_code}` : '',
);

const copyJoinLink = () => {
    if (joinUrl.value) navigator.clipboard.writeText(joinUrl.value);
};

const generateJoinCodeForm = useForm({});
const revokeJoinCodeForm = useForm({});
const removeStudentForm = useForm({});

const generateJoinCode = () => {
    generateJoinCodeForm.post(AdminSchoolClassController.generateJoinCode.url({ class: props.class.id }), {
        preserveScroll: true,
    });
};

const revokeJoinCode = () => {
    if (confirm('Revoke this join link? Students will no longer be able to use it.')) {
        revokeJoinCodeForm.delete(AdminSchoolClassController.revokeJoinCode.url({ class: props.class.id }), {
            preserveScroll: true,
        });
    }
};

const removeStudent = (studentId: number) => {
    if (confirm('Remove this student from the class?')) {
        removeStudentForm.delete(AdminSchoolClassController.removeStudent.url({ class: props.class.id, student: studentId }), {
            preserveScroll: true,
        });
    }
};

const getInitials = (name: string) =>
    name.split(' ').slice(0, 2).map((n) => n[0]).join('').toUpperCase();

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
</script>

<template>
    <Head :title="`Class: ${props.class.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <Link :href="admin.classes.index()" class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ChevronLeft class="h-4 w-4" /> Back to Classes
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-sidebar-border/70 bg-white dark:bg-sidebar">
                    <div class="border-b border-sidebar-border/50 px-5 pb-4 pt-5">
                        <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ props.class.name }}</h1>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span v-if="props.class.section" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">Sec {{ props.class.section }}</span>
                            <span v-if="props.class.school_year" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ props.class.school_year }}</span>
                            <span v-if="props.class.semester" class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">{{ props.class.semester === 1 ? '1st Sem' : '2nd Sem' }}</span>
                            <span v-if="props.class.term" class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">{{ props.class.term }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="props.class.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                                {{ props.class.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div v-if="props.class.subjects.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="subject in props.class.subjects" :key="subject.id" class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-950/30 dark:text-violet-400" :title="subject.name">
                                {{ subject.code }}<span v-if="subject.program" class="font-normal opacity-70"> · {{ subject.program.code }}</span>
                            </span>
                        </div>
                        <p v-if="props.class.faculty" class="mt-3 text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">Faculty:</span> {{ props.class.faculty.name }}
                        </p>
                        <p v-if="props.class.description" class="mt-3 text-sm text-muted-foreground">{{ props.class.description }}</p>
                    </div>

                    <div class="space-y-5 p-5">
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Student Join Link</p>
                            <div v-if="props.class.join_code" class="space-y-3">
                                <Input :value="joinUrl" readonly class="bg-slate-50 font-mono text-sm dark:bg-slate-900/50" />
                                <div class="flex flex-wrap items-center gap-2">
                                    <Button size="sm" variant="outline" @click="copyJoinLink" type="button">
                                        <Copy class="mr-1.5 h-3.5 w-3.5" /> Copy Link
                                    </Button>
                                    <Button size="sm" variant="ghost" @click="generateJoinCode" :disabled="generateJoinCodeForm.processing" class="border border-input">
                                        <RefreshCw class="mr-1.5 h-3.5 w-3.5" :class="{ 'animate-spin': generateJoinCodeForm.processing }" /> Regenerate
                                    </Button>
                                    <Button size="sm" variant="ghost" @click="revokeJoinCode" :disabled="revokeJoinCodeForm.processing" class="text-destructive hover:bg-destructive/10 hover:text-destructive">
                                        <Unlink class="mr-1.5 h-3.5 w-3.5" /> Revoke
                                    </Button>
                                </div>
                            </div>
                            <div v-else class="space-y-3">
                                <p class="text-sm text-muted-foreground">No join link generated yet.</p>
                                <Button @click="generateJoinCode" :disabled="generateJoinCodeForm.processing" class="border-0 bg-orange-500 text-white shadow shadow-orange-500/20 hover:bg-orange-600">
                                    Generate Join Link
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex h-full max-h-[800px] flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-white dark:bg-sidebar lg:col-span-1">
                <div class="flex shrink-0 items-center justify-between border-b border-sidebar-border/50 px-5 pb-4 pt-5">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Students</h2>
                    <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-950/30 dark:text-orange-400">{{ props.students.length }}</span>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div v-if="props.students.length === 0" class="flex h-full flex-col items-center justify-center p-8 text-center">
                        <div class="mb-4 rounded-2xl bg-orange-50 p-4 dark:bg-orange-950/30">
                            <Users class="h-8 w-8 text-orange-500" />
                        </div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">No students enrolled</p>
                        <p class="mt-2 max-w-[200px] text-xs text-muted-foreground">Students join via the join link.</p>
                    </div>
                    <ul v-else class="divide-y divide-sidebar-border/30">
                        <li v-for="student in props.students" :key="student.id" class="flex items-center gap-3 p-4">
                            <div class="h-9 w-9 shrink-0 overflow-hidden rounded-full">
                                <img v-if="student.avatar_url" :src="student.avatar_url" :alt="student.name" class="h-full w-full object-cover" />
                                <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-orange-400 to-teal-400 text-xs font-bold text-white">
                                    {{ getInitials(student.name) }}
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ student.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ student.email }}</p>
                                <p class="text-[10px] text-muted-foreground">Joined {{ formatDate(student.joined_at) }}</p>
                            </div>
                            <Button variant="ghost" size="icon" class="h-8 w-8 shrink-0 text-muted-foreground hover:text-red-500" @click="removeStudent(student.id)" :disabled="removeStudentForm.processing">
                                <UserMinus class="h-3.5 w-3.5" />
                            </Button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
