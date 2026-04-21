<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Briefcase, Clock, Mail } from 'lucide-vue-next';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Registration Pending',
        description: 'Your registration is under review.',
    },
});
</script>

<template>
    <Head title="Registration Pending" />

    <div class="flex flex-col items-center gap-6 text-center">
        <!-- Icon -->
        <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-teal-50 ring-4 ring-teal-100 dark:bg-teal-900/30 dark:ring-teal-800/50"
        >
            <Clock class="h-8 w-8 text-teal-600 dark:text-teal-400" />
        </div>

        <!-- Heading -->
        <div class="space-y-2">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
                Awaiting Approval
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Your registration as a faculty member is waiting for approval by
                the administrator.
            </p>
        </div>

        <!-- Info card -->
        <div
            class="w-full rounded-xl border border-teal-100 bg-teal-50/60 px-5 py-4 text-left dark:border-teal-800/40 dark:bg-teal-900/20"
        >
            <div class="flex items-start gap-3">
                <Mail
                    class="mt-0.5 h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400"
                />
                <p class="text-sm text-teal-800 dark:text-teal-300">
                    An email will be sent to you once your account has been
                    reviewed. Please check your inbox (and spam folder) for
                    updates.
                </p>
            </div>
        </div>

        <!-- What happens next -->
        <div class="w-full space-y-3 text-left">
            <p
                class="text-xs font-semibold tracking-wide text-gray-400 uppercase dark:text-gray-500"
            >
                What happens next
            </p>
            <ol class="space-y-2">
                <li class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-600 dark:bg-orange-900/30 dark:text-orange-400"
                        >1</span
                    >
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >An administrator reviews your faculty registration
                        request.</span
                    >
                </li>
                <li class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-600 dark:bg-orange-900/30 dark:text-orange-400"
                        >2</span
                    >
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >You receive an email notification with the
                        decision.</span
                    >
                </li>
                <li class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-600 dark:bg-orange-900/30 dark:text-orange-400"
                        >3</span
                    >
                    <span class="text-sm text-gray-600 dark:text-gray-400"
                        >Once approved, you can log in and access the
                        system.</span
                    >
                </li>
            </ol>
        </div>

        <!-- Back to login -->
        <div
            class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
        >
            <Briefcase class="h-3.5 w-3.5" />
            Already approved?
            <TextLink :href="login()">Sign in</TextLink>
        </div>
    </div>
</template>
