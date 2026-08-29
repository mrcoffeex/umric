<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ChevronDown,
    ChevronUp,
    ScrollText,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    firstPendingWorkflowStepIndex,
    workflowFocusStepKey,
    workflowProgressPercent,
} from '@/lib/research-workflow-ui';
import { getStepBadgeClass } from '@/lib/step-colors';
import student from '@/routes/student';

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
}

const props = defineProps<{
    paper: Paper;
    stepLabels: Record<string, string>;
    steps: string[];
}>();

const showAllSteps = ref(false);

const focusIndex = computed(() =>
    firstPendingWorkflowStepIndex(props.paper, props.steps),
);
const focusStepKey = computed(() =>
    workflowFocusStepKey(props.paper, props.steps),
);
const progressPercent = computed(() =>
    workflowProgressPercent(props.paper, props.steps),
);
const stepCount = computed(() => Math.max(props.steps.length, 1));
const currentStepNumber = computed(() =>
    focusIndex.value >= 0 ? focusIndex.value + 1 : 1,
);

const visibleSteps = computed(() => {
    if (showAllSteps.value || focusIndex.value < 0) {
        return props.steps.map((step, index) => ({ step, index }));
    }

    const start = Math.max(0, focusIndex.value - 1);
    const end = Math.min(props.steps.length - 1, focusIndex.value + 1);

    return props.steps
        .slice(start, end + 1)
        .map((step, offset) => ({ step, index: start + offset }));
});

function stepLabel(step: string): string {
    return props.stepLabels[step] ?? step;
}

function isCompleted(index: number): boolean {
    return focusIndex.value >= 0 && index < focusIndex.value;
}

function isCurrent(index: number): boolean {
    return focusIndex.value >= 0 && index === focusIndex.value;
}

