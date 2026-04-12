<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-gray-50 p-6 dark:bg-gray-950"
    >
        <!-- Subtle decorative blobs -->
        <div
            class="pointer-events-none absolute -top-40 -left-40 h-96 w-96 rounded-full opacity-[0.07]"
            style="
                background: radial-gradient(
                    circle,
                    #f97316 0%,
                    transparent 70%
                );
            "
        ></div>
        <div
            class="pointer-events-none absolute -right-40 -bottom-40 h-96 w-96 rounded-full opacity-[0.07]"
            style="
                background: radial-gradient(
                    circle,
                    #14b8a6 0%,
                    transparent 70%
                );
            "
        ></div>

        <div class="relative z-10 w-full max-w-sm">
            <!-- Top bar: logo + back link -->
            <div class="mb-6 flex items-center justify-between">
                <Link :href="home()" class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg"
                        style="
                            background: linear-gradient(
                                135deg,
                                #f97316,
                                #14b8a6
                            );
                        "
                    >
                        <AppLogoIcon class="size-4 text-white" />
                    </div>
                    <span class="font-semibold text-gray-800 dark:text-gray-100"
                        >UMRIC</span
                    >
                </Link>
                <Link
                    :href="home()"
                    class="text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    ← Home
                </Link>
            </div>

            <!-- Auth card -->
            <div
                class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
