<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    ClipboardList,
    GraduationCap,
    QrCode,
    ShieldCheck,
    Telescope,
    Users,
} from 'lucide-vue-next';
import { faq } from '@/routes';

const roleGuides = [
    {
        title: 'Students',
        icon: GraduationCap,
        items: [
            'Submit research proposals and monitor progress by workflow stage.',
            'Join classes using join codes and coordinate with advisers/panelists.',
            'Track defense schedules and final manuscript/hard-bound milestones.',
        ],
    },
    {
        title: 'Faculty',
        icon: Users,
        items: [
            'Manage advisees by class and review status transitions quickly.',
            'Provide comments and approvals tied to each step.',
            'View defense calendars and panel assignments for supervised papers.',
        ],
    },
    {
        title: 'Admin & Staff',
        icon: ShieldCheck,
        items: [
            'Configure departments, programs, subjects, SDGs, and agendas.',
            'Manage users, approvals, class operations, and announcements.',
            'Audit critical actions through System Logs and rate-limit protection.',
        ],
    },
];

const workflowSteps = [
    'Title Proposal',
    'RIC/Admin Review',
    'Plagiarism Check',
    'Outline Defense',
    'Rating',
    'Final Manuscript',
    'Final Defense',
    'Hard Bound',
    'Completed',
];

const keyFeatures = [
    {
        title: 'Public Tracking',
        description:
            'Shareable tracking IDs let users view paper status without signing in.',
        icon: Telescope,
    },
    {
        title: 'QR Access',
        description:
            'Generate QR links for quick access to paper tracking and record pages.',
        icon: QrCode,
    },
    {
        title: 'Defense Calendar',
        description:
            'Centralized monthly schedules for outline/final defenses with panel context.',
        icon: CalendarDays,
    },
    {
        title: 'Structured Workflow',
        description:
            'Every paper moves through predefined stages with status history and comments.',
        icon: ClipboardList,
    },
];
</script>

<template>
    <Head title="Documentation - UMRIC" />

    <main class="mx-auto max-w-6xl space-y-10 px-5 py-10">
        <section
            class="rounded-2xl border border-slate-200 bg-slate-50 p-7 dark:border-slate-800 dark:bg-slate-900/60"
        >
            <div
                class="flex flex-wrap items-center gap-2 text-sm font-semibold text-orange-600 dark:text-orange-400"
            >
                <BookOpen class="h-4 w-4" />
                Platform Documentation
            </div>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                UMRIC User Guide
            </h1>
            <p
                class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300"
            >
                UMRIC is a complete research-paper workflow platform for
                students, faculty, and administrators. It combines submission,
                review, defense scheduling, and audit logging into one
                role-based system.
            </p>
        </section>

        <section>
            <h2 class="text-xl font-bold">Role-Based Guide</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <article
                    v-for="guide in roleGuides"
                    :key="guide.title"
                    class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-2">
                        <component
                            :is="guide.icon"
                            class="h-4 w-4 text-teal-500"
                        />
                        <h3 class="font-bold">{{ guide.title }}</h3>
                    </div>
                    <ul
                        class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-slate-600 dark:text-slate-300"
                    >
                        <li v-for="item in guide.items" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </article>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold">Workflow Stages</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                Papers follow this standard lifecycle from initial proposal to
                archive-ready output.
            </p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(step, idx) in workflowSteps"
                    :key="step"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold dark:border-slate-800 dark:bg-slate-900"
                >
                    <span
                        class="mr-2 text-xs text-orange-600 dark:text-orange-400"
                        >{{ idx + 1 }}.</span
                    >
                    {{ step }}
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold">Core Features</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <article
                    v-for="feature in keyFeatures"
                    :key="feature.title"
                    class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-center gap-2">
                        <component
                            :is="feature.icon"
                            class="h-4 w-4 text-orange-500"
                        />
                        <h3 class="font-bold">{{ feature.title }}</h3>
                    </div>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        {{ feature.description }}
                    </p>
                </article>
            </div>
        </section>

        <section
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300"
        >
            Need quick answers? Visit the
            <Link
                :href="faq.url()"
                class="font-semibold underline decoration-dotted"
                >FAQ page</Link
            >
            for common troubleshooting and usage questions.
        </section>
    </main>
</template>
