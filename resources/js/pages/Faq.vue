<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CircleHelp } from 'lucide-vue-next';
import { documentation } from '@/routes';

const faqs = [
    {
        question: 'How do I start submitting a research paper?',
        answer: 'Sign in with your account, go to your research module, and create a new paper entry. Provide required metadata such as title, abstract, category, and associated SDG/agenda tags when available.',
    },
    {
        question: 'What are the official workflow stages in UMRIC?',
        answer: 'A paper progresses through Title Proposal, RIC/Admin Review, Plagiarism Check, Outline Defense, Rating, Final Manuscript, Final Defense, Hard Bound, and Completed.',
    },
    {
        question: 'How can I track a paper without logging in?',
        answer: 'Use the public tracking route with the paper tracking ID. This allows status visibility for authorized public tracking views.',
    },
    {
        question: 'How do faculty members monitor student work?',
        answer: 'Faculty can review papers by class, post comments, approve required steps, and monitor defense schedules from dedicated faculty pages.',
    },
    {
        question: 'What can admin and staff users configure?',
        answer: 'Admins/staff can manage departments, programs, subjects, classes, agendas, SDGs, announcements, user approvals, and defense calendar records.',
    },
    {
        question: 'Where can I find audit and security logs?',
        answer: 'Admins can open System Logs from the admin area. Logs include action metadata such as event details, IP address, browser, device, and location headers when available.',
    },
    {
        question: 'Why does my role not see certain menus?',
        answer: 'Navigation is role-based. Student, Faculty, Staff, and Admin each have different permissions and menu visibility according to platform policies.',
    },
    {
        question: 'Can I join a class later if I missed enrollment?',
        answer: 'Yes, students can join classes using valid join codes provided by faculty/admin as long as the class remains active and the code has not been revoked.',
    },
    {
        question: 'How do I reset my password?',
        answer: 'Use the Forgot Password flow on the sign-in page. A reset link will be sent to your registered email address.',
    },
];
</script>

<template>
    <Head title="FAQ - UMRIC" />

    <main class="mx-auto max-w-5xl px-5 py-10">
        <section
            class="rounded-2xl border border-slate-200 bg-slate-50 p-7 dark:border-slate-800 dark:bg-slate-900/60"
        >
            <div
                class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-bold tracking-wide text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
            >
                <CircleHelp class="h-3.5 w-3.5" />
                Frequently Asked Questions
            </div>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                UMRIC FAQ
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Quick answers about submission, workflow, permissions, tracking,
                and system operations.
            </p>
        </section>

        <section class="mt-6 space-y-3">
            <details
                v-for="item in faqs"
                :key="item.question"
                class="group rounded-xl border border-slate-200 bg-white p-4 open:border-orange-300 dark:border-slate-800 dark:bg-slate-900 dark:open:border-orange-700"
            >
                <summary
                    class="cursor-pointer list-none text-sm font-semibold text-slate-800 outline-none dark:text-slate-100"
                >
                    <span class="inline-flex items-start gap-2">
                        <span class="mt-0.5 text-orange-500">+</span>
                        {{ item.question }}
                    </span>
                </summary>
                <p
                    class="mt-3 border-t border-slate-100 pt-3 text-sm leading-relaxed text-slate-600 dark:border-slate-800 dark:text-slate-300"
                >
                    {{ item.answer }}
                </p>
            </details>
        </section>

        <section
            class="mt-8 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800 dark:border-teal-900/40 dark:bg-teal-950/20 dark:text-teal-300"
        >
            Looking for full feature coverage?
            <Link
                :href="documentation.url()"
                class="font-semibold underline decoration-dotted"
            >
                Open Documentation
            </Link>
        </section>
    </main>
</template>
