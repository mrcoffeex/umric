<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FlaskConical, Mail } from 'lucide-vue-next';
import { useBranding } from '@/composables/useBranding';
import { documentation, faq, privacy, terms } from '@/routes';

const branding = useBranding();

function scrollTo(id: string) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

const productLinks = [
    { label: 'Features', section: 'features' },
    { label: 'Showcase', section: 'showcase' },
    { label: 'How it works', section: 'how-it-works' },
    { label: 'Who it’s for', section: 'audience' },
    { label: 'Contact', section: 'contact' },
];

const resourceLinks = [
    { label: 'Documentation', route: documentation() },
    { label: 'FAQ', route: faq() },
];

const legalLinks = [
    { label: 'Terms & Conditions', route: terms() },
    { label: 'Privacy Policy', route: privacy() },
    { label: 'Cookies', href: `${privacy.url()}#cookies` },
];
</script>

<template>
    <footer
        class="border-t border-slate-200 bg-slate-100 px-4 pt-14 pb-8 sm:px-6 lg:px-8 dark:border-slate-800 dark:bg-slate-950"
    >
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-12 grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div class="sm:col-span-2 lg:col-span-1">
                    <button
                        type="button"
                        class="group mb-4 flex min-w-0 items-center gap-2"
                        @click="scrollTo('hero')"
                    >
                        <div
                            v-if="branding.logoUrl"
                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted"
                        >
                            <img
                                :src="branding.logoUrl"
                                :alt="branding.name"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <div
                            v-else
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500"
                        >
                            <FlaskConical class="h-4 w-4 text-white" />
                        </div>
                        <span
                            class="font-display truncate text-xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                            >{{ branding.name }}</span
                        >
                    </button>
                    <p
                        class="max-w-xs text-sm leading-relaxed text-slate-600 dark:text-slate-500"
                    >
                        <template v-if="branding.tagline">{{
                            branding.tagline
                        }}</template>
                        <template v-else
                            >Official research paper tracking — from title
                            proposal to publication.</template
                        >
                    </p>
                    <a
                        href="mailto:research@umdigos.edu.ph"
                        class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-orange-300 hover:text-orange-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-orange-600/40 dark:hover:text-orange-400"
                    >
                        <Mail class="h-4 w-4" />
                        research@umdigos.edu.ph
                    </a>
                </div>

                <div>
                    <h4
                        class="mb-4 text-sm font-semibold text-slate-800 dark:text-slate-200"
                    >
                        Product
                    </h4>
                    <ul class="space-y-3">
                        <li v-for="link in productLinks" :key="link.label">
                            <button
                                type="button"
                                class="text-sm text-slate-600 transition hover:text-orange-600 dark:text-slate-500 dark:hover:text-orange-400"
                                @click="scrollTo(link.section)"
                            >
                                {{ link.label }}
                            </button>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4
                        class="mb-4 text-sm font-semibold text-slate-800 dark:text-slate-200"
                    >
                        Resources
                    </h4>
                    <ul class="space-y-3">
                        <li v-for="link in resourceLinks" :key="link.label">
                            <Link
                                :href="link.route.url"
                                class="text-sm text-slate-600 transition hover:text-orange-600 dark:text-slate-500 dark:hover:text-orange-400"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4
                        class="mb-4 text-sm font-semibold text-slate-800 dark:text-slate-200"
                    >
                        Legal
                    </h4>
                    <ul class="space-y-3">
                        <li v-for="link in legalLinks" :key="link.label">
                            <a
                                v-if="'href' in link"
                                :href="link.href"
                                class="text-sm text-slate-600 transition hover:text-orange-600 dark:text-slate-500 dark:hover:text-orange-400"
                            >
                                {{ link.label }}
                            </a>
                            <Link
                                v-else
                                :href="link.route.url"
                                class="text-sm text-slate-600 transition hover:text-orange-600 dark:text-slate-500 dark:hover:text-orange-400"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="flex flex-col items-start justify-between gap-3 border-t border-slate-200 pt-8 sm:flex-row sm:items-center dark:border-slate-800"
            >
                <p class="text-xs text-slate-500">
                    &copy; {{ new Date().getFullYear() }} {{ branding.name }}.
                    All rights reserved.
                </p>
                <p class="text-xs text-slate-500">
                    Built for UM Digos College research
                </p>
            </div>
        </div>
    </footer>
</template>
