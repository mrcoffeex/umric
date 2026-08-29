<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Calendar,
    Check,
    ChevronDown,
    Copy,
    ExternalLink,
    GraduationCap,
    Hash,
    Quote,
    Search,
    Tag,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TrackingTimeline from '@/components/TrackingTimeline.vue';
import { Button } from '@/components/ui/button';
import { useBranding } from '@/composables/useBranding';
import {
    workflowFocusStepKey,
    workflowProgressPercent,
} from '@/lib/research-workflow-ui';
import type { WorkflowStepSetup } from '@/lib/workflow-step-config';
import { documentation, home } from '@/routes';

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
    keywords?: string;
    proponents?: string[] | Record<string, unknown>[] | string | null;
    category?: Category;
    school_class?: SchoolClass;
    authors?: Author[];
    publication?: Publication[];
    citations?: Citation[];
    tracking_records?: TrackingRecord[];
    custom_step_statuses?: Record<string, string | null> | null;
    step_ric_review?: string | null;
    step_outline_defense?: string | null;
    step_data_gathering?: string | null;
    step_rating?: string | null;
    step_final_manuscript?: string | null;
    step_final_defense?: string | null;
    step_hard_bound?: string | null;
}

interface Props {
    paper: Paper;
    steps: string[];
    stepLabels: Record<string, string>;
    stepConfigs?: Record<string, WorkflowStepSetup>;
}

const props = defineProps<Props>();
const branding = useBranding();

const abstractExpanded = ref(false);
const copied = ref(false);

const proponents = computed(() => {
    if (!props.paper.proponents) {
        return [];
    }

    if (Array.isArray(props.paper.proponents)) {
        return props.paper.proponents
            .map((value) => {
                if (typeof value === 'string') {
                    return value.trim();
                }

                if (
                    value &&
                    typeof value === 'object' &&
                    'name' in value &&
                    typeof value.name === 'string'
                ) {
                    return value.name.trim();
                }

                return '';
            })
            .filter(Boolean);
    }

    return props.paper.proponents
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean);
});

const keywords = computed(() =>
    (props.paper.keywords ?? '')
        .split(',')
        .map((keyword) => keyword.trim())
        .filter(Boolean),
);

const publications = computed(() => props.paper.publication ?? []);
const citations = computed(() => props.paper.citations ?? []);
const hasOutputs =
    publications.value.length > 0 || citations.value.length > 0;

const focusStepKey = computed(() =>
    workflowFocusStepKey(props.paper, props.steps, props.stepConfigs),
);

const progressPercent = computed(() =>
    workflowProgressPercent(props.paper, props.steps, props.stepConfigs),
);

const currentStepIndex = computed(() =>
    Math.max(props.steps.indexOf(focusStepKey.value), 0),
);

const currentStepLabel = computed(
    () => props.stepLabels[focusStepKey.value] ?? focusStepKey.value,
);

const nextStepKey = computed(
    () => props.steps[currentStepIndex.value + 1] ?? null,
);

const nextStepLabel = computed(() =>
    nextStepKey.value
        ? (props.stepLabels[nextStepKey.value] ?? nextStepKey.value)
        : null,
);

const isComplete = computed(
    () => progressPercent.value >= 100 || focusStepKey.value === 'completed',
);

const abstractIsLong = computed(
    () => (props.paper.abstract ?? '').trim().length > 220,
);

const pageTitle = computed(
    () => `${props.paper.tracking_id} · Research Tracker`,
);

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

async function copyTrackingId(): Promise<void> {
    if (typeof navigator === 'undefined' || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(props.paper.tracking_id);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 2000);
}
</script>

