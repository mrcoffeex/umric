<script setup lang="ts">
import { ListFilter } from 'lucide-vue-next';

defineProps<{
    items: { id: string; label: string }[];
}>();
</script>

<template>
    <nav
        v-if="items.length"
        aria-label="On this page"
        class="sticky top-0 z-10 -mx-4 border-b border-border/80 bg-background/90 px-4 py-2.5 shadow-[0_1px_0_0_hsl(var(--border)/0.5)] backdrop-blur-md md:mx-0 md:rounded-xl md:border"
    >
        <div
            class="mb-1.5 flex items-center gap-1.5 text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
        >
            <ListFilter class="h-3.5 w-3.5 shrink-0 text-orange-500" />
            <span>On this page</span>
        </div>
        <div
            class="flex gap-1.5 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] sm:flex-wrap sm:overflow-visible [&::-webkit-scrollbar]:hidden"
        >
            <a
                v-for="item in items"
                :key="item.id"
                :href="`#${item.id}`"
                class="shrink-0 rounded-lg border border-border/60 bg-muted/40 px-2.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:border-border hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                {{ item.label }}
            </a>
        </div>
    </nav>
</template>
