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

    <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900">
        <div class="w-full max-w-md text-center">
            <!-- Icon -->
            <div class="w-16 h-16 rounded-3xl bg-amber-100 dark:bg-amber-950/40 flex items-center justify-center mx-auto mb-6">
                <Clock class="w-8 h-8 text-amber-600 dark:text-amber-400" />
            </div>

            <!-- Heading -->
            <h1 class="text-2xl font-black text-slate-900 dark:text-white mb-2">
                Pending Approval
            </h1>
            <p class="text-sm text-muted-foreground mb-8">
                Your faculty account is awaiting administrative approval. This typically takes 1–2 business days.
            </p>

            <!-- Status card -->
            <div class="rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-950/20 p-4 mb-8">
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 rounded-full bg-amber-600 dark:bg-amber-400 mt-1.5 shrink-0" />
                    <div class="text-left">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            Your account has been created
                        </p>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                            An administrator will review and approve your registration shortly.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="space-y-3 mb-8 text-left">
                <div class="flex items-start gap-3">
                    <Mail class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" />
                    <div class="text-sm">
                        <p class="font-medium text-slate-700 dark:text-slate-300">We'll notify you by email</p>
                        <p class="text-xs text-muted-foreground mt-0.5">You'll receive a notification when your account is approved.</p>
                    </div>
                </div>
            </div>

            <!-- Action -->
            <Link
                href="/"
                class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white text-sm font-semibold hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors"
            >
                Return to Home
            </Link>
        </div>
    </div>
</template>
