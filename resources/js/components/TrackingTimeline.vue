<script setup lang="ts">
import {
    BookCheck,
    Check,
    CheckCircle2,
    FileBarChart2,
    FileSearch,
    GraduationCap,
    ScrollText,
    Send,
    Shield,
    Trophy,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    currentStep: string;
    steps: string[];
    stepLabels: Record<string, string>;
    tracking: Array<{
        id: number;
        step?: string;
        action?: string;
        status?: string;
        created_at: string;
        notes?: string;
    }>;
}

const props = defineProps<Props>();

const stepIcons: Record<string, any> = {
    title_proposal: Send,
    ric_review: Shield,
    plagiarism_check: FileSearch,
    outline_defense: BookCheck,
    rating: FileBarChart2,
    final_manuscript: ScrollText,
    final_defense: GraduationCap,
    hard_bound: Trophy,
    completed: CheckCircle2,
};

const currentStepIndex = computed(() => props.steps.indexOf(props.currentStep));

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Step progress bar -->
        <div class="space-y-3">
            <!-- Scrollable steps row -->
            <div class="overflow-x-auto pb-2">
                <div class="flex min-w-max gap-0">
                    <div
                        v-for="(step, index) in steps"
                        :key="step"
                        class="flex items-center"
                    >
                        <div
                            class="flex flex-col items-center gap-1.5"
                            style="width: 80px"
                        >
                            <!-- Circle with icon -->
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full border-2 transition-colors"
                                :class="{
                                    'border-orange-500 bg-orange-500 text-white':
                                        index === currentStepIndex,
                                    'border-emerald-500 bg-emerald-500 text-white':
                                        index < currentStepIndex,
                                    'border-gray-300 bg-white text-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-500':
                                        index > currentStepIndex,
                                }"
                            >
                                <Check
                                    v-if="index < currentStepIndex"
                                    class="h-4 w-4"
                                />
                                <component
                                    v-else
                                    :is="stepIcons[step]"
                                    class="h-3.5 w-3.5"
                                />
                            </div>
                            <!-- Label -->
                            <span
                                class="max-w-[72px] text-center text-[10px] leading-tight font-medium"
                                :class="{
                                    'text-orange-600 dark:text-orange-400':
                                        index === currentStepIndex,
                                    'text-emerald-600 dark:text-emerald-400':
                                        index < currentStepIndex,
                                    'text-gray-400 dark:text-gray-500':
                                        index > currentStepIndex,
                                }"
                            >
                                {{ stepLabels[step] ?? step }}
                            </span>
                        </div>
                        <!-- Connector line between steps -->
                        <div
                            v-if="index < steps.length - 1"
                            class="mx-0.5 mb-5 h-0.5 w-6 rounded-full transition-colors"
                            :class="{
                                'bg-emerald-500': index < currentStepIndex,
                                'bg-gray-200 dark:bg-gray-700':
                                    index >= currentStepIndex,
                            }"
                        />
                    </div>
                </div>
            </div>

            <!-- Progress track -->
            <div
                class="relative h-1.5 rounded-full bg-gray-200 dark:bg-gray-700"
            >
                <div
                    class="h-1.5 rounded-full bg-gradient-to-r from-orange-500 to-amber-400 transition-all duration-500"
                    :style="{
                        width: `${steps.length <= 1 ? 0 : Math.max(0, (currentStepIndex / (steps.length - 1)) * 100)}%`,
                    }"
                />
            </div>
        </div>

        <!-- History log -->
        <div v-if="tracking.length > 0" class="space-y-2">
            <p
                class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
            >
                History
            </p>
            <div>
                <div
                    v-for="record in tracking"
                    :key="record.id"
                    class="flex gap-3"
                >
                    <!-- Left: dot + connecting line -->
                    <div class="flex flex-col items-center">
                        <div
                            class="mt-3 h-3 w-3 shrink-0 rounded-full border-2 border-orange-500 bg-white dark:bg-gray-900"
                        />
                        <div
                            class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700"
                        />
                    </div>
                    <!-- Right: content card -->
                    <div class="flex-1 pb-4">
                        <div
                            class="rounded-lg border border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/30"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        {{
                                            record.action ??
                                            stepLabels[record.step ?? ''] ??
                                            record.status ??
                                            record.step
                                        }}
                                    </span>
                                    <span
                                        v-if="record.step && record.action"
                                        class="rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-400"
                                    >
                                        {{
                                            stepLabels[record.step] ??
                                            record.step
                                        }}
                                    </span>
                                </div>
                                <span
                                    class="shrink-0 text-xs text-gray-400 dark:text-gray-500"
                                    >{{ formatDate(record.created_at) }}</span
                                >
                            </div>
                            <p
                                v-if="record.status && record.action"
                                class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                            >
                                Status: {{ record.status }}
                            </p>
                            <p
                                v-if="record.notes"
                                class="mt-1 text-xs text-gray-500 italic dark:text-gray-400"
                            >
                                {{ record.notes }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-gray-200 py-6 text-center text-sm text-gray-400 dark:border-gray-700 dark:text-gray-500"
        >
            No tracking history yet.
        </div>
    </div>
</template>
