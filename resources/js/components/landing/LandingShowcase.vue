<script setup lang="ts">
import { Construction, HardHat } from 'lucide-vue-next';
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

                    <div
                        class="relative flex min-h-[280px] flex-col items-center justify-center overflow-hidden bg-um-wash px-6 py-16 sm:min-h-[360px] sm:py-20"
                        role="img"
                        aria-label="Demo video under construction"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 opacity-[0.07]"
                            aria-hidden="true"
                            style="
                                background-image: repeating-linear-gradient(
                                    -45deg,
                                    #be0002,
                                    #be0002 12px,
                                    transparent 12px,
                                    transparent 24px
                                );
                            "
                        />

                        <div
                            class="relative z-10 flex flex-col items-center text-center"
                        >
                            <div
                                class="relative mb-6 flex h-24 w-24 items-center justify-center sm:h-28 sm:w-28"
                            >
                                <span
                                    class="absolute inset-0 rounded-full bg-um-maroon/10"
                                    aria-hidden="true"
                                />
                                <span
                                    class="absolute inset-2 rounded-full border-2 border-dashed border-um-gold"
                                    aria-hidden="true"
                                />
                                <Construction
                                    class="relative h-11 w-11 text-um-maroon sm:h-12 sm:w-12"
                                    :stroke-width="1.75"
                                    aria-hidden="true"
                                />
                                <HardHat
                                    class="absolute -top-1 -right-1 h-7 w-7 text-um-gold sm:h-8 sm:w-8"
                                    :stroke-width="1.75"
                                    aria-hidden="true"
                                />
                            </div>

                            <p
                                class="mb-2 font-display text-xl font-extrabold tracking-tight text-um-heading sm:text-2xl"
                            >
                                Demo video under construction
                            </p>
                            <p
                                class="max-w-md text-sm leading-relaxed text-um-body sm:text-base"
                            >
                                We're preparing a walkthrough of the research
                                tracking workspace. Check back soon for a full
                                platform overview.
                            </p>
                            <p
                                class="mt-5 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.16em] text-um-maroon uppercase"
                            >
                                <span
                                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-um-gold"
                                    aria-hidden="true"
                                />
                                Coming soon
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
