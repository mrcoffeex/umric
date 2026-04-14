<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Bell, Pin, School, UserRoundPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { getStepStatusClass } from '@/lib/step-colors';
import classesRoutes from '@/routes/classes';
import student from '@/routes/student';

interface Announcement {
    id: number;
    title: string;
    content: string;
    type: 'info' | 'success' | 'warning' | 'danger';
    is_pinned: boolean;
    published_at?: string | null;
}

interface ClassInfo {
    id: number;
    name: string;
    section?: string | null;
    class_code?: string | null;
    subjects?: Array<{ id: number; name: string; code?: string }>;
}

interface Paper {
    id: number;
    title: string;
    tracking_id: string;
    current_step: string;
    step_label?: string;
}

interface Props {
    announcements: Announcement[];
    classes: ClassInfo[];
    paper: Paper | null;
    stepLabels: Record<string, string>;
    steps: string[];
}

const props = defineProps<Props>();
const page = usePage();

const joinCode = ref('');

const userName = computed(() => {
    return String((page.props as { auth?: { user?: { name?: string } } }).auth?.user?.name ?? 'Student');
});

const announcementTypeStyles: Record<Announcement['type'], string> = {
    info: 'border-l-blue-500',
    success: 'border-l-green-500',
    warning: 'border-l-amber-500',
    danger: 'border-l-red-500',
};

const currentStepIndex = computed(() => {
    if (!props.paper) {
        return -1;
    }

    return props.steps.indexOf(props.paper.current_step);
});

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function formatDate(value?: string | null): string {
    if (!value) {
        return 'Recently posted';
    }

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function isCompletedStep(index: number): boolean {
    return currentStepIndex.value > index;
}

function isCurrentStep(index: number): boolean {
    return currentStepIndex.value === index;
}

function joinClassUrl(): string {
    if (!joinCode.value.trim()) {
        return '#';
    }

    return classesRoutes.join.show.url({ code: joinCode.value.trim() });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Student Home', href: student.home() }],
    },
});
</script>

<template>
    <Head title="Student Home" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <section class="overflow-hidden rounded-2xl border border-border bg-card">
            <div class="h-1 bg-gradient-to-r from-orange-500 to-teal-500" />
            <div class="p-5">
                <h1 class="text-2xl font-bold text-foreground">Welcome, {{ userName }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">Stay updated with your classes and research progress.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5">
            <div class="mb-4 flex items-center gap-2">
                <Bell class="h-4 w-4 text-orange-500" />
                <h2 class="text-base font-bold text-foreground">Announcements</h2>
            </div>

            <div v-if="announcements.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                No announcements at the moment.
            </div>

            <div v-else class="grid gap-3 md:grid-cols-2">
                <article
                    v-for="item in announcements"
                    :key="item.id"
                    :class="['rounded-xl border border-border border-l-4 bg-card p-4', announcementTypeStyles[item.type]]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-bold text-foreground">{{ item.title }}</h3>
                        <Pin v-if="item.is_pinned" class="h-4 w-4 text-orange-500" />
                    </div>
                    <p class="mt-2 text-sm text-foreground">{{ item.content }}</p>
                    <p class="mt-2 text-xs text-muted-foreground">{{ formatDate(item.published_at) }}</p>
                </article>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5">
            <div class="mb-4 flex items-center gap-2">
                <School class="h-4 w-4 text-teal-500" />
                <h2 class="text-base font-bold text-foreground">Classes</h2>
            </div>

            <div v-if="classes.length === 0" class="rounded-xl border border-dashed border-border p-6">
                <p class="text-sm font-semibold text-foreground">You haven't joined a class yet.</p>
                <p class="mt-1 text-sm text-muted-foreground">Enter a class code to join.</p>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <input
                        v-model="joinCode"
                        type="text"
                        placeholder="Enter class code"
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/30 sm:max-w-xs"
                    />
                    <Link
                        :href="joinClassUrl()"
                        class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-600 disabled:opacity-50"
                    >
                        <UserRoundPlus class="h-3.5 w-3.5" />
                        Join a Class
                    </Link>
                </div>
            </div>

            <div v-else class="grid gap-3 md:grid-cols-2">
                <article
                    v-for="classItem in classes"
                    :key="classItem.id"
                    class="rounded-xl border border-border bg-card p-4"
                >
                    <h3 class="text-sm font-bold text-foreground">{{ classItem.name }}</h3>
                    <p class="mt-1 text-xs text-muted-foreground">Section: {{ classItem.section ?? '-' }}</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span
                            v-for="subject in classItem.subjects ?? []"
                            :key="subject.id"
                            :class="[
                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                subject.code
                                    ? 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                    : 'bg-muted text-muted-foreground',
                            ]"
                        >
                            {{ subject.code ?? subject.name }}
                        </span>
                        <span v-if="!(classItem.subjects ?? []).length" class="text-xs text-muted-foreground">No subjects listed</span>
                    </div>
                </article>
            </div>
        </section>

        <section class="rounded-2xl border border-border bg-card p-5">
            <h2 class="text-base font-bold text-foreground">Research</h2>

            <div v-if="!paper" class="mt-3 rounded-xl border border-dashed border-border p-6">
                <p class="text-sm font-semibold text-foreground">No research paper yet.</p>
                <p class="mt-1 text-sm text-muted-foreground">Submit your title proposal to start your workflow.</p>
                <Link
                    :href="student.research.index.url()"
                    class="mt-3 inline-flex rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50"
                >
                    Submit Title Proposal
                </Link>
            </div>

            <div v-else class="mt-3 rounded-xl border border-border bg-card p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-bold text-foreground">{{ paper.title }}</p>
                        <p class="text-xs text-muted-foreground">Tracking ID: {{ paper.tracking_id }}</p>
                    </div>
                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-950/40 dark:text-orange-300">
                        {{ paper.step_label ?? stepLabel(paper.current_step) }}
                    </span>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                    <div
                        v-for="(step, idx) in steps"
                        :key="step"
                        :aria-current="isCurrentStep(idx) ? 'step' : undefined"
                        :data-completed="isCompletedStep(idx) || undefined"
                        :class="['rounded-xl border px-3 py-2 text-center text-sm font-semibold', getStepStatusClass(step, paper.current_step, steps)]"
                    >
                        {{ stepLabel(step) }}
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
