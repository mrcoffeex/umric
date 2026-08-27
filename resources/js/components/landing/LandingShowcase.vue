<script setup lang="ts">
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);
</script>

<template>
    <section
        id="showcase"
        class="scroll-mt-28 bg-um-wash px-4 py-20 sm:px-6 sm:py-24"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="['reveal mb-12 text-center', { visible: isVisible }]"
            >
                <p
                    class="mb-3 text-[11px] font-bold tracking-[0.18em] text-um-maroon uppercase"
                >
                    Platform overview
                </p>
                <h2
                    class="mb-4 font-display text-3xl font-extrabold tracking-tight text-um-heading sm:text-4xl"
                >
                    See the research office system
                </h2>
                <p
                    class="mx-auto max-w-2xl text-base leading-relaxed text-um-body"
                >
                    A campus dashboard that gives students and faculty
                    visibility over research papers in progress at UM Digos
                    College.
                </p>
            </div>

            <div :class="['reveal', { visible: isVisible }]">
                <div
                    class="overflow-hidden border border-black/8 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center border-b border-black/8 bg-white px-4 py-2.5"
                    >
                        <p
                            class="text-[11px] font-bold tracking-[0.14em] text-um-maroon uppercase"
                        >
                            UMRIC · research tracking
                        </p>
                    </div>
                    <div class="relative w-full" style="padding-bottom: 56.25%">
                        <iframe
                            class="absolute inset-0 h-full w-full"
                            src="https://www.youtube.com/embed/o4jIKc_DIoM?rel=0&modestbranding=1"
                            title="UMRIC platform overview"
                            allow="
                                accelerometer;
                                autoplay;
                                clipboard-write;
                                encrypted-media;
                                gyroscope;
                                picture-in-picture;
                                web-share;
                            "
                            allowfullscreen
                            loading="lazy"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
