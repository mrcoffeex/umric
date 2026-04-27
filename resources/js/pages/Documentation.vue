<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    ClipboardList,
    FileSearch,
    GraduationCap,
    ListChecks,
    Send,
    ShieldCheck,
    Telescope,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { WORKFLOW_STEP_KEYS } from '@/lib/research-workflow-ui';
import type { WorkflowStepKey } from '@/lib/research-workflow-ui';
import { faq, privacy, terms } from '@/routes';

const branding = useBranding();
const docTitle = computed(() => `Documentation - ${branding.value.name}`);

/**
 * Human-readable names — keep aligned with
 * `ResearchPaper::STEP_LABELS` (app/Models/ResearchPaper.php).
 */
const WORKFLOW_LABELS: Record<WorkflowStepKey, string> = {
    title_proposal: 'Title Evaluation',
    ric_review: 'RIC/Admin Review',
    outline_defense: 'Outline Defense',
    data_gathering: 'Data Gathering',
    rating: 'Rating',
    final_manuscript: 'Final Manuscript',
    final_defense: 'Final Defense',
    hard_bound: 'Hard Bound',
    completed: 'Completed',
};

/**
 * Explanations for the user guide. Step order and transitions follow
 * `updateStep` on admin research and the faculty class research controller.
 */
const stepDocumentation: Record<WorkflowStepKey, string> = {
    title_proposal:
        'Students create and refine the title proposal in their class. While the paper is still in this first stage, the student can edit or delete it. Staff move it into RIC/Admin review when the proposal is ready. If RIC returns the work, the student revises and resubmits, which places it back in review.',
    ric_review:
        'Admins and faculty record whether the title proposal is approved, sent back to the student for revision, or rejected. Approval moves the paper to Outline Defense. A return may send the paper back to Title Evaluation for edits.',
    outline_defense:
        'Panel outcomes and scheduling for the outline defense are recorded. When the defense is marked passed, the paper advances to Data Gathering (with an optional re-defense path depending on your program’s rules).',
    data_gathering:
        'Tracks completion of the data-gathering phase. When it is marked completed, the workflow advances to Rating.',
    rating: 'Faculty and admins can record a rating and optional overall grade, then mark the step rated. That unlocks the Final Manuscript step.',
    final_manuscript:
        'The final manuscript is submitted and reviewed. When it is accepted as submitted, the paper moves to Final Defense preparation.',
    final_defense:
        'The final defense is scheduled, held, and marked passed (or re-defense). Passing advances the process to the Hard Bound / binding stage.',
    hard_bound:
        'The bound copy of the work is produced and then marked submitted, which can mark the research as complete and ready for publication in the system.',
    completed:
        'The paper has finished the pipeline. No further workflow step changes are expected here.',
};

const workflowStepRows = WORKFLOW_STEP_KEYS.map((key) => ({
    key,
    name: WORKFLOW_LABELS[key],
    detail: stepDocumentation[key],
}));

const roleGuides = [
    {
        title: 'Students',
        icon: GraduationCap,
        items: [
            'Create papers under My Research; optionally extract title, abstract, and keywords from a PDF or DOCX when building your entry.',
            'Upload defense-related documents, move through workflow steps, and use Handoffs to send and receive document packages (including claim links) with faculty and staff.',
            'Join classes with join codes, open the defense calendar, and track outline/final defense and hard-bound milestones.',
        ],
    },
    {
        title: 'Faculty',
        icon: Users,
        items: [
            'Review work from Research Papers or a class, post comments, update steps, and use approvals where your program requires them.',
            'Complete panel defense evaluations using institution rubrics, export PDFs when available, and share calendars with advisees.',
            'Run classes (join codes, rosters) and use Handoffs to pass documents along with optional forwarding to the next recipient.',
        ],
    },
    {
        title: 'Admin & Staff',
        icon: ShieldCheck,
        items: [
            'Oversee the full research list: assignments, SDG/agenda tags, panel defense rows, and step updates with full auditability.',
            'Configure master data (departments, programs, subjects, SDGs, agendas), classes, announcements, and evaluation formats with criteria for defenses.',
            'Use Defense Calendar, approve pending registrations, review System Logs, and manage Handoffs; sensitive admin actions are throttled and logged.',
        ],
    },
];

const keyFeatures = [
    {
        title: 'Public tracking & QR',
        description:
            'Anyone with a tracking ID can see status on the public page; signed-in users can generate QR codes for quick access to a paper’s views.',
        icon: Telescope,
    },
    {
        title: 'Handoffs',
        description:
            'Create document transmissions, notify recipients, receive and acknowledge packages, and forward items along a chain with secure claim links.',
        icon: Send,
    },
    {
        title: 'Panel defense evaluation',
        description:
            'Admins define evaluation formats and rubric criteria; faculty submit scores; PDF exports support official records when enabled.',
        icon: ListChecks,
    },
    {
        title: 'Defense calendar',
        description:
            'Role-specific calendars for outline and final defenses with scheduling context and panel information.',
        icon: CalendarDays,
    },
    {
        title: 'Structured workflow',
        description:
            'Papers move through defined stages with history, comments, and notifications tied to each step.',
        icon: ClipboardList,
    },
    {
        title: 'Document metadata help',
        description:
            'When creating research, students can upload a PDF or DOCX to pre-fill title, abstract, and keywords (when extraction succeeds).',
        icon: FileSearch,
    },
];
</script>