<template>
    <Head :title="pageTitle" />

    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-5 sm:py-10">
        <section
            class="overflow-hidden rounded-2xl border border-black/8 bg-white shadow-sm"
        >
            <div
                class="h-1.5 bg-gradient-to-r from-um-maroon via-um-gold to-um-maroon"
            />
            <div class="p-5 sm:p-7">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-bold tracking-[0.16em] text-um-maroon uppercase"
                        >
                            Public research tracker
                        </p>
                        <h1
                            class="mt-2 font-display text-2xl leading-tight font-extrabold tracking-tight text-um-heading sm:text-3xl"
                        >
                            {{ paper.title }}
                        </h1>
                    </div>
                    <Link
                        :href="`${home.url()}#track-paper`"
                        class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-[3px] border border-black/10 px-3 text-sm font-semibold text-um-heading transition hover:border-um-maroon/30 hover:text-um-maroon active:opacity-90"
                    >
                        <Search class="h-4 w-4" />
                        Track another
                    </Link>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold"
                        :class="
                            isComplete
                                ? 'bg-emerald-50 text-emerald-800'
                                : 'bg-um-maroon/8 text-um-maroon'
                        "
                    >
                        {{
                            isComplete ? 'Completed' : currentStepLabel
                        }}
                    </span>
                    <span
                        v-if="paper.category"
                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                    >
                        <Tag class="h-3 w-3" />
                        {{ paper.category.name }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                    >
                        <Calendar class="h-3 w-3" />
                        Submitted {{ formatDate(paper.created_at) }}
                    </span>
                </div>

                <div class="mt-6 rounded-xl bg-um-wash px-4 py-4 sm:px-5">
                    <div class="flex items-end justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="text-[11px] font-bold tracking-wide text-um-body uppercase"
                            >
                                Progress
                            </p>
                            <p
                                class="mt-1 text-sm font-semibold text-um-heading"
                            >
                                Step {{ currentStepIndex + 1 }} of
                                {{ steps.length }}
                                <span class="text-um-body">·</span>
                                {{ currentStepLabel }}
                            </p>
                        </div>
                        <p
                            class="text-lg font-extrabold text-um-maroon tabular-nums"
                        >
                            {{ progressPercent }}%
                        </p>
                    </div>
                    <div
                        class="mt-3 h-2.5 overflow-hidden rounded-full bg-black/8"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-um-maroon to-um-gold transition-all duration-700 ease-out"
                            :style="{ width: `${progressPercent}%` }"
                        />
                    </div>
                    <p
                        v-if="!isComplete && nextStepLabel"
                        class="mt-3 text-sm text-um-body"
                    >
                        Next up:
                        <span class="font-semibold text-um-heading">{{
                            nextStepLabel
                        }}</span>
                    </p>
                    <p
                        v-else-if="isComplete"
                        class="mt-3 text-sm font-medium text-emerald-800"
                    >
                        This paper has finished the research workflow.
                    </p>
                </div>

                <div v-if="paper.abstract" class="mt-6">
                    <p
                        class="text-[11px] font-bold tracking-wide text-um-body uppercase"
                    >
                        Rationale
                    </p>
                    <p
                        class="mt-2 text-sm leading-relaxed text-slate-600 sm:text-base"
                        :class="
                            abstractIsLong && !abstractExpanded
                                ? 'line-clamp-4'
                                : ''
                        "
                    >
                        {{ paper.abstract }}
                    </p>
                    <button
                        v-if="abstractIsLong"
                        type="button"
                        class="mt-2 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-um-maroon transition hover:text-um-maroon-deep active:opacity-90"
                        @click="abstractExpanded = !abstractExpanded"
                    >
                        {{ abstractExpanded ? 'Show less' : 'Read more' }}
                        <ChevronDown
                            class="h-4 w-4 transition"
                            :class="abstractExpanded ? 'rotate-180' : ''"
                        />
                    </button>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section
                class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm sm:p-6 lg:col-span-2"
            >
                <h2
                    class="mb-5 flex items-center gap-2 font-display text-lg font-extrabold text-um-heading"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-um-maroon/8"
                    >
                        <Calendar class="h-4 w-4 text-um-maroon" />
                    </span>
                    Workflow timeline
                </h2>
                <TrackingTimeline
                    :current-step="focusStepKey"
                    :steps="steps"
                    :step-labels="stepLabels"
                    :tracking="paper.tracking_records || []"
                />
            </section>

            <aside class="space-y-4">
                <section
                    class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="flex items-center gap-2 text-[11px] font-bold tracking-wider text-um-body uppercase"
                    >
                        <Hash class="h-3.5 w-3.5" />
                        Tracking ID
                    </h2>
                    <div
                        class="mt-3 flex items-center gap-2 rounded-lg border border-um-maroon/20 bg-um-maroon/5 px-3 py-2.5"
                    >
                        <p
                            class="min-w-0 flex-1 font-mono text-sm font-bold break-all text-um-maroon"
                        >
                            {{ paper.tracking_id }}
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="min-h-11 min-w-11 shrink-0 border-um-maroon/20 text-um-maroon hover:bg-um-maroon/8"
                            :aria-label="
                                copied
                                    ? 'Tracking ID copied'
                                    : 'Copy tracking ID'
                            "
                            @click="copyTrackingId"
                        >
                            <Check v-if="copied" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                        </Button>
                    </div>
                    <p class="mt-2 text-xs text-um-body">
                        Share this ID or a QR code so others can check status
                        without signing in.
                    </p>
                </section>

                <section
                    v-if="proponents.length"
                    class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="flex items-center gap-2 text-[11px] font-bold tracking-wider text-um-body uppercase"
                    >
                        <Users class="h-3.5 w-3.5" />
                        Proponents
                    </h2>
                    <ul class="mt-3 flex flex-wrap gap-1.5">
                        <li
                            v-for="name in proponents"
                            :key="name"
                            class="rounded-full border border-black/8 bg-um-wash px-2.5 py-1 text-xs font-medium text-um-heading"
                        >
                            {{ name }}
                        </li>
                    </ul>
                </section>

                <section
                    v-if="paper.authors && paper.authors.length > 0"
                    class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="flex items-center gap-2 text-[11px] font-bold tracking-wider text-um-body uppercase"
                    >
                        <Users class="h-3.5 w-3.5" />
                        Authors
                    </h2>
                    <ol class="mt-3 divide-y divide-black/6">
                        <li
                            v-for="(author, index) in paper.authors"
                            :key="author.id"
                            class="flex items-center justify-between gap-2 py-2.5 first:pt-0 last:pb-0"
                        >
                            <p class="text-sm font-medium text-um-heading">
                                {{ author.name }}
                            </p>
                            <span
                                class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500"
                            >
                                #{{ author.pivot?.author_order || index + 1 }}
                            </span>
                        </li>
                    </ol>
                </section>

                <section
                    v-if="paper.school_class"
                    class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="flex items-center gap-2 text-[11px] font-bold tracking-wider text-um-body uppercase"
                    >
                        <GraduationCap class="h-3.5 w-3.5" />
                        Class
                    </h2>
                    <p class="mt-2 text-sm font-semibold text-um-heading">
                        {{ paper.school_class.name }}
                        <span
                            v-if="paper.school_class.section"
                            class="font-medium text-um-body"
                        >
                            · Section {{ paper.school_class.section }}
                        </span>
                    </p>
                </section>

                <section
                    v-if="keywords.length"
                    class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="flex items-center gap-2 text-[11px] font-bold tracking-wider text-um-body uppercase"
                    >
                        <Tag class="h-3.5 w-3.5" />
                        Keywords
                    </h2>
                    <ul class="mt-3 flex flex-wrap gap-1.5">
                        <li
                            v-for="keyword in keywords"
                            :key="keyword"
                            class="rounded-full bg-um-gold/15 px-2.5 py-1 text-xs font-medium text-um-heading"
                        >
                            {{ keyword }}
                        </li>
                    </ul>
                </section>

                <p class="px-1 text-xs leading-relaxed text-um-body">
                    Need a walkthrough of each stage?
                    <Link
                        :href="documentation.url()"
                        class="font-semibold text-um-maroon underline-offset-2 hover:underline"
                    >
                        Read the user guide
                    </Link>
                    for {{ branding.name }}.
                </p>
            </aside>
        </div>

        <div
            v-if="hasOutputs"
            class="grid grid-cols-1 gap-6 lg:grid-cols-2"
        >
            <section
                v-if="publications.length"
                class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm sm:p-6"
            >
                <h2
                    class="mb-4 flex items-center gap-2 font-display text-lg font-extrabold text-um-heading"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50"
                    >
                        <BookOpen class="h-4 w-4 text-emerald-600" />
                    </span>
                    Publications
                </h2>
                <div class="space-y-3">
                    <article
                        v-for="pub in publications"
                        :key="pub.id"
                        class="rounded-xl border border-black/6 bg-um-wash p-4"
                    >
                        <h3 class="text-sm font-semibold text-um-heading">
                            {{ pub.journal_name }}
                        </h3>
                        <div class="mt-2 space-y-1 text-xs text-slate-600">
                            <p
                                v-if="pub.doi"
                                class="flex items-center gap-1.5"
                            >
                                <ExternalLink class="h-3 w-3 shrink-0" />
                                <code class="font-mono">{{ pub.doi }}</code>
                            </p>
                            <p v-if="pub.publisher">
                                Publisher: {{ pub.publisher }}
                            </p>
                            <p v-if="pub.volume || pub.issue">
                                Vol. {{ pub.volume }}, Issue {{ pub.issue }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section
                v-if="citations.length"
                class="rounded-2xl border border-black/8 bg-white p-5 shadow-sm sm:p-6"
            >
                <h2
                    class="mb-4 flex items-center gap-2 font-display text-lg font-extrabold text-um-heading"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50"
                    >
                        <Quote class="h-4 w-4 text-amber-600" />
                    </span>
                    Citations
                </h2>
                <div class="space-y-3">
                    <blockquote
                        v-for="citation in citations"
                        :key="citation.id"
                        class="rounded-xl border border-black/6 bg-um-wash p-4"
                    >
                        <p class="text-sm leading-relaxed text-slate-700">
                            {{ citation.citation_text }}
                        </p>
                        <p
                            v-if="citation.format"
                            class="mt-2 text-[10px] font-semibold tracking-wide text-um-body uppercase"
                        >
                            {{ citation.format }}
                        </p>
                    </blockquote>
                </div>
            </section>
        </div>
    </main>
</template>
