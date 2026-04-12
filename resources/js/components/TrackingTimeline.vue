<template>
    <div class="tracking-timeline">
        <div class="timeline-track">
            <div
                class="timeline-progress"
                :style="{ width: progressPercent + '%' }"
            ></div>
            <div
                v-for="(stage, index) in stages"
                :key="stage"
                class="timeline-dot"
                :class="{
                    completed: index < currentStageIndex,
                    current: index === currentStageIndex,
                }"
            >
                <span class="stage-label">{{ stageLabels[stage] }}</span>
            </div>
        </div>
        <div class="timeline-history">
            <div
                v-for="record in tracking"
                :key="record.id"
                class="history-item"
            >
                <div class="history-dot"></div>
                <div class="history-content">
                    <div class="history-title">
                        {{ stageLabels[record.status] }}
                    </div>
                    <div class="history-date">
                        {{ formatDate(record.created_at) }}
                    </div>
                    <div v-if="record.notes" class="history-notes">
                        {{ record.notes }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

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
];

const stageLabels: Record<string, string> = {
    submitted: 'Submitted',
    under_review: 'Under Review',
    approved: 'Approved',
    presented: 'Presented',
    published: 'Published',
    archived: 'Archived',
};

const currentStageIndex = computed(() => stages.indexOf(props.currentStatus));

const progressPercent = computed(
    () => ((currentStageIndex.value + 1) / stages.length) * 100,
);

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

<style scoped>
.tracking-timeline {
    space-y: 2rem;
}

.timeline-track {
    position: relative;
    display: flex;
    justify-content: space-between;
    margin-bottom: 3rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 20px;
    box-shadow:
        5px 5px 12px rgba(0, 0, 0, 0.1),
        -5px -5px 12px rgba(255, 255, 255, 0.7);
}

.timeline-progress {
    position: absolute;
    top: 1rem;
    left: 1rem;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
    transition: width 0.5s ease;
    width: 0;
}

.timeline-dot {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.timeline-dot::before {
    content: '';
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: white;
    border: 3px solid #e5e7eb;
    box-shadow:
        0 2px 8px rgba(0, 0, 0, 0.1),
        -0 -2px 8px rgba(255, 255, 255, 0.7);
    transition: all 0.3s ease;
}

.timeline-dot.completed::before {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    box-shadow:
        0 4px 12px rgba(102, 126, 234, 0.3),
        -0 -4px 12px rgba(255, 255, 255, 0.7);
}

.timeline-dot.current::before {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    border-color: #f59e0b;
    box-shadow:
        0 4px 12px rgba(245, 158, 11, 0.3),
        -0 -4px 12px rgba(255, 255, 255, 0.7);
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.stage-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #4b5563;
    text-align: center;
    max-width: 80px;
}

.timeline-history {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.history-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 16px;
    box-shadow:
        3px 3px 8px rgba(0, 0, 0, 0.1),
        -3px -3px 8px rgba(255, 255, 255, 0.7);
}

.history-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.history-content {
    flex: 1;
}

.history-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.history-date {
    font-size: 0.875rem;
    color: #6b7280;
}

.history-notes {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #4b5563;
    font-style: italic;
}

@media (prefers-color-scheme: dark) {
    .timeline-track {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        box-shadow:
            5px 5px 12px rgba(0, 0, 0, 0.3),
            -5px -5px 12px rgba(255, 255, 255, 0.1);
    }

    .stage-label {
        color: #cbd5e0;
    }

    .history-item {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        box-shadow:
            3px 3px 8px rgba(0, 0, 0, 0.3),
            -3px -3px 8px rgba(255, 255, 255, 0.1);
    }

    .history-title {
        color: #e2e8f0;
    }

    .history-date {
        color: #a0aec0;
    }

    .history-notes {
        color: #cbd5e0;
    }
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}
</style>
