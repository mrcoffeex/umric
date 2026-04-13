<script setup lang="ts">
interface Props {
    padding?: string;
}

withDefaults(defineProps<Props>(), {
    padding: '1.5rem',
});
</script>

<template>
    <div
        class="rounded-xl border border-slate-200 bg-white p-[var(--card-padding)] shadow-sm transition-colors dark:border-gray-800 dark:bg-gray-950"
        :style="{ '--card-padding': padding }"
    >
        <slot />
    </div>
</template>
