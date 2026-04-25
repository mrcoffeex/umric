<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { isCurrentUrl } = useCurrentUrl();

function badgeLabel(count: number) {
    return count > 99 ? '99+' : String(count);
}

function tooltipText(item: NavItem) {
    const c = item.badgeCount;

    if (c != null && c > 0) {
        return `${item.title} · ${c} pending handoff${c === 1 ? '' : 's'}`;
    }

    return item.title;
}

function hasBadge(item: NavItem) {
    return (item.badgeCount ?? 0) > 0;
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="tooltipText(item)"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
                <SidebarMenuBadge
                    v-if="hasBadge(item)"
                    :class="
                        cn(
                            'bg-orange-500/20 text-orange-900 dark:text-orange-200',
                        )
                    "
                >
                    {{ badgeLabel(item.badgeCount ?? 0) }}
                </SidebarMenuBadge>
                <span
                    v-if="hasBadge(item)"
                    class="pointer-events-none absolute top-2 right-0.5 z-10 hidden h-1.5 w-1.5 rounded-full bg-orange-500 group-data-[collapsible=icon]:block"
                    aria-hidden="true"
                />
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
