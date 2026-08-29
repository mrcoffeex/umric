<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, School, ScrollText, UserRoundPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SmartInsights from '@/components/dashboard/SmartInsights.vue';
import type {
    InsightAction,
    InsightHealth,
} from '@/components/dashboard/SmartInsights.vue';
import StudentResearchProgress from '@/components/student/StudentResearchProgress.vue';
import classesRoutes from '@/routes/classes';
import student from '@/routes/student';

interface ClassInfo {
    id: string;
    name: string;
    section?: string | null;
    class_code?: string | null;
    subjects?: Array<{ id: string; name: string; code?: string }>;
}

interface Paper {
    id: string;
    title: string;
    tracking_id: string;
    current_step: string;
    step_label?: string;
    step_ric_review?: string | null;
    step_outline_defense?: string | null;
    step_data_gathering?: string | null;
    step_rating?: string | null;
    step_final_manuscript?: string | null;
    step_final_defense?: string | null;
    step_hard_bound?: string | null;
    is_returned?: boolean;
    last_update?: {
        step: string;
        action: string;
        status: string;
        notes?: string | null;
        at?: string | null;
    } | null;
    upcoming_defense?: { type: string; at: string } | null;
}

interface Props {
    classes: ClassInfo[];
    paper: Paper | null;
    stepLabels: Record<string, string>;
    steps: string[];
    hasClass: boolean;
    insights: {
        actions: InsightAction[];
        health: InsightHealth[];
    };
}

defineProps<Props>();
const page = usePage();
const joinCode = ref('');

const userName = computed(() => {
    return String(
        (page.props as { auth?: { user?: { name?: string } } }).auth?.user
            ?.name ?? 'Student',
    );
});

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

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

    <div class="flex h-full flex-1 flex-col gap-5 p-4 md:gap-6 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold text-foreground">
                    {{ greeting }}, {{ userName }}
                </h1>
            </div>
            <Link
                :href="student.research.index.url()"
                class="inline-flex min-h-9 items-center gap-1 text-xs font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400"
            >
                <ScrollText class="h-3.5 w-3.5" />
                My Research
            </Link>
        </div>

        <SmartInsights :insights="insights">
            <template #primary-cta>
                <div
                    class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[18rem] sm:flex-row sm:items-center"
                >
                    <input
                        v-model="joinCode"
                        type="text"
                        placeholder="Enter class code"
                        class="h-11 min-h-11 w-full rounded-xl border border-input bg-background px-3 text-base transition outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-400/30 md:text-sm"
                        autocomplete="off"
                        spellcheck="false"
                    />
                    <Link
                        :href="joinClassUrl()"
                        class="inline-flex h-11 min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-teal-500 px-4 text-sm font-semibold text-white transition hover:bg-teal-600 focus-visible:ring-2 focus-visible:ring-teal-400/40 focus-visible:outline-none active:scale-[0.98]"
                    >
                        <UserRoundPlus class="h-4 w-4" />
                        Join class
                    </Link>
                </div>
            </template>
        </SmartInsights>

        <StudentResearchProgress
            v-if="paper"
            :paper="paper"
            :step-labels="stepLabels"
            :steps="steps"
        />

        <section
            v-if="classes.length > 0"
            class="rounded-2xl border border-border bg-card"
        >
            <div
                class="flex items-center justify-between border-b border-border px-5 py-4"
            >
                <div class="flex items-center gap-2">
                    <School class="h-4 w-4 text-teal-500" />
                    <h2 class="text-base font-bold text-foreground">
                        My class
                    </h2>
                </div>
                <Link
                    :href="student.classes.index.url()"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-teal-600 hover:text-teal-700"
                >
                    View all
                    <ArrowRight class="h-3 w-3" />
                </Link>
            </div>

            <div class="grid gap-3 p-5 sm:grid-cols-2">
                <article
                    v-for="classItem in classes.slice(0, 2)"
                    :key="classItem.id"
                    class="rounded-xl border border-border px-4 py-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-bold text-foreground">
                            {{ classItem.name }}
                        </h3>
                        <span
                            v-if="classItem.section"
                            class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                        >
                            {{ classItem.section }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            (classItem.subjects ?? []).length
                                ? `${(classItem.subjects ?? []).length} subject${(classItem.subjects ?? []).length === 1 ? '' : 's'}`
                                : 'No subjects listed'
                        }}
                        <span v-if="(classItem.subjects ?? []).length">
                            ·
                            {{
                                (classItem.subjects ?? [])
                                    .map((s) => s.code ?? s.name)
                                    .slice(0, 3)
                                    .join(', ')
                            }}
                        </span>
                    </p>
                </article>
            </div>
        </section>
    </div>
</template>
