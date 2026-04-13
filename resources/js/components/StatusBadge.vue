<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    status: string;
}

const props = defineProps<Props>();

interface StatusConfig {
    label: string;
    classes: string;
    dotClasses: string;
    pulse: boolean;
}

const statusConfig: Record<string, StatusConfig> = {
    submitted: {
        label: 'Submitted',
        classes:
            'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
        dotClasses: 'bg-orange-500 dark:bg-orange-400',
        pulse: false,
    },
    under_review: {
        label: 'Under Review',
        classes:
            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
        dotClasses: 'bg-amber-500 dark:bg-amber-400',
        pulse: true,
    },
    approved: {
        label: 'Approved',
        classes:
            'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400',
        dotClasses: 'bg-teal-500 dark:bg-teal-400',
        pulse: false,
    },
    presented: {
        label: 'Presented',
        classes:
            'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-400',
        dotClasses: 'bg-cyan-500 dark:bg-cyan-400',
        pulse: false,
    },
    published: {
        label: 'Published',
        classes:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
        dotClasses: 'bg-emerald-500 dark:bg-emerald-400',
        pulse: false,
    },
    archived: {
        label: 'Archived',
        classes:
            'bg-slate-100 text-slate-500 dark:bg-slate-500/10 dark:text-slate-400',
        dotClasses: 'bg-slate-400 dark:bg-slate-500',
        pulse: false,
    },
};

const config = computed(
    (): StatusConfig =>
        statusConfig[props.status] ?? {
            label: props.status,
            classes:
                'bg-slate-100 text-slate-500 dark:bg-slate-500/10 dark:text-slate-400',
            dotClasses: 'bg-slate-400',
            pulse: false,
        },
);
</script>

<template>
    <span
        class="inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
        :class="config.classes"
    >
        <span
            class="size-1.5 rounded-full"
            :class="[config.dotClasses, { 'animate-pulse': config.pulse }]"
        />
        {{ config.label }}
    </span>
</template>
