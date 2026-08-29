<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';
import { watch } from 'vue';
import MaintenanceController from '@/actions/App/Http/Controllers/Admin/MaintenanceController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type MaintenanceData = {
    enabled: boolean;
    message: string | null;
};

const props = defineProps<{
    maintenance: MaintenanceData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: '#' },
            { title: 'Maintenance mode', href: admin.maintenance.index.url() },
        ],
    },
});

const page = usePage();

const form = useForm({
    enabled: props.maintenance.enabled,
    message: props.maintenance.message ?? '',
});

watch(
    () => props.maintenance,
    (m) => {
        form.enabled = m.enabled;
        form.message = m.message ?? '';
        form.clearErrors();
    },
    { deep: true },
);

function submit() {
    form.put(MaintenanceController.update.url(), { preserveScroll: true });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Maintenance mode" />

        <div
            v-if="page.props.maintenance?.enabled"
            class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-100"
        >
            Maintenance mode is currently
            <span class="font-semibold">on</span>. Staff, faculty, and students
            cannot use the system until you turn it off.
        </div>

        <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div class="mb-6 flex items-start gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30"
                >
                    <Construction
                        class="h-5 w-5 text-orange-600 dark:text-orange-400"
                    />
                </div>
                <div>
                    <h1 class="text-lg font-semibold tracking-tight sm:text-xl">
                        System maintenance
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Temporarily block non-admin users while you perform
                        updates. Admins can still sign in and manage the system.
                    </p>
                </div>
            </div>

            <form class="max-w-lg space-y-5" @submit.prevent="submit">
                <div class="flex items-start gap-3 rounded-lg border p-4">
                    <Checkbox
                        id="enabled"
                        :model-value="form.enabled"
                        class="mt-0.5"
                        @update:model-value="
                            (v) => (form.enabled = v === true)
                        "
                    />
                    <div class="space-y-1">
                        <Label for="enabled" class="cursor-pointer">
                            Enable maintenance mode
                        </Label>
                        <p class="text-xs text-muted-foreground">
                            When enabled, other roles see a maintenance screen
                            and a warning toast if they try to use the app.
                        </p>
                    </div>
                </div>
                <InputError :message="form.errors.enabled" />

                <div class="space-y-2">
                    <Label for="message">Message (optional)</Label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="3"
                        maxlength="500"
                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="The system is temporarily under maintenance. Please try again later."
                    />
                    <p class="text-xs text-muted-foreground">
                        Shown to blocked users. Leave blank to use the default
                        message.
                    </p>
                    <InputError :message="form.errors.message" />
                </div>

                <div class="pt-2">
                    <Button type="submit" :disabled="form.processing">
                        Save changes
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
