<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText } from 'lucide-vue-next';
import { documentation, faq, privacy } from '@/routes';

const lastUpdated = 'April 27, 2026';
</script>

<template>
    <Head title="Terms & Conditions" />

    <main class="mx-auto max-w-3xl px-5 py-10">
        <section
            class="rounded-2xl border border-slate-200 bg-slate-50 p-7 dark:border-slate-800 dark:bg-slate-900/60"
        >
            <div
                class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-bold tracking-wide text-orange-700 dark:bg-orange-900/30 dark:text-orange-300"
            >
                <FileText class="h-3.5 w-3.5" />
                Legal
            </div>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                Terms &amp; Conditions
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Last updated: {{ lastUpdated }}
            </p>
        </section>

        <article class="mt-8 space-y-6 text-sm leading-relaxed">
            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    1. Agreement
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    These Terms &amp; Conditions (&ldquo;Terms&rdquo;) govern
                    your access to and use of UMRIC (the &ldquo;Service&rdquo;),
                    the official research-paper tracking and workflow platform
                    &mdash; including submission, review, defense scheduling,
                    and related tools &mdash; operated for UM Digos College and
                    authorized users. By creating an account or using the
                    Service, you agree to these Terms.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    2. Eligibility and accounts
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    The Service is intended for students, faculty, staff, and
                    administrators affiliated with the institution&rsquo;s
                    research programs, as approved by institutional
                    administrators. You are responsible for accurate
                    registration information, safeguarding your credentials, and
                    all activity under your account. The institution may suspend
                    or revoke access for policy violations or when employment or
                    enrollment ends.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    3. Acceptable use
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    You agree to use UMRIC only for lawful, institutional
                    purposes, including to:
                </p>
                <ul
                    class="mt-2 list-disc space-y-1.5 pl-5 text-slate-600 dark:text-slate-300"
                >
                    <li>
                        Submit and manage research metadata, documents, and
                        workflow steps consistent with program rules.
                    </li>
                    <li>
                        Respect confidentiality of classes, join codes, peer
                        work, and committee deliberations.
                    </li>
                    <li>
                        Not attempt to disrupt the Service, probe security
                        beyond your role, or access data you are not authorized
                        to see.
                    </li>
                </ul>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    4. User content and research materials
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    You retain ownership of intellectual property in your
                    research, subject to institutional policies, adviser
                    agreements, and applicable law. By uploading or entering
                    content, you represent that you have the rights needed to
                    process it in UMRIC for grading, review, defense scheduling,
                    and related academic operations. The institution may use
                    such content for those purposes in line with the
                    <Link
                        :href="privacy.url()"
                        class="font-semibold text-orange-600 underline decoration-dotted dark:text-orange-400"
                    >
                        Privacy Policy
                    </Link>
                    .
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    5. Platform role and changes
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    UMRIC provides workflow, tracking, and administrative tools;
                    it does not replace official academic or publication
                    decisions made by the institution. Features, workflow
                    stages, and policies may be updated. Material changes to
                    these Terms will be reflected on this page with an updated
                    &ldquo;Last updated&rdquo; date.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    6. Disclaimers and limitation of liability
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    The Service is provided on an as-available basis for
                    academic and administrative use. To the maximum extent
                    permitted by law, the institution and its operators are not
                    liable for indirect or consequential damages arising from
                    use of the Service, or for third-party service
                    interruptions.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    7. Contact
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    For questions about these Terms, contact your program office
                    or institutional IT/administration through the official
                    channels provided by UM Digos College.
                </p>
            </section>
        </article>

        <section
            class="mt-8 flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-300"
        >
            <span>Related:</span>
            <Link
                :href="privacy.url()"
                class="font-semibold text-orange-600 underline decoration-dotted dark:text-orange-400"
            >
                Privacy Policy
            </Link>
            <span class="text-slate-400">·</span>
            <Link
                :href="documentation.url()"
                class="font-semibold text-orange-600 underline decoration-dotted dark:text-orange-400"
            >
                Documentation
            </Link>
            <span class="text-slate-400">·</span>
            <Link
                :href="faq.url()"
                class="font-semibold text-orange-600 underline decoration-dotted dark:text-orange-400"
            >
                FAQ
            </Link>
        </section>
    </main>
</template>
