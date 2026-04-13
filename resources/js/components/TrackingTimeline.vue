<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    currentStatus: string;
    tracking: Array<{
        id: number;
        status: string;
        created_at: string;
        notes?: string;
    }>;
}

const props = defineProps<Props>();

const stages = [
    'submitted',
    'under_review',
    'approved',
    'presented',
    'published',
    'archived',
] as const;

const stageLabels: Record<string, string> = {
    submitted: 'Submitted',
    under_review: 'Under Review',
    approved: 'Approved',
    presented: 'Presented',
    published: 'Published',
    archived: 'Archived',
};

const stageIcons: Record<string, string> = {
    submitted: '📥',
    under_review: '🔍',
    approved: '✅',
    presented: '🎤',
    published: '📚',
    archived: '🗄️',
};

const currentStageIndex = computed(() => stages.indexOf(props.currentStatus as typeof stages[number]));

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
            <!-- Labels row -->
            <div class="grid grid-cols-6 gap-1">
                <div
                    v-for="(stage, index) in stages"
                    :key="stage"
                    class="flex flex-col items-center gap-1.5"
                >
                    <!-- Circle dot -->
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold transition-colors"
                        :class="{
                            'border-orange-500 bg-orange-500 text-white': index === currentStageIndex,
                            'border-emerald-500 bg-emerald-500 text-white': index < currentStageIndex,
                            'border-border bg-background text-muted-foreground': index > currentStageIndex,
                        }"
                    >
                        <svg v-if="index < currentStageIndex" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <!-- Label -->
                    <span
                        class="text-center text-[10px] font-medium leading-tight"
                        :class="{
                            'text-orange-600 dark:text-orange-400': index === currentStageIndex,
                            'text-emerald-600 dark:text-emerald-400': index < currentStageIndex,
                            'text-muted-foreground': index > currentStageIndex,
                        }"
                    >
                        {{ stageLabels[stage] }}
                    </span>
                </div>
            </div>

            <!-- Progress track -->
            <div class="relative mx-4 h-1.5 rounded-full bg-border">
                <div
                    class="h-1.5 rounded-full bg-orange-500 transition-all duration-500"
                    :style="{ width: `${Math.max(0, (currentStageIndex / (stages.length - 1)) * 100)}%` }"
                />
            </div>
        </div>

        <!-- History log -->
        <div v-if="tracking.length > 0" class="space-y-2">
            <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">History</p>
            <div>
                <div
                    v-for="(record, idx) in tracking"
                    :key="record.id"
                    class="flex gap-3"
                >
                    <!-- Left: dot + connecting line -->
                    <div class="flex flex-col items-center">
                        <div class="mt-3 h-3 w-3 shrink-0 rounded-full border-2 border-orange-500 bg-background" />
                        <div
                            v-if="idx < tracking.length - 1"
                            class="w-0.5 flex-1 bg-border"
                        />
                    </div>
                    <!-- Right: content card -->
                    <div class="flex-1 pb-4 last:pb-0">
                        <div class="rounded-lg border border-border bg-muted/40 px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-foreground">
                                    {{ stageLabels[record.status] ?? record.status }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ formatDate(record.created_at) }}</span>
                            </div>
                            <p v-if="record.notes" class="mt-1 text-xs italic text-muted-foreground">
                                {{ record.notes }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="rounded-lg border border-dashed border-border py-6 text-center text-sm text-muted-foreground">
            No tracking history yet.
        </div>
    </div>
</template>
