<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CircleHelp } from 'lucide-vue-next';
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { documentation, privacy, terms } from '@/routes';

const branding = useBranding();

const faqTitle = computed(() => `FAQ - ${branding.value.name}`);

const faqs = computed(() => {
    const n = branding.value.name;

    return [
        {
            question: 'How do I start submitting a research paper?',
            answer: 'After you sign in, open My Research (students), create a new entry, and fill in title, abstract, category, and SDG/agenda tags as required. On supported uploads you can pre-fill title, abstract, and keywords from a PDF or DOCX file before saving.',
        },
        {
            question: `What are the official workflow stages in ${n}?`,
            answer: 'The app uses this order (the same labels as on a paper’s detail page): Title Evaluation, RIC/Admin Review, Outline Defense, Data Gathering, Rating, Final Manuscript, Final Defense, Hard Bound, and Completed. “Title Evaluation” is the display name for the first stage, even though you may still call it a title proposal. Data Gathering is its own step between outline defense and rating. Plagiarism or similarity data can be kept on the record but is not a separate numbered step in that list. The timeline’s highlight follows the first step that is still missing its required outcome; see the User Guide for details.',
        },
        {
            question: 'What are Handoffs?',
            answer: 'Handoffs are in-app document transmissions: you can send a package to another user, they receive and acknowledge it, and items can be forwarded in a chain. Recipients may get a secure link to claim or open what was sent, depending on how your institution configured the feature.',
        },
        {
            question: 'What is panel defense evaluation?',
            answer: 'Admins set up evaluation formats and criteria (rubrics) for defenses. Faculty assigned as panelists complete evaluations for a defense, and the system can produce PDFs for records where that action is available. You will see Evaluation in the sidebar when your role can access it.',
        },
        {
            question: 'How can I track a paper without logging in?',
            answer: 'Use the public tracking page with the paper’s tracking ID. Signed-in users can also open a paper and use QR options where available. What is visible publicly may be limited to status and other non-sensitive fields.',
        },
        {
            question: 'How do faculty members monitor student work?',
            answer: 'From Research Papers and class views, faculty can read papers, add comments, update workflow steps, use step approvals when required, manage classes and join codes, work with the defense calendar, complete panel evaluations, and use Handoffs to exchange documents with students and staff.',
        },
        {
            question: 'What can admin and staff users configure?',
            answer: 'They manage departments, programs, subjects, classes, SDGs, agendas, announcements, the defense calendar, and research records (including assignments and panel defense rows). Admins also maintain evaluation formats and criteria, approve new users, review System Logs, and manage Handoffs. Full user and role management is limited to the admin role where your policy allows.',
        },
        {
            question: 'Where can I find audit and security logs?',
            answer: 'Admins can open System Logs in the admin area. Entries include who did what, when, and context such as IP address, browser, device, and location headers when available, plus protection against abusive bulk admin actions.',
        },
        {
            question: 'Can I sign in with Google?',
            answer: 'If your school enabled it, use Continue with Google on the sign-in or register page. You may still need email verification and, for new self-registration, staff approval before the full app unlocks.',
        },
        {
            question: 'Why is my account pending after I registered?',
            answer: 'Some deployments require a staff or admin to approve new accounts. Stay on the registration-pending page or check your email; once approved, you can use the same credentials (including Google) to sign in.',
        },
        {
            question: 'Is two-factor authentication (2FA) available?',
            answer: 'Yes. In your account security settings you can enable two-factor authentication (authenticator app and recovery codes) in addition to your password, where your institution allows it.',
        },
        {
            question: 'Why does my role not see certain menus?',
            answer: 'Navigation is role-based. Students, faculty, staff, and admin each see only the areas they are allowed to use (for example Handoffs, Evaluation, and admin tools appear when applicable).',
        },
        {
            question: 'Can I join a class later if I missed enrollment?',
            answer: 'Yes, if a faculty member or admin gives you a valid join code and the class is still active, you can use the class join link or flow to enroll.',
        },
        {
            question: 'How do I reset my password?',
            answer: 'Use Forgot Password on the sign-in page. A reset link is sent to your registered email. If you use Google-only access, use Google to sign in or add a password in settings if your school supports it.',
        },
        {
            question:
                'Where can I read the Terms & Conditions and Privacy Policy?',
            answer: 'Use the Terms and Privacy links in the header or footer on Docs, FAQ, and the welcome page, or the User Guide. They cover acceptable use and how data is processed.',
        },
        {
            question: `What data does ${n} collect about me and my research?`,
            answer: 'The platform uses account and profile data, research and class workflow data, handoff and evaluation activity as needed, and security logs. Full detail is in the Privacy Policy, including cookies and local storage.',
        },
        {
            question: `Does using ${n} replace official college policies?`,
            answer: `No. ${n} supports the official workflow; grades, program rules, and publication decisions are governed by the institution. If anything on the site conflicts with published school policy, the institution's rules apply.`,
        },
    ];
});
</script>

<template>
    <Head :title="faqTitle" />

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
                {{ branding.name }} FAQ
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Quick answers on research submission, handoffs, defense
                evaluation, sign-in, and day-to-day use of {{ branding.name }}.
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
            class="mt-8 space-y-3 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-800 dark:border-teal-900/40 dark:bg-teal-950/20 dark:text-teal-300"
        >
            <p>
                Looking for full feature coverage?
                <Link
                    :href="documentation.url()"
                    class="font-semibold underline decoration-dotted"
                >
                    Open documentation
                </Link>
            </p>
            <p>
                Legal:
                <Link
                    :href="terms.url()"
                    class="font-semibold underline decoration-dotted"
                >
                    Terms &amp; Conditions
                </Link>
                ·
                <Link
                    :href="privacy.url()"
                    class="font-semibold underline decoration-dotted"
                >
                    Privacy Policy
                </Link>
            </p>
        </section>
    </main>
</template>
