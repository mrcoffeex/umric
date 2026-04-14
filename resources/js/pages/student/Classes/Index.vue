<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, GraduationCap, School, UserRoundPlus } from 'lucide-vue-next';
import { ref } from 'vue';
import classesRoutes from '@/routes/classes';
import student from '@/routes/student';

interface ClassInfo {
    id: number;
    name: string;
    section?: string | null;
    school_year?: string | null;
    semester?: number | null;
    class_code?: string | null;
    description?: string | null;
    faculty?: { id: number; name: string } | null;
    subjects?: Array<{ id: number; name: string; code?: string; program?: { id: number; name: string } | null }>;
    research_papers?: Array<{ id: number; title: string; current_step: string; tracking_id: string }>;
}

interface Props {
    classes: ClassInfo[];
}

defineProps<Props>();

const joinCode = ref('');

function joinClassUrl(): string {
    if (!joinCode.value.trim()) {
        return '#';
    }

    return classesRoutes.join.show.url({ code: joinCode.value.trim() });
}

function semesterLabel(semester?: number | null): string {
    if (!semester) {
        return '-';
    }

    const labels: Record<number, string> = { 1: '1st Semester', 2: '2nd Semester', 3: 'Summer' };

    return labels[semester] ?? `Semester ${semester}`;
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My Classes', href: student.classes.index() }],
    },
});
</script>

<template>
    <Head title="My Classes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-teal-500 to-orange-500" />
            <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">My Classes</h1>
                    <p class="mt-1 text-sm text-muted-foreground">View your enrolled classes and subjects.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="joinCode"
                        type="text"
                        placeholder="Class code"
                        class="w-40 rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                    <Link
                        :href="joinClassUrl()"
                        class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600"
                    >
                        <UserRoundPlus class="h-3.5 w-3.5" />
                        Join
                    </Link>
                </div>
            </div>
        </section>

        <div v-if="classes.length === 0" class="rounded-2xl border border-dashed border-border bg-card p-10 text-center">
            <School class="mx-auto h-10 w-10 text-muted-foreground/50" />
            <p class="mt-3 text-sm font-semibold text-foreground">No classes yet</p>
            <p class="mt-1 text-sm text-muted-foreground">Enter a class code above to join your first class.</p>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="classItem in classes"
                :key="classItem.id"
                class="overflow-hidden rounded-2xl border border-border bg-card"
            >
                <div class="h-1 bg-gradient-to-r from-teal-500 to-teal-300 dark:to-teal-700" />
                <div class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-base font-bold text-foreground">{{ classItem.name }}</h3>
                            <p v-if="classItem.section" class="text-xs text-muted-foreground">Section {{ classItem.section }}</p>
                        </div>
                        <GraduationCap class="h-5 w-5 shrink-0 text-teal-500" />
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <div v-if="classItem.faculty" class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Instructor</span>
                            <span class="text-sm text-foreground">{{ classItem.faculty.name }}</span>
                        </div>

                        <div v-if="classItem.school_year || classItem.semester" class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Period</span>
                            <span class="text-sm text-foreground">
                                {{ classItem.school_year ?? '' }}
                                <template v-if="classItem.school_year && classItem.semester"> · </template>
                                {{ semesterLabel(classItem.semester) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="classItem.subjects?.length" class="mt-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Subjects</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="subject in classItem.subjects"
                                :key="subject.id"
                                class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 dark:bg-violet-950/30 dark:text-violet-300"
                            >
                                <BookOpen class="h-3 w-3" />
                                {{ subject.code ?? subject.name }}
                            </span>
                        </div>
                    </div>

                    <div v-if="classItem.research_papers?.length" class="mt-4 border-t border-border pt-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">My Research</p>
                        <div
                            v-for="paper in classItem.research_papers"
                            :key="paper.id"
                            class="rounded-lg bg-muted/50 px-3 py-2"
                        >
                            <p class="truncate text-sm font-medium text-foreground">{{ paper.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ paper.tracking_id }}</p>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
