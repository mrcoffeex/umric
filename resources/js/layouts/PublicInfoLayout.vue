<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FlaskConical } from 'lucide-vue-next';
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { documentation, faq, home, login, privacy, terms } from '@/routes';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
const branding = useBranding();
</script>

<template>
    <div
        class="min-h-screen bg-white text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100"
    >
        <header
            class="border-b border-slate-200/70 bg-white/90 backdrop-blur dark:border-slate-800/70 dark:bg-slate-950/90"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4"
            >
                <Link
                    :href="home.url()"
                    class="inline-flex min-w-0 items-center gap-2"
                >
                    <span
                        v-if="branding.logoUrl"
                        class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted"
                    >
                        <img
                            :src="branding.logoUrl"
                            :alt="branding.name"
                            class="h-full w-full object-contain"
                        />
                    </span>
                    <span
                        v-else
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500"
                    >
                        <FlaskConical class="h-4 w-4 text-white" />
                    </span>
                    <span class="truncate text-lg font-black">{{
                        branding.name
                    }}</span>
                </Link>

                <nav
                    class="flex flex-wrap items-center justify-end gap-1 sm:gap-2"
                >
                    <Link
                        :href="documentation.url()"
                        class="rounded-lg px-2.5 py-2 text-sm font-semibold transition sm:px-3"
                        :class="
                            currentPath === documentation.url()
                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                        "
                    >
                        Docs
                    </Link>
                    <Link
                        :href="faq.url()"
                        class="rounded-lg px-2.5 py-2 text-sm font-semibold transition sm:px-3"
                        :class="
                            currentPath === faq.url()
                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                        "
                    >
                        FAQ
                    </Link>
                    <Link
                        :href="terms.url()"
                        title="Terms &amp; Conditions"
                        class="rounded-lg px-2.5 py-2 text-sm font-semibold transition sm:px-3"
                        :class="
                            currentPath === terms.url()
                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                        "
                    >
                        Terms
                    </Link>
                    <Link
                        :href="privacy.url()"
                        title="Privacy Policy"
                        class="rounded-lg px-2.5 py-2 text-sm font-semibold transition sm:px-3"
                        :class="
                            currentPath === privacy.url()
                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                        "
                    >
                        Privacy
                    </Link>
                    <Link
                        :href="login.url()"
                        class="rounded-lg bg-orange-500 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-orange-600"
                    >
                        Sign In
                    </Link>
                </nav>
            </div>
        </header>

        <slot />

        <footer
            class="border-t border-slate-200/70 px-5 py-6 text-xs text-slate-500 dark:border-slate-800/70 dark:text-slate-400"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-3"
            >
                <p>&copy; {{ new Date().getFullYear() }} {{ branding.name }}</p>
                <div
                    class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1"
                >
                    <Link
                        :href="documentation.url()"
                        class="hover:text-orange-500 dark:hover:text-orange-400"
                        >Documentation</Link
                    >
                    <Link
                        :href="faq.url()"
                        class="hover:text-orange-500 dark:hover:text-orange-400"
                        >FAQ</Link
                    >
                    <Link
                        :href="terms.url()"
                        class="hover:text-orange-500 dark:hover:text-orange-400"
                        >Terms</Link
                    >
                    <Link
                        :href="privacy.url()"
                        class="hover:text-orange-500 dark:hover:text-orange-400"
                        >Privacy</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
