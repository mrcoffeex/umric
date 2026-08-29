<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useBranding } from '@/composables/useBranding';
import { logout } from '@/routes';

defineOptions({
    layout: {
        title: 'Maintenance',
        description: 'The system is temporarily unavailable.',
    },
});

defineProps<{
    message: string;
}>();

const branding = useBranding();
</script>

<template>
    <Head title="Maintenance mode" />

    <div class="flex flex-col items-center gap-6 text-center">
        <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-orange-50 ring-4 ring-orange-100 dark:bg-orange-900/30 dark:ring-orange-800/50"
        >
            <Construction
                class="h-8 w-8 text-orange-600 dark:text-orange-400"
            />
        </div>

        <div class="space-y-2">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
                Maintenance mode
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ branding.name }} is temporarily unavailable for your account.
            </p>
        </div>

        <div
            class="w-full rounded-xl border border-orange-100 bg-orange-50/60 px-5 py-4 text-left dark:border-orange-800/40 dark:bg-orange-900/20"
        >
            <p class="text-sm text-orange-900 dark:text-orange-200">
                {{ message }}
            </p>
        </div>

        <Form v-bind="logout.form()" class="w-full">
            <Button type="submit" variant="secondary" class="w-full">
                Log out
            </Button>
        </Form>
    </div>
</template>
