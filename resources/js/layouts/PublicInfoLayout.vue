<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LandingLockup from '@/components/landing/LandingLockup.vue';
import { documentation, faq, home, login, privacy, terms } from '@/routes';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
</script>

<template>
    <div class="min-h-screen bg-um-wash font-landing text-um-heading">
        <header class="bg-white">
            <div class="bg-um-maroon px-5 py-1.5 text-white">
                <p
                    class="mx-auto max-w-6xl text-[11px] font-semibold tracking-[0.12em] uppercase"
                >
                    UM Digos College · Research and Innovation Center
                </p>
            </div>
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-3 border-b border-black/8 px-5 py-3"
            >
                <Link :href="home.url()" class="min-w-0">
                    <LandingLockup />
                </Link>

                <nav
                    class="flex flex-wrap items-center justify-end gap-1 sm:gap-2"
                >
                    <Link
                        :href="documentation.url()"
                        class="min-h-11 px-2.5 py-2 text-sm font-semibold sm:px-3"
                        :class="
                            currentPath === documentation.url()
                                ? 'text-um-maroon'
                                : 'text-um-heading hover:text-um-maroon'
                        "
                    >
                        Docs
                    </Link>
                    <Link
                        :href="faq.url()"
                        class="min-h-11 px-2.5 py-2 text-sm font-semibold sm:px-3"
                        :class="
                            currentPath === faq.url()
                                ? 'text-um-maroon'
                                : 'text-um-heading hover:text-um-maroon'
                        "
                    >
                        FAQ
                    </Link>
                    <Link
                        :href="terms.url()"
                        title="Terms &amp; Conditions"
                        class="min-h-11 px-2.5 py-2 text-sm font-semibold sm:px-3"
                        :class="
                            currentPath === terms.url()
                                ? 'text-um-maroon'
                                : 'text-um-heading hover:text-um-maroon'
                        "
                    >
                        Terms
                    </Link>
                    <Link
                        :href="privacy.url()"
                        title="Privacy Policy"
                        class="min-h-11 px-2.5 py-2 text-sm font-semibold sm:px-3"
                        :class="
                            currentPath === privacy.url()
                                ? 'text-um-maroon'
                                : 'text-um-heading hover:text-um-maroon'
                        "
                    >
                        Privacy
                    </Link>
                    <Link
                        :href="login.url()"
                        class="min-h-11 rounded-[3px] bg-um-gold px-3.5 py-2 text-sm font-bold text-white transition hover:bg-um-gold-hover"
                    >
                        Sign in
                    </Link>
                </nav>
            </div>
        </header>

        <slot />

        <footer class="bg-um-navy px-5 py-6 text-xs text-white/60">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-3"
            >
                <p>
                    &copy; {{ new Date().getFullYear() }} University of Mindanao
                    Digos College
                </p>
                <div
                    class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1"
                >
                    <Link :href="documentation.url()" class="hover:text-white"
                        >Documentation</Link
                    >
                    <Link :href="faq.url()" class="hover:text-white">FAQ</Link>
                    <Link :href="terms.url()" class="hover:text-white"
                        >Terms</Link
                    >
                    <Link :href="privacy.url()" class="hover:text-white"
                        >Privacy</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
