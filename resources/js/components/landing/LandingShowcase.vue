<script setup lang="ts">
import { TrendingUp } from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);
</script>

<template>
    <section id="showcase" class="relative overflow-hidden px-5 py-28">
        <!-- Gradient bg slab -->
        <div
            class="absolute inset-0 -z-10 bg-gradient-to-br from-orange-50 via-white to-teal-50/60 dark:from-slate-900 dark:via-slate-950 dark:to-teal-950/20"
        />
        <!-- Decorative circle -->
        <div
            class="animate-spin-slow absolute top-1/4 -right-40 h-[600px] w-[600px] rounded-full bg-gradient-to-br from-teal-400/10 to-orange-400/10 blur-3xl dark:from-teal-500/5 dark:to-orange-500/5"
        />

        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['reveal mb-16 text-center', { visible: isVisible }]"
            >
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-orange-200/60 bg-orange-100 px-4 py-1.5 text-sm font-semibold text-orange-700 dark:border-orange-800/40 dark:bg-orange-950/50 dark:text-orange-400"
                >
                    <TrendingUp class="h-3.5 w-3.5" />
                    Platform Preview
                </div>
                <h2 class="mb-5 text-4xl font-black tracking-tight sm:text-5xl">
                    <span class="text-slate-900 dark:text-white"
                        >See UMRIC</span
                    >
                    <span class="text-gradient-teal"> in action</span>
                </h2>
                <p
                    class="mx-auto max-w-2xl text-lg leading-relaxed text-slate-500 dark:text-slate-400"
                >
                    A dashboard that gives students and faculty full visibility
                    over every active research paper at UM Digos College.
                </p>
            </div>

            <!-- YouTube video embed -->
            <div :class="['reveal-right', { visible: isVisible }]">
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200/60 shadow-2xl shadow-slate-900/15 dark:border-slate-700/40 dark:shadow-black/50"
                >
                    <!-- Browser bar -->
                    <div
                        class="flex items-center gap-3 border-b border-slate-200/60 bg-slate-100 px-4 py-3 dark:border-slate-700/40 dark:bg-slate-900"
                    >
                        <div class="flex gap-1.5">
                            <div class="h-3 w-3 rounded-full bg-red-400/70" />
                            <div class="h-3 w-3 rounded-full bg-yellow-400/70" />
                            <div class="h-3 w-3 rounded-full bg-green-400/70" />
                        </div>
                        <div
                            class="mx-auto max-w-xs flex-1 rounded-lg border border-slate-200/40 bg-white/70 px-3 py-1 text-center font-mono text-xs text-slate-500 dark:border-slate-700/40 dark:bg-slate-800/70 dark:text-slate-500"
                        >
                            youtube.com / watch
                        </div>
                        <div class="w-16" />
                    </div>

                    <!-- 16:9 video wrapper -->
                    <div class="relative w-full" style="padding-bottom: 56.25%">
                        <iframe
                            class="absolute inset-0 h-full w-full"
                            src="https://www.youtube.com/embed/o4jIKc_DIoM?rel=0&modestbranding=1"
                            title="UMRIC Platform Preview"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
