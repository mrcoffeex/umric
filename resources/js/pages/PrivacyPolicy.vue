<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Shield } from 'lucide-vue-next';
import { documentation, faq, terms } from '@/routes';

const lastUpdated = 'April 27, 2026';
</script>

<template>
    <Head title="Privacy Policy" />

    <main class="mx-auto max-w-3xl px-5 py-10">
        <section
            class="rounded-2xl border border-slate-200 bg-slate-50 p-7 dark:border-slate-800 dark:bg-slate-900/60"
        >
            <div
                class="inline-flex items-center gap-2 rounded-full bg-teal-100 px-3 py-1 text-xs font-bold tracking-wide text-teal-800 dark:bg-teal-900/30 dark:text-teal-300"
            >
                <Shield class="h-3.5 w-3.5" />
                Privacy
            </div>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                Privacy Policy
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
                    1. Scope
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    This Privacy Policy explains how UMRIC collects, uses,
                    stores, and protects personal information in connection with
                    the research workflow platform operated for UM Digos
                    College. It applies to students, faculty, staff, and
                    administrators who use the Service.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    2. Data we process
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    Depending on your role, UMRIC may process:
                </p>
                <ul
                    class="mt-2 list-disc space-y-1.5 pl-5 text-slate-600 dark:text-slate-300"
                >
                    <li>
                        <span class="font-semibold">Account data:</span> name,
                        email address, institutional identifiers, and role (e.g.
                        student, faculty, staff, admin) used for sign-in,
                        approval workflows, and access control.
                    </li>
                    <li>
                        <span class="font-semibold"
                            >Research and class data:</span
                        >
                        paper titles, abstracts, categories, status and workflow
                        history, class enrollments, join codes, comments,
                        defense schedules, and file metadata required for the
                        academic process.
                    </li>
                    <li>
                        <span class="font-semibold"
                            >Security and operations:</span
                        >
                        system and audit logs (such as event type, timestamps,
                        IP address, and device/browser information) to protect
                        accounts and investigate misuse, consistent with the
                        platform&rsquo;s design.
                    </li>
                </ul>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    3. Purposes
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    We use this information to:
                </p>
                <ul
                    class="mt-2 list-disc space-y-1.5 pl-5 text-slate-600 dark:text-slate-300"
                >
                    <li>
                        Run and document the official research-paper workflow.
                    </li>
                    <li>
                        Authenticate users and enforce role-based permissions.
                    </li>
                    <li>
                        Support scheduling, notifications, and administrative
                        reporting required by the institution.
                    </li>
                    <li>
                        Maintain integrity, security, and compliance of the
                        platform.
                    </li>
                </ul>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    4. Legal basis and institutional oversight
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    Processing is carried out in support of educational and
                    legitimate institutional purposes, and in line with
                    applicable data protection rules (including the Data Privacy
                    Act of 2012 in the Philippines, as relevant). The
                    institution is responsible for official policies and
                    requests concerning academic records; use UMRIC in
                    accordance with those policies.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    5. Retention
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    Data is kept for as long as needed for the academic and
                    administrative purposes described here, and as required by
                    institutional and legal retention requirements. Audit logs
                    are retained for security and compliance for a period
                    determined by policy.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    6. Sharing
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    Information is shared within the institution&rsquo;s
                    authorized users (e.g. between students, faculty, and
                    administrators as roles permit). The Service is not a public
                    social network; do not post sensitive data beyond what is
                    required for the workflow. We do not sell personal
                    information. Subprocessors (e.g. hosting) are used only as
                    needed to operate the application and with appropriate
                    safeguards.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    7. Security
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    We apply technical and organizational measures appropriate
                    to the environment (access controls, transport security
                    where configured, logging). You should use strong passwords
                    and report suspected unauthorized access to institutional IT
                    or administration.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    8. Your rights
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    You may have rights to access, correct, or object to certain
                    processing of your personal data under applicable law.
                    Submit requests through your program office or the
                    institution&rsquo;s data protection or IT channels as they
                    define.
                </p>
            </section>

            <section id="cookies">
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    9. Cookies and local storage
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    UMRIC and its hosting stack may use cookies or similar
                    technologies that are necessary for security, sessions, and
                    preferences (for example, staying signed in, theme, or
                    application state). You can control browser cookies through
                    your device settings; disabling essential cookies may affect
                    sign-in or functionality.
                </p>
            </section>

            <section>
                <h2
                    class="scroll-mt-24 text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    10. Changes
                </h2>
                <p class="mt-2 text-slate-600 dark:text-slate-300">
                    We may update this Privacy Policy from time to time. The
                    &ldquo;Last updated&rdquo; date at the top will change when
                    we do. Continued use of the Service after updates
                    constitutes notice of the revised policy, subject to
                    stricter requirements where the law requires more.
                </p>
            </section>
        </article>

        <section
            class="mt-8 flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-300"
        >
            <span>Related:</span>
            <Link
                :href="terms.url()"
                class="font-semibold text-teal-600 underline decoration-dotted dark:text-teal-400"
            >
                Terms &amp; Conditions
            </Link>
            <span class="text-slate-400">·</span>
            <Link
                :href="documentation.url()"
                class="font-semibold text-teal-600 underline decoration-dotted dark:text-teal-400"
            >
                Documentation
            </Link>
            <span class="text-slate-400">·</span>
            <Link
                :href="faq.url()"
                class="font-semibold text-teal-600 underline decoration-dotted dark:text-teal-400"
            >
                FAQ
            </Link>
        </section>
    </main>
</template>
