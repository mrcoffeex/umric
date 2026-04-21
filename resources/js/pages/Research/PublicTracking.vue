<script setup lang="ts">
import {
    BookOpen,
    Calendar,
    ExternalLink,
    GraduationCap,
    Hash,
    Moon,
    Quote,
    Sun,
    Tag,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import TrackingTimeline from '@/components/TrackingTimeline.vue';
import { useAppearance } from '@/composables/useAppearance';

interface Author {
    id: string;
    name: string;
    pivot?: { author_order?: number };
}

interface Category {
    id: string;
    name: string;
}

interface SchoolClass {
    id: string;
    name: string;
    section?: string;
}

interface Publication {
    id: string;
    journal_name: string;
    doi?: string;
    publisher?: string;
    volume?: number;
    issue?: number;
}

interface Citation {
    id: string;
    citation_text: string;
    format?: string;
}

interface TrackingRecord {
    id: string;
    step?: string;
    action?: string;
    status?: string;
    created_at: string;
    notes?: string;
}

interface Paper {
    id: string;
    title: string;
    abstract: string;
    status: string;
    current_step: string;
    tracking_id: string;
    created_at: string;
    progress?: number;
    keywords?: string;
    proponents?: string[] | string | null;
    category?: Category;
    school_class?: SchoolClass;
    authors?: Author[];
    publication?: Publication[];
    citations?: Citation[];
    tracking_records?: TrackingRecord[];
}

interface Props {
    paper: Paper;
    steps: string[];
    stepLabels: Record<string, string>;
}

const props = defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();

function toggleTheme() {
    updateAppearance(
        appearance.value === 'dark'
            ? 'light'
            : appearance.value === 'light'
              ? 'dark'
              : 'dark',
    );
}

const proponents = computed(() => {
    if (!props.paper.proponents) {
        return [];
    }

    if (Array.isArray(props.paper.proponents)) {
        return props.paper.proponents.map((p) =>
            typeof p === 'string' ? p : ((p as any).name ?? p),
        );
    }

    return (props.paper.proponents as string)
        .split(',')
        .map((v) => v.trim())
        .filter(Boolean);
});

const currentStepIndex = computed(() =>
    props.steps.indexOf(props.paper.current_step),
);
const progressPercent = computed(() => {
    if (props.steps.length <= 1) {
        return 0;
    }

    return Math.round(
        (currentStepIndex.value / (props.steps.length - 1)) * 100,
    );
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <div
        class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100"
    >
        <!-- Sticky Header -->
        <header
            class="sticky top-0 z-50 border-b border-gray-200/80 bg-white/80 backdrop-blur-lg dark:border-gray-800/80 dark:bg-gray-950/80"
        >
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-amber-500 shadow-sm"
                    >
                        <BookOpen class="h-4 w-4 text-white" />
                    </div>
                    <span
                        class="text-sm font-bold tracking-tight text-gray-900 dark:text-gray-100"
                        >Research Tracker</span
                    >
                </div>
                <button
                    @click="toggleTheme"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    <Sun v-if="appearance === 'dark'" class="h-4 w-4" />
                    <Moon v-else class="h-4 w-4" />
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 sm:py-8">
            <!-- Hero Card -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="p-5 sm:p-6">
                    <h1
                        class="text-xl leading-tight font-bold text-gray-900 sm:text-2xl md:text-3xl dark:text-gray-50"
                    >
                        {{ paper.title }}
                    </h1>
                    <p
                        class="mt-3 line-clamp-3 text-sm leading-relaxed text-gray-500 sm:text-base dark:text-gray-400"
                    >
                        {{ paper.abstract }}
                    </p>

                    <!-- Meta chips -->
                    <div class="mt-5 flex flex-wrap items-center gap-2.5">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-400"
                        >
                            {{
                                stepLabels[paper.current_step] ??
                                paper.current_step
                            }}
                        </span>
                        <span
                            v-if="paper.category"
                            class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-500/10 dark:text-violet-400"
                        >
                            <Tag class="h-3 w-3" />
                            {{ paper.category.name }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                        >
                            <Calendar class="h-3 w-3" />
                            {{ formatDate(paper.created_at) }}
                        </span>
                    </div>
                </div>

                <!-- Progress bar strip -->
                <div
                    class="border-t border-gray-100 bg-gray-50/50 px-5 py-3.5 sm:px-6 dark:border-gray-800 dark:bg-gray-950/30"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span
                            class="font-medium text-gray-500 dark:text-gray-400"
                            >Progress</span
                        >
                        <span
                            class="font-bold text-orange-600 tabular-nums dark:text-orange-400"
                            >{{ progressPercent }}%</span
                        >
                    </div>
                    <div
                        class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-orange-500 to-amber-400 transition-all duration-700 ease-out"
                            :style="{ width: progressPercent + '%' }"
                        />
                    </div>
                </div>
            </div>

            <!-- Grid: Timeline + Sidebar -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Timeline -->
                <div class="lg:col-span-2">
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <h2
                            class="mb-5 flex items-center gap-2 text-base font-bold text-gray-900 sm:text-lg dark:text-gray-50"
                        >
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-500/10"
                            >
                                <Calendar class="h-3.5 w-3.5 text-orange-500" />
                            </div>
                            Timeline
                        </h2>
                        <TrackingTimeline
                            :current-step="paper.current_step"
                            :steps="steps"
                            :step-labels="stepLabels"
                            :tracking="paper.tracking_records || []"
                        />
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Tracking ID -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <h3
                            class="mb-2.5 flex items-center gap-2 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            <Hash class="h-3.5 w-3.5" />
                            Tracking ID
                        </h3>
                        <div
                            class="rounded-lg border border-orange-200 bg-orange-50/60 px-3 py-2.5 font-mono text-sm font-bold break-all text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400"
                        >
                            {{ paper.tracking_id }}
                        </div>
                    </div>

                    <!-- Proponents -->
                    <div
                        v-if="proponents.length"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <h3
                            class="mb-3 flex items-center gap-2 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            <Users class="h-3.5 w-3.5" />
                            Proponents
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="name in proponents"
                                :key="name"
                                class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >{{ name }}</span
                            >
                        </div>
                    </div>

                    <!-- Authors -->
                    <div
                        v-if="paper.authors && paper.authors.length > 0"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <h3
                            class="mb-3 flex items-center gap-2 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            <Users class="h-3.5 w-3.5" />
                            Authors
                        </h3>
                        <div
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <div
                                v-for="(author, index) in paper.authors"
                                :key="author.id"
                                class="flex items-center justify-between gap-2 py-2.5 first:pt-0 last:pb-0"
                            >
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                >
                                    {{ author.name }}
                                </p>
                                <span
                                    class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    #{{
                                        author.pivot?.author_order || index + 1
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Class -->
                    <div
                        v-if="paper.school_class"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <h3
                            class="mb-2.5 flex items-center gap-2 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            <GraduationCap class="h-3.5 w-3.5" />
                            Class
                        </h3>
                        <p
                            class="text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                            {{ paper.school_class.name }}
                            <span
                                v-if="paper.school_class.section"
                                class="text-gray-400 dark:text-gray-500"
                            >
                                · Section {{ paper.school_class.section }}</span
                            >
                        </p>
                    </div>

                    <!-- Keywords -->
                    <div
                        v-if="paper.keywords"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <h3
                            class="mb-3 flex items-center gap-2 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            <Tag class="h-3.5 w-3.5" />
                            Keywords
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="keyword in paper.keywords.split(',')"
                                :key="keyword.trim()"
                                class="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-500/10 dark:text-orange-400"
                            >
                                {{ keyword.trim() }}
                            </span>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <h3
                            class="mb-3 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            Quick Stats
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-xl bg-gray-50 px-3 py-2.5 text-center dark:bg-gray-800/60"
                            >
                                <p
                                    class="text-lg font-bold text-gray-900 tabular-nums dark:text-gray-100"
                                >
                                    {{ (paper.publication || []).length }}
                                </p>
                                <p
                                    class="text-[10px] font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Pubs
                                </p>
                            </div>
                            <div
                                class="rounded-xl bg-gray-50 px-3 py-2.5 text-center dark:bg-gray-800/60"
                            >
                                <p
                                    class="text-lg font-bold text-gray-900 tabular-nums dark:text-gray-100"
                                >
                                    {{ (paper.citations || []).length }}
                                </p>
                                <p
                                    class="text-[10px] font-medium text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Cites
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Publications & Citations -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Publications -->
                <div
                    v-if="paper.publication && paper.publication.length > 0"
                    class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-800 dark:bg-gray-900"
                >
                    <h2
                        class="mb-4 flex items-center gap-2 text-base font-bold text-gray-900 dark:text-gray-50"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10"
                        >
                            <BookOpen class="h-3.5 w-3.5 text-emerald-500" />
                        </div>
                        Publications
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="pub in paper.publication"
                            :key="pub.id"
                            class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/30"
                        >
                            <h4
                                class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                            >
                                {{ pub.journal_name }}
                            </h4>
                            <div
                                class="mt-2 space-y-1 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <p
                                    v-if="pub.doi"
                                    class="flex items-center gap-1.5"
                                >
                                    <ExternalLink class="h-3 w-3 shrink-0" />
                                    <code
                                        class="font-mono text-gray-600 dark:text-gray-300"
                                        >{{ pub.doi }}</code
                                    >
                                </p>
                                <p v-if="pub.publisher">
                                    Publisher: {{ pub.publisher }}
                                </p>
                                <p v-if="pub.volume || pub.issue">
                                    Vol. {{ pub.volume }}, Issue {{ pub.issue }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Citations -->
                <div
                    v-if="paper.citations && paper.citations.length > 0"
                    class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 dark:border-gray-800 dark:bg-gray-900"
                >
                    <h2
                        class="mb-4 flex items-center gap-2 text-base font-bold text-gray-900 dark:text-gray-50"
                    >
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-500/10"
                        >
                            <Quote class="h-3.5 w-3.5 text-amber-500" />
                        </div>
                        Citations
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="citation in paper.citations"
                            :key="citation.id"
                            class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/30"
                        >
                            <p
                                class="text-xs leading-relaxed text-gray-700 dark:text-gray-300"
                            >
                                {{ citation.citation_text }}
                            </p>
                            <p
                                v-if="citation.format"
                                class="mt-2 text-[10px] font-semibold tracking-wide text-gray-400 uppercase dark:text-gray-500"
                            >
                                {{ citation.format }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-8 border-t border-gray-200 dark:border-gray-800">
            <div class="mx-auto max-w-5xl px-4 py-6 text-center sm:px-6">
                <p class="text-xs text-gray-400 dark:text-gray-600">
                    University Research Information Center &middot; Research
                    Tracking System
                </p>
            </div>
        </footer>
    </div>
</template>