function formatRelative(value?: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const diffMs = Date.now() - date.getTime();
    const minutes = Math.floor(diffMs / 60000);

    if (minutes < 1) {
        return 'Just now';
    }

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 7) {
        return `${days}d ago`;
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-border bg-card">
        <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4"
        >
            <div class="flex items-center gap-2.5">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/40"
                >
                    <ScrollText class="h-4 w-4 text-orange-500" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-foreground">
                        Research Progress
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Step {{ currentStepNumber }} of {{ stepCount }}
                        <span v-if="paper.last_update?.at">
                            · Updated {{ formatRelative(paper.last_update.at) }}
                        </span>
                    </p>
                </div>
            </div>
            <Link
                :href="student.research.index.url()"
                class="inline-flex min-h-9 items-center gap-1 rounded-lg px-2 text-xs font-semibold text-orange-600 transition hover:bg-orange-50 hover:text-orange-700 dark:text-orange-400 dark:hover:bg-orange-950/40"
            >
                View all
                <ArrowRight class="h-3.5 w-3.5" />
            </Link>
        </div>

        <div class="space-y-5 p-5">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
            >
                <div class="min-w-0 flex-1 space-y-3">
                    <div>
                        <Link
                            :href="student.research.show.url(paper.id)"
                            class="line-clamp-2 text-lg font-bold text-foreground transition hover:text-orange-600 dark:hover:text-orange-400"
                        >
                            {{ paper.title }}
                        </Link>
                        <div
                            class="mt-2 flex flex-wrap items-center gap-2 text-xs"
                        >
                            <code
                                class="rounded-md bg-orange-50 px-2 py-1 font-mono text-[11px] font-semibold text-orange-700 dark:bg-orange-950/40 dark:text-orange-300"
                            >
                                {{ paper.tracking_id }}
                            </code>
                            <span
                                v-if="paper.is_returned"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300"
                            >
                                Action required
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-muted-foreground">
                            Current stage
                        </span>
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                getStepBadgeClass(focusStepKey),
                            ]"
                        >
                            <span
                                class="h-1.5 w-1.5 animate-pulse rounded-full bg-current"
                            />
                            {{ stepLabel(focusStepKey) }}
                        </span>
                    </div>

                    <p
                        v-if="paper.last_update"
                        class="text-xs text-muted-foreground"
                    >
                        Last update:
                        {{ stepLabel(paper.last_update.step) }}
                        ·
                        {{ paper.last_update.status }}
                        <span v-if="paper.last_update.notes">
                            — {{ paper.last_update.notes }}
                        </span>
                    </p>
                </div>

                <div
                    class="flex shrink-0 items-center gap-3 self-stretch sm:self-auto"
                >
                    <div
                        class="flex min-w-[5.5rem] flex-col items-center justify-center rounded-2xl border border-orange-200/80 bg-gradient-to-br from-orange-50 to-amber-50 px-4 py-3 dark:border-orange-900/50 dark:from-orange-950/40 dark:to-amber-950/20"
                    >
                        <span
                            class="text-2xl font-bold tracking-tight text-orange-600 dark:text-orange-400"
                        >
                            {{ progressPercent }}%
                        </span>
                        <span
                            class="text-[10px] font-semibold tracking-wide text-orange-700/70 uppercase dark:text-orange-300/70"
                        >
                            Complete
                        </span>
                    </div>
                    <Link
                        :href="student.research.show.url(paper.id)"
                        class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600 active:scale-[0.98] sm:flex-none"
                    >
                        Open paper
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>

            <div>
                <div
                    class="h-2.5 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-valuenow="progressPercent"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-label="`Research progress ${progressPercent} percent`"
                >
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-orange-500 via-amber-500 to-teal-500 transition-all duration-500"
                        :style="{ width: `${progressPercent}%` }"
                    />
                </div>
            </div>

            <div>
                <ol
                    class="flex items-start justify-between gap-2"
                    aria-label="Research workflow stages"
                >
                    <li
                        v-for="{ step, index } in visibleSteps"
                        :key="step"
                        class="relative flex flex-1 flex-col items-center px-1"
                        :aria-current="isCurrent(index) ? 'step' : undefined"
                    >
                        <div
                            :class="[
                                'relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 transition',
                                isCompleted(index)
                                    ? 'border-teal-500 bg-teal-500 text-white'
                                    : isCurrent(index)
                                      ? 'border-orange-500 bg-orange-500 text-white shadow-[0_0_0_4px] shadow-orange-500/20'
                                      : 'border-border bg-card text-muted-foreground',
                            ]"
                        >
                            <Check
                                v-if="isCompleted(index)"
                                class="h-3.5 w-3.5"
                                stroke-width="3"
                            />
                            <span v-else class="text-[11px] font-bold">
                                {{ index + 1 }}
                            </span>
                        </div>

                        <p
                            :class="[
                                'mt-2 max-w-[6.5rem] text-center text-[10px] leading-tight font-semibold sm:text-[11px]',
                                isCurrent(index)
                                    ? 'text-orange-700 dark:text-orange-300'
                                    : isCompleted(index)
                                      ? 'text-teal-700 dark:text-teal-300'
                                      : 'text-muted-foreground',
                            ]"
                        >
                            {{ stepLabel(step) }}
                        </p>
                        <span
                            v-if="isCurrent(index)"
                            class="mt-1 rounded-full bg-orange-100 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-orange-700 uppercase dark:bg-orange-950/50 dark:text-orange-300"
                        >
                            Now
                        </span>
                        <span
                            v-else-if="isCompleted(index)"
                            class="mt-1 text-[9px] font-medium text-teal-600/80 dark:text-teal-400/80"
                        >
                            Done
                        </span>
                    </li>
                </ol>

                <button
                    type="button"
                    class="mt-3 inline-flex min-h-9 w-full items-center justify-center gap-1 rounded-lg text-xs font-semibold text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    @click="showAllSteps = !showAllSteps"
                >
                    <component
                        :is="showAllSteps ? ChevronUp : ChevronDown"
                        class="h-3.5 w-3.5"
                    />
                    {{ showAllSteps ? 'Show less' : 'Show all stages' }}
                </button>
            </div>
        </div>
    </section>
</template>
