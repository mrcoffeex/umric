<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock, Mail } from 'lucide-vue-next';

defineOptions({
    layout: {
        title: 'Pending Approval',
        description: '',
    },
});
</script>

<template>
    <Head title="Pending Approval" />

    <div
        class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 p-4 dark:from-slate-950 dark:to-slate-900"
    >
        <div class="w-full max-w-md text-center">
            <!-- Icon -->
            <div
                class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-3xl bg-amber-100 dark:bg-amber-950/40"
            >
                <Clock class="h-8 w-8 text-amber-600 dark:text-amber-400" />
            </div>

            <!-- Heading -->
            <h1 class="mb-2 text-2xl font-black text-slate-900 dark:text-white">
                Pending Approval
            </h1>
            <p class="mb-8 text-sm text-muted-foreground">
                Your faculty account is awaiting administrative approval. This
                typically takes 1–2 business days.
            </p>

            <!-- Status card -->
            <div
                class="mb-8 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-600 dark:bg-amber-400"
                    />
                    <div class="text-left">
                        <p
                            class="text-sm font-semibold text-amber-800 dark:text-amber-300"
                        >
                            Your account has been created
                        </p>
                        <p
                            class="mt-0.5 text-xs text-amber-700 dark:text-amber-400"
                        >
                            An administrator will review and approve your
                            registration shortly.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="mb-8 space-y-3 text-left">
                <div class="flex items-start gap-3">
                    <Mail class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
                    <div class="text-sm">
                        <p
                            class="font-medium text-slate-700 dark:text-slate-300"
                        >
                            We'll notify you by email
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            You'll receive a notification when your account is
                            approved.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action -->
            <Link
                href="/"
                class="inline-flex w-full items-center justify-center rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-900 transition-colors hover:bg-slate-300 dark:bg-slate-700 dark:text-white dark:hover:bg-slate-600"
            >
                Return to Home
            </Link>
        </div>
    </div>
</template>
