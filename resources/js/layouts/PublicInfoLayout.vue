<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FlaskConical } from 'lucide-vue-next';
import { computed } from 'vue';
import { documentation, faq, home, login } from '@/routes';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
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
                <Link :href="home.url()" class="inline-flex items-center gap-2">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500"
                    >
                        <FlaskConical class="h-4 w-4 text-white" />
                    </span>
                    <span class="text-lg font-black">UMRIC</span>
                </Link>

                <nav class="flex items-center gap-2">
                    <Link
                        :href="documentation.url()"
                        class="rounded-lg px-3 py-2 text-sm font-semibold transition"
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
                        class="rounded-lg px-3 py-2 text-sm font-semibold transition"
                        :class="
                            currentPath === faq.url()
                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                        "
                    >
                        FAQ
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
                <p>&copy; {{ new Date().getFullYear() }} UMRIC</p>
                <div class="flex items-center gap-4">
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
                </div>
            </div>
        </footer>
    </div>
</template>
