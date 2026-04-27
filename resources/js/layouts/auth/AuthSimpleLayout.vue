<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useBranding } from '@/composables/useBranding';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const branding = useBranding();
</script>

<template>
    <div class="min-h-svh lg:grid lg:grid-cols-2">
        <!-- Left: branded panel (desktop only) -->
        <div
            class="relative hidden overflow-hidden lg:flex lg:flex-col"
            style="
                background: linear-gradient(
                    145deg,
                    #c2410c 0%,
                    #f97316 45%,
                    #0d9488 100%
                );
            "
        >
            <!-- Decorative rings -->
            <div
                class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full border border-white/10"
            ></div>
            <div
                class="pointer-events-none absolute -top-12 -right-12 h-64 w-64 rounded-full border border-white/10"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-32 -left-32 h-[480px] w-[480px] rounded-full border border-white/10"
            ></div>
            <div
                class="pointer-events-none absolute top-1/2 left-1/2 h-80 w-80 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/5"
            ></div>

            <div class="relative z-10 flex h-full flex-col p-12">
                <!-- Logo -->
                <Link :href="home()" class="group flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 ring-1 ring-white/30 transition-colors group-hover:bg-white/30"
                    >
                        <AppLogoIcon class="size-5 text-white" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">{{
                        branding.name
                    }}</span>
                </Link>

                <!-- Center content -->
                <div class="flex flex-1 flex-col justify-center">
                    <p
                        class="mb-3 text-sm font-semibold tracking-widest text-white/60 uppercase"
                    >
                        University of Mindanao
                    </p>
                    <h1 class="text-4xl leading-tight font-bold text-white">
                        Research made<br />transparent.
                    </h1>
                    <p class="mt-4 text-base leading-relaxed text-white/70">
                        Track, manage, and collaborate on research papers — from
                        title proposal to final defense.
                    </p>

                    <!-- Feature list -->
                    <div class="mt-10 flex flex-col gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/20"
                            >
                                <svg
                                    class="h-4 w-4 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm text-white/80"
                                >Real-time tracking with public QR codes</span
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/20"
                            >
                                <svg
                                    class="h-4 w-4 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm text-white/80"
                                >Collaborate with advisers and panel
                                members</span
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/20"
                            >
                                <svg
                                    class="h-4 w-4 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm text-white/80"
                                >Defense calendar and scheduling</span
                            >
                        </div>
                    </div>
                </div>

                <p class="text-xs text-white/40">
                    © 2026 University of Mindanao Research and Innovation Center
                </p>
            </div>
        </div>

        <!-- Right: form panel -->
        <div
            class="flex min-h-svh flex-col items-center justify-center bg-gray-50 px-6 py-12 lg:min-h-0 lg:px-10 dark:bg-gray-950"
        >
            <!-- Mobile logo -->
            <Link
                :href="home()"
                class="mb-8 flex items-center gap-2.5 lg:hidden"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl"
                    style="
                        background: linear-gradient(135deg, #f97316, #0d9488);
                    "
                >
                    <AppLogoIcon class="size-4 text-white" />
                </div>
                <span class="text-lg font-bold text-gray-900 dark:text-white">{{
                    branding.name
                }}</span>
            </Link>

            <div class="w-full max-w-sm">
                <!-- Desktop back link -->
                <div class="mb-5 hidden justify-end lg:flex">
                    <Link
                        :href="home()"
                        class="text-sm text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                    >
                        ← Back to home
                    </Link>
                </div>

                <!-- Card -->
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
