<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed } from 'vue';

export type StepStatusType = 'success' | 'warning' | 'danger' | 'neutral';

export interface ResearchStepTile {
    key: string;
    icon: Component;
    status: string;
    statusType: StepStatusType;
    info?: string | null;
    completed: boolean;
}

const props = withDefaults(
    defineProps<{
        steps: ResearchStepTile[];
        stepLabels: Record<string, string>;
        /** Index of the step that currently needs attention */
        focusIndex: number;
        /** Step key with an open manage panel (admin) */
        activeKey?: string | null;
        title?: string;
        description?: string;
    }>(),
    {
        activeKey: null,
        title: 'Step management',
        description: undefined,
    },
);

type StepState = 'completed' | 'current' | 'upcoming';

const enriched = computed(() =>
    props.steps.map((step, index) => {
        let state: StepState = 'upcoming';

        if (step.completed) {
            state = 'completed';
        } else if (index === props.focusIndex) {
            state = 'current';
        } else if (index < props.focusIndex) {
            state = 'completed';
        }

        return {
            ...step,
            index,
            state,
            title: props.stepLabels[step.key] ?? step.key,
            isActive: props.activeKey === step.key,
            isUpcoming: index > props.focusIndex && !step.completed,
        };
    }),
);

const statusTypeClasses: Record<StepStatusType, string> = {
    success:
        'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
    warning:
        'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
    danger: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
    neutral: 'bg-muted text-muted-foreground',
};

function tileSurfaceClass(state: StepState, isActive: boolean): string {
    if (isActive) {
        return 'border-primary ring-2 ring-primary/25';
    }

    if (state === 'current') {
        return 'border-primary/40 bg-primary/10 dark:bg-primary/15';
    }

    if (state === 'completed') {
        return 'border-green-300/80 bg-green-50/50 dark:border-green-800/70 dark:bg-green-950/25';
    }

    // pending / upcoming
    return 'border-amber-300/80 bg-amber-50/50 dark:border-amber-800/70 dark:bg-amber-950/25';
}

function iconWrapClass(state: StepState): string {
    if (state === 'completed') {
        return 'bg-green-500 text-white shadow-sm shadow-green-500/20';
    }

    if (state === 'current') {
        return 'bg-primary text-primary-foreground shadow-sm shadow-primary/25';
    }

    return 'bg-amber-500 text-white shadow-sm shadow-amber-500/20';
}

function railClass(state: StepState): string {
    if (state === 'completed') {
        return 'bg-green-500';
    }

    if (state === 'current') {
        return 'bg-primary';
    }

    return 'bg-amber-500';
}

function stateBadgeClass(state: StepState): string {
    if (state === 'completed') {
        return statusTypeClasses.success;
    }

    if (state === 'current') {
        return 'bg-primary/15 text-primary';
    }

    return statusTypeClasses.warning;
}
</script>

<template>
    <section
        class="scroll-mt-24 rounded-2xl border border-border bg-card p-4 sm:p-5"
    >
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <slot name="header-extra" />
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-foreground">
                        {{ title }}
                    </h2>
                    <p
                        v-if="description"
                        class="mt-0.5 text-xs text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
            </div>
        </div>

        <ol
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
            aria-label="Research workflow steps"
        >
            <li
                v-for="step in enriched"
                :key="step.key"
                :class="[
                    'relative flex min-h-[9.5rem] flex-col overflow-hidden rounded-2xl border transition-all duration-200',
                    tileSurfaceClass(step.state, step.isActive),
                    step.isActive ? 'sm:col-span-2 xl:col-span-3' : '',
                ]"
            >
                <div
                    :class="[
                        'absolute inset-y-0 left-0 w-1',
                        railClass(step.state),
                    ]"
                    aria-hidden="true"
                />

                <div
                    v-if="step.state === 'current'"
                    class="absolute top-0 right-0 rounded-bl-lg bg-primary px-2 py-0.5 text-[10px] font-bold tracking-wide text-primary-foreground uppercase"
                >
                    Current
                </div>

                <div class="flex flex-1 flex-col gap-3 p-4 pl-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 items-start gap-3">
                            <span
                                :class="[
                                    'flex size-11 shrink-0 items-center justify-center rounded-xl',
                                    iconWrapClass(step.state),
                                ]"
                            >
                                <Check
                                    v-if="step.state === 'completed'"
                                    class="h-5 w-5"
                                />
                                <component
                                    :is="step.icon"
                                    v-else
                                    class="h-5 w-5"
                                />
                            </span>

                            <div class="min-w-0 space-y-1">
                                <p
                                    class="text-[10px] font-bold tracking-[0.14em] text-muted-foreground uppercase"
                                >
                                    Step
                                    {{
                                        String(step.index + 1).padStart(2, '0')
                                    }}
                                </p>
                                <h3
                                    class="text-sm font-bold tracking-tight text-foreground sm:text-[15px]"
                                >
                                    {{ step.title }}
                                </h3>
                            </div>
                        </div>

                        <div class="shrink-0 pt-0.5">
                            <slot
                                name="actions"
                                :step="step"
                                :index="step.index"
                                :state="step.state"
                            />
                        </div>
                    </div>

                    <div class="mt-auto space-y-2">
                        <span
                            v-if="!step.isUpcoming"
                            :class="[
                                'inline-flex max-w-full items-center truncate rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                step.state === 'completed' ||
                                step.state === 'current'
                                    ? stateBadgeClass(step.state)
                                    : statusTypeClasses[step.statusType],
                            ]"
                        >
                            {{ step.status }}
                        </span>
                        <span
                            v-else
                            :class="[
                                'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold',
                                stateBadgeClass('upcoming'),
                            ]"
                        >
                            Pending
                        </span>

                        <p
                            v-if="step.isUpcoming"
                            class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                        >
                            Waiting for previous steps to complete.
                        </p>
                        <p
                            v-else-if="step.info"
                            class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ step.info }}
                        </p>
                        <p
                            v-else-if="step.state === 'completed'"
                            class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                        >
                            This step is finished.
                        </p>
                        <p
                            v-else-if="step.state === 'current'"
                            class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                        >
                            This is the active stage in the pipeline.
                        </p>
                    </div>
                </div>

                <div
                    v-if="$slots.panel && step.isActive"
                    class="border-t border-border bg-muted/30 p-4"
                >
                    <slot
                        name="panel"
                        :step="step"
                        :index="step.index"
                        :state="step.state"
                    />
                </div>
            </li>
        </ol>
    </section>
</template>