<template>
    <Head :title="docTitle" />

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
                {{ branding.name }} User Guide
            </h1>
            <p
                class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300"
            >
                {{ branding.name }} is a research-paper workflow platform for
                students, faculty, and administrators. It brings together
                submission and review, defense calendars, configurable panel
                evaluations, handoffs for routed documents, public tracking, and
                comprehensive audit logging in one role-based system. Sign in
                with email or continue with Google where enabled; new accounts
                may require staff approval.
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
            <h2 class="text-xl font-bold">Workflow and step management</h2>
            <p
                class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300"
            >
                The ordered list below is the end-to-end pipeline from first
                submission to completion. The names match what you see on the
                research paper screen, including
                <span class="font-medium text-slate-800 dark:text-slate-200"
                    >Title Evaluation</span
                >
                (the in-app name for the initial title / proposal stage) and
                <span class="font-medium text-slate-800 dark:text-slate-200"
                    >Data Gathering</span
                >
                (the stage after outline defense and before rating). The same
                order is used in the back end and the progress bar logic, so
                this page stays aligned with how steps are actually managed in
                the app.
            </p>

            <p
                class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300"
            >
                <span class="font-semibold text-slate-800 dark:text-slate-200"
                    >How progress is highlighted:</span
                >
                the timeline and percentage bar use the
                <em>first</em> step that does not yet have its required outcome
                (pending RIC, pending defense result, and so on). That can
                differ from the raw
                <code
                    class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                    >current_step</code
                >
                value alone, by design, so the interface points at the work that
                still needs attention.
            </p>

            <p
                class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300"
            >
                <span class="font-semibold text-slate-800 dark:text-slate-200"
                    >Who updates what:</span
                >
                admins and staff can move every sub-step. Faculty (from a class
                research view) can update outline defense, data gathering,
                rating, and final defense, and can approve a paper that is
                already in RIC/Admin review. Students submit the title proposal,
                upload defense PDFs when required, and handle RIC return cycles
                from Title Evaluation.
            </p>

            <h3
                class="mt-6 text-sm font-bold tracking-wide text-slate-500 uppercase dark:text-slate-400"
            >
                Ordered steps
            </h3>
            <ol class="mt-3 list-none space-y-3 p-0">
                <li
                    v-for="(row, idx) in workflowStepRows"
                    :key="row.key"
                    class="flex gap-3 rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-bold text-orange-800 dark:bg-orange-900/40 dark:text-orange-200"
                    >
                        {{ idx + 1 }}
                    </span>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-slate-100">
                            {{ row.name }}
                        </p>
                        <p
                            class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300"
                        >
                            {{ row.detail }}
                        </p>
                    </div>
                </li>
            </ol>

            <div
                class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-300"
            >
                <p class="font-semibold text-slate-800 dark:text-slate-200">
                    Plagiarism and similar checks
                </p>
                <p class="mt-1.5">
                    The database can store plagiarism-related fields (status,
                    attempts, and scores) on the same research record, but
                    <strong>they are not a separate numbered step</strong> in
                    the pipeline above. They may be used in parallel with review
                    or at your program’s discretion, independent of Data
                    Gathering and other stages.
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-500">
                    Developer note: primary step keys are
                    <code class="rounded bg-slate-200/80 px-1 dark:bg-slate-800"
                        >ResearchPaper::STEPS</code
                    >
                    (PHP) and
                    <code class="rounded bg-slate-200/80 px-1 dark:bg-slate-800"
                        >WORKFLOW_STEP_KEYS</code
                    >
                    (TypeScript).
                </p>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold">Core features</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                Capabilities that sit alongside the standard workflow (see
                above). Availability depends on your role and institutional
                configuration.
            </p>
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

        <section>
            <h2 class="text-xl font-bold">Terms, privacy, and support</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                Use of {{ branding.name }} is subject to institutional policies.
                Read the platform rules and how account and research data are
                handled.
            </p>
            <div
                class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-300"
            >
                <Link
                    :href="terms.url()"
                    class="font-semibold text-orange-600 underline decoration-dotted dark:text-orange-400"
                >
                    Terms &amp; Conditions
                </Link>
                <span class="text-slate-400">·</span>
                <Link
                    :href="privacy.url()"
                    class="font-semibold text-orange-600 underline decoration-dotted dark:text-orange-400"
                >
                    Privacy Policy
                </Link>
                <span class="text-slate-400">·</span>
                <span class="text-slate-500">Cookies:</span>
                <Link
                    :href="`${privacy.url()}#cookies`"
                    class="font-semibold text-orange-600 underline decoration-dotted dark:text-orange-400"
                >
                    see Privacy Policy
                </Link>
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
