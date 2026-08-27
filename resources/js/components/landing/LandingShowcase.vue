<script setup lang="ts">
import { computed } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { useScrollReveal } from '@/composables/useScrollReveal';

const branding = useBranding();
const { target: sectionRef, isVisible } = useScrollReveal(0.1);
const previewTitle = computed(() => `${branding.value.name} Platform Preview`);
</script>

<template>
    <section
        id="showcase"
        class="scroll-mt-24 overflow-hidden bg-slate-50 px-4 py-20 sm:px-6 sm:py-24 lg:px-8 dark:bg-slate-900/40"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="[
                    'reveal mb-12 max-w-2xl',
                    { visible: isVisible },
                ]"
            >
                <p
                    class="mb-3 text-sm font-semibold tracking-wide text-orange-600 uppercase dark:text-orange-400"
                >
                    Platform preview
                </p>
                <h2
                    class="font-display mb-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                >
                    See the workflow in motion
                </h2>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">
                    A short look at how students and faculty follow active
                    research papers across UM Digos College.
                </p>
            </div>

            <div :class="['reveal-right', { visible: isVisible }]">
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-900/10 dark:border-slate-700/50 dark:shadow-black/40"
                >
                    <div
                        class="flex items-center gap-2 border-b border-slate-200/70 bg-slate-100/90 px-4 py-2.5 dark:border-slate-700/50 dark:bg-slate-900"
                    >
                        <div class="flex gap-1.5" aria-hidden="true">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-slate-600" />
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-slate-600" />
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-slate-600" />
                        </div>
                        <p
                            class="flex-1 text-center font-mono text-[11px] text-slate-500"
                        >
                            Platform walkthrough
                        </p>
                    </div>
                    <div class="relative aspect-video w-full bg-slate-900">
                        <iframe
                            class="absolute inset-0 h-full w-full"
                            src="https://www.youtube.com/embed/o4jIKc_DIoM?rel=0&modestbranding=1"
                            :title="previewTitle"
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
