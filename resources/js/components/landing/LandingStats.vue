<script setup lang="ts">
import { computed } from 'vue';
import { useCountUp } from '@/composables/useCountUp';
import { useScrollReveal } from '@/composables/useScrollReveal';

const props = defineProps<{
    stats?: {
        papers: number;
        students: number;
        departments: number;
    };
}>();

const { target, isVisible } = useScrollReveal(0.25);

const paperCount = useCountUp(
    computed(() => props.stats?.papers ?? 0),
    isVisible,
    1800,
);
const studentCount = useCountUp(
    computed(() => props.stats?.students ?? 0),
    isVisible,
    1800,
);
const deptCount = useCountUp(
    computed(() => props.stats?.departments ?? 0),
    isVisible,
    1200,
);

const items = [
    { key: 'papers', label: 'Papers tracked', suffix: '+' },
    { key: 'students', label: 'Student researchers', suffix: '+' },
    { key: 'depts', label: 'Departments', suffix: '+' },
    { key: 'stages', label: 'Research stages', value: '9' },
] as const;
</script>

<template>
    <section
        ref="target"
        class="border-y border-slate-200/80 bg-white dark:border-slate-800/80 dark:bg-slate-950"
        aria-label="Platform statistics"
    >
        <div
            class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 sm:grid-cols-4 sm:px-6 lg:px-8"
        >
            <div
                v-for="item in items"
                :key="item.key"
                class="text-center sm:text-left"
            >
                <p
                    class="font-display text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums dark:text-white"
                >
                    <template v-if="item.key === 'papers'"
                        >{{ paperCount.toLocaleString()
                        }}{{ item.suffix }}</template
                    >
                    <template v-else-if="item.key === 'students'"
                        >{{ studentCount.toLocaleString()
                        }}{{ item.suffix }}</template
                    >
                    <template v-else-if="item.key === 'depts'"
                        >{{ deptCount }}{{ item.suffix }}</template
                    >
                    <template v-else>{{ item.value }}</template>
                </p>
                <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">
                    {{ item.label }}
                </p>
            </div>
        </div>
    </section>
</template>
