<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Theme toggle — segmented pill -->
        <div class="flex items-center gap-0.5 rounded-xl bg-muted p-1">
            <button
                v-for="opt in options"
                :key="opt.value"
                :title="opt.label"
                @click="updateAppearance(opt.value)"
                :class="[
                    'flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition-all',
                    appearance === opt.value
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
            >
                <component :is="opt.icon" class="size-3.5 shrink-0" />
                <span class="hidden sm:inline">{{ opt.label }}</span>
            </button>
        </div>
    </header>
</template>
