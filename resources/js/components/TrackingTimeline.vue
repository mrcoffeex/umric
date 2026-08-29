<script setup lang="ts">
import {
    BookCheck,
    Check,
    CheckCircle2,
    Database,
    FileBarChart2,
    GraduationCap,
    ScrollText,
    Send,
    Shield,
    Trophy,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed } from 'vue';

interface Props {
    currentStep: string;
    steps: string[];
    stepLabels: Record<string, string>;
    tracking: Array<{
        id: string;
        step?: string;
        action?: string;
        status?: string;
        created_at: string;
        notes?: string;
    }>;
}

const props = defineProps<Props>();

const stepIcons: Record<string, Component> = {
    title_proposal: Send,
    ric_review: Shield,
    outline_defense: BookCheck,
    data_gathering: Database,
    rating: FileBarChart2,
    final_manuscript: ScrollText,
    final_defense: GraduationCap,
    hard_bound: Trophy,
    completed: CheckCircle2,
};

const currentStepIndex = computed(() => props.steps.indexOf(props.currentStep));

const progressWidth = computed(() => {
    if (props.steps.length <= 1) {
        return 0;
    }

    return Math.max(
        0,
        (currentStepIndex.value / (props.steps.length - 1)) * 100,
    );
});

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function recordTitle(record: Props['tracking'][number]): string {
    return (
        record.action ??
        props.stepLabels[record.step ?? ''] ??
        record.status ??
        record.step ??
        'Update'
    );
}
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-3">
            <div
                class="overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            >
                <ol class="flex min-w-max items-start">
                    <li
                        v-for="(step, index) in steps"
                        :key="step"
                        class="flex items-start"
                    >
                        <div
                            class="flex w-20 flex-col items-center gap-2 sm:w-24"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-colors"
                                :class="{
                                    'border-orange-500 bg-orange-500 text-white shadow-[0_0_0_4px_rgba(249,115,22,0.15)]':
                                        index === currentStepIndex,
                                    'border-emerald-500 bg-emerald-500 text-white':
                                        index < currentStepIndex,
                                    'border-border bg-background text-muted-foreground':
                                        index > currentStepIndex,
                                }"
                            >
                                <Check
                                    v-if="index < currentStepIndex"
                                    class="h-4 w-4"
                                />
                                <component
                                    v-else
                                    :is="stepIcons[step] ?? CheckCircle2"
                                    class="h-4 w-4"
                                />
                            </div>
                            <span
                                class="max-w-[4.75rem] text-center text-[11px] leading-tight font-semibold sm:max-w-[5.5rem]"
                                :class="{
                                    'text-orange-600 dark:text-orange-400':
                                        index === currentStepIndex,
                                    'text-emerald-700 dark:text-emerald-400':
                                        index < currentStepIndex,
                                    'text-muted-foreground':
                                        index > currentStepIndex,
                                }"
                            >
                                {{ stepLabels[step] ?? step }}
                            </span>
                        </div>
                        <div
                            v-if="index < steps.length - 1"
                            class="mt-5 h-0.5 w-5 rounded-full sm:w-7"
                            :class="
                                index < currentStepIndex
                                    ? 'bg-emerald-500'
                                    : 'bg-border'
                            "
                        />
                    </li>
                </ol>
            </div>

            <div class="relative h-1.5 rounded-full bg-muted">
                <div
                    class="h-1.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-400 transition-all duration-500"
                    :style="{ width: `${progressWidth}%` }"
                />
            </div>
        </div>

        <div v-if="tracking.length > 0" class="space-y-2">
            <p
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                History
            </p>
            <ol>
                <li
                    v-for="record in tracking"
                    :key="record.id"
                    class="flex gap-3"
                >
                    <div class="flex flex-col items-center">
                        <div
                            class="mt-3 h-3 w-3 shrink-0 rounded-full border-2 border-orange-500 bg-background"
                        />
                        <div class="w-0.5 flex-1 bg-border" />
                    </div>
                    <div class="min-w-0 flex-1 pb-4">
                        <article
                            class="rounded-xl border border-border bg-muted/40 px-4 py-3"
                        >
                            <div
                                class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        {{ recordTitle(record) }}
                                    </p>
                                    <p
                                        v-if="record.step && record.action"
                                        class="mt-1 inline-flex rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-400"
                                    >
                                        {{
                                            stepLabels[record.step] ??
                                            record.step
                                        }}
                                    </p>
                                </div>
                                <time
                                    class="shrink-0 text-xs text-muted-foreground"
                                    :datetime="record.created_at"
                                >
                                    {{ formatDate(record.created_at) }}
                                </time>
                            </div>
                            <p
                                v-if="record.status && record.action"
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                Status:
                                <span class="font-medium text-foreground">{{
                                    record.status
                                }}</span>
                            </p>
                            <p
                                v-if="record.notes"
                                class="mt-1 text-xs text-muted-foreground italic"
                            >
                                {{ record.notes }}
                            </p>
                        </article>
                    </div>
                </li>
            </ol>
        </div>

        <div
            v-else
            class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            No tracking history yet. Updates will appear here as the paper moves
            through each step.
        </div>
    </div>
</template>
