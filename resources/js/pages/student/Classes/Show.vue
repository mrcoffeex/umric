<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, GraduationCap, ScrollText, Users } from 'lucide-vue-next';
import student from '@/routes/student';

type Props = {
    schoolClass: {
        id: number;
        name: string;
        class_code: string | null;
        section: string;
        school_year: string | null;
        semester: number | null;
        description: string | null;
        is_active: boolean;
        faculty: { id: number; name: string } | null;
        subjects: Array<{ id: number; name: string; code: string; program: { name: string; code: string } | null }>;
    };
    students: Array<{
        id: number;
        name: string;
        avatar_url: string | null;
        joined_at: string;
    }>;
    researchPapersCount: number;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My Classes', href: student.classes.index() },
            { title: 'Class Details', href: '' },
        ],
    },
});

const getInitials = (name: string) =>
    name.split(' ').slice(0, 2).map((n) => n[0]).join('').toUpperCase();

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
</script>

<template>
    <Head :title="`Class: ${props.schoolClass.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Back nav -->
        <div>
            <Link :href="student.classes.index()" class="flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground">
                <ChevronLeft class="h-4 w-4" /> Back to My Classes
            </Link>
        </div>

        <!-- Class header -->
        <div class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-teal-500 to-orange-500" />
            <div class="p-5 md:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 dark:bg-teal-950/30">
                        <GraduationCap class="h-5 w-5 text-teal-500" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold text-foreground">{{ props.schoolClass.name }}</h1>
                        <p v-if="props.schoolClass.class_code" class="font-mono text-xs text-muted-foreground">{{ props.schoolClass.class_code }}</p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span class="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-semibold uppercase text-muted-foreground">Sec {{ props.schoolClass.section }}</span>
                    <span v-if="props.schoolClass.school_year" class="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-semibold text-muted-foreground">{{ props.schoolClass.school_year }}</span>
                    <span v-if="props.schoolClass.semester" class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ props.schoolClass.semester === 1 ? '1st Sem' : '2nd Sem' }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold" :class="props.schoolClass.is_active ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300'">
                        {{ props.schoolClass.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <!-- Subjects -->
                <div v-if="props.schoolClass.subjects.length > 0" class="mt-4 flex flex-wrap gap-1.5">
                    <span
                        v-for="subject in props.schoolClass.subjects"
                        :key="subject.id"
                        class="rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950/30 dark:text-purple-300"
                        :title="subject.name"
                    >
                        {{ subject.code }}
                        <span v-if="subject.program" class="font-normal opacity-60">· {{ subject.program.code }}</span>
                    </span>
                </div>

                <p v-if="props.schoolClass.faculty" class="mt-4 text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">Instructor:</span> {{ props.schoolClass.faculty.name }}
                </p>
                <p v-if="props.schoolClass.description" class="mt-4 text-sm leading-relaxed text-muted-foreground">{{ props.schoolClass.description }}</p>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-2xl border border-border bg-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Classmates</p>
                <p class="mt-1 text-2xl font-bold text-foreground">{{ props.students.length }}</p>
            </div>
            <div class="rounded-2xl border border-border bg-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Research Papers</p>
                <p class="mt-1 text-2xl font-bold text-foreground">{{ props.researchPapersCount }}</p>
            </div>
        </div>

        <!-- Classmates list -->
        <div class="flex max-h-[600px] flex-col overflow-hidden rounded-2xl border border-border bg-card">
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-border px-5 py-4">
                <div class="flex items-center gap-2">
                    <Users class="h-4 w-4 text-teal-500" />
                    <h2 class="text-sm font-bold text-foreground">Classmates</h2>
                </div>
                <span class="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-bold text-teal-700 dark:bg-teal-950/30 dark:text-teal-300">
                    {{ props.students.length }}
                </span>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div v-if="props.students.length === 0" class="flex h-full min-h-[200px] flex-col items-center justify-center p-8 text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-teal-950/30">
                        <Users class="h-7 w-7 text-teal-400" />
                    </div>
                    <h3 class="text-sm font-bold text-foreground">No classmates yet</h3>
                    <p class="mt-1 max-w-[200px] text-xs text-muted-foreground">Other students will appear here once they join.</p>
                </div>

                <ul v-else class="divide-y divide-border/50">
                    <li v-for="s in props.students" :key="s.id" class="flex items-center gap-3 px-5 py-3.5">
                        <div class="h-9 w-9 shrink-0 overflow-hidden rounded-full">
                            <img v-if="s.avatar_url" :src="s.avatar_url" :alt="s.name" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-teal-400 to-orange-400 text-[11px] font-bold text-white">
                                {{ getInitials(s.name) }}
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-foreground">{{ s.name }}</p>
                        </div>
                        <span class="hidden whitespace-nowrap text-[10px] text-muted-foreground sm:block">Joined {{ formatDate(s.joined_at) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
