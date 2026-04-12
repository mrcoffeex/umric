<template>
    <div
        class="status-badge"
        :class="{
            'status-pulse': props.status === 'under_review',
            'status-badge-dark': isDark,
        }"
        :style="{ color: badgeColor }"
    >
        <span class="flex items-center gap-2">
            <span class="dot" :style="{ backgroundColor: badgeColor }"></span>
            {{ label }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';

interface Props {
    status: string;
}

const props = defineProps<Props>();

const isDark = ref(false);

let observer: MutationObserver | null = null;

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});

onUnmounted(() => {
    observer?.disconnect();
});

const statusConfig: Record<string, { label: string; color: string }> = {
    submitted: { label: 'Submitted', color: '#f97316' },
    under_review: { label: 'Under Review', color: '#f59e0b' },
    approved: { label: 'Approved', color: '#14b8a6' },
    presented: { label: 'Presented', color: '#0891b2' },
    published: { label: 'Published', color: '#059669' },
    archived: { label: 'Archived', color: '#6b7280' },
};

const config = computed(
    () =>
        statusConfig[props.status] ?? { label: props.status, color: '#6b7280' },
);
const badgeColor = computed(() => config.value.color);
const label = computed(() => config.value.label);
</script>

<style scoped>
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    box-shadow:
        3px 3px 8px rgba(0, 0, 0, 0.1),
        -3px -3px 8px rgba(255, 255, 255, 0.7);
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-pulse .dot {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

:is(.dark *) .status-badge,
:is(.dark) .status-badge {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    box-shadow:
        3px 3px 8px rgba(0, 0, 0, 0.3),
        -3px -3px 8px rgba(255, 255, 255, 0.1);
}

.status-badge-dark {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    box-shadow:
        3px 3px 8px rgba(0, 0, 0, 0.3),
        -3px -3px 8px rgba(255, 255, 255, 0.1);
}
</style>
