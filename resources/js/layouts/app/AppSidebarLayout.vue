<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AlertModal from '@/components/AlertModal.vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const maintenanceEnabled = computed(
    () =>
        page.props.maintenance?.enabled === true &&
        (page.props.auth.user as { role?: string } | null)?.role === 'admin',
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <div
                v-if="maintenanceEnabled"
                class="border-b border-orange-200 bg-orange-50 px-4 py-2 text-center text-sm text-orange-900 dark:border-orange-800 dark:bg-orange-950/50 dark:text-orange-100"
            >
                Maintenance mode is on — non-admin users cannot use the system.
            </div>
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
        <AlertModal />
        <ConfirmModal />
    </AppShell>
</template>
