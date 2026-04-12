<script setup lang="ts">
import { UserPlus, FileUp, BarChart3, CheckCircle2 } from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const steps = [
    {
        number: '01',
        icon: UserPlus,
        title: 'Register & Submit Title Proposal',
        description:
            'Sign up with your student account, complete your research title form, and get your assigned adviser notified automatically.',
        color: 'from-orange-500 to-orange-600',
        shadow: 'shadow-orange-500/30',
        highlights: [
            'Student account setup',
            'Title proposal form',
            'Adviser auto-assignment',
        ],
    },
    {
        number: '02',
        icon: FileUp,
        title: 'Complete Chapters & Pass Panel Review',
        description:
            'Upload your chapters and revisions stage by stage. Receive feedback from your panel and track all comments in one place.',
        color: 'from-teal-500 to-teal-600',
        shadow: 'shadow-teal-500/30',
        highlights: [
            'Chapter-by-chapter upload',
            'Panel feedback tracking',
            'Pre-defense clearance',
        ],
    },
    {
        number: '03',
        icon: BarChart3,
        title: 'Defend & Publish',
        description:
            'Schedule your oral defense, submit your final manuscript, and complete the publication pipeline to the college research repository.',
        color: 'from-orange-400 to-teal-500',
        shadow: 'shadow-orange-500/20',
        highlights: [
            'Defense scheduling',
            'Final manuscript upload',
            'Repository publication',
        ],
    },
];
</script>

<template>
    <section id="how-it-works" class="relative overflow-hidden px-5 py-28">
        <!-- Background -->
        <div class="absolute inset-0 -z-10 bg-slate-50 dark:bg-slate-950" />
        <div
            class="absolute right-0 bottom-0 left-0 h-px bg-gradient-to-r from-transparent via-orange-300/40 to-transparent dark:via-orange-700/30"
        />

        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['reveal mb-20 text-center', { visible: isVisible }]"
            >
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-orange-200/60 bg-orange-100 px-4 py-1.5 text-sm font-semibold text-orange-700 dark:border-orange-800/40 dark:bg-orange-950/50 dark:text-orange-400"
                >
                    <CheckCircle2 class="h-3.5 w-3.5" />
                    The research lifecycle
                </div>
                <h2 class="mb-5 text-4xl font-black tracking-tight sm:text-5xl">
                    <span class="text-slate-900 dark:text-white"
                        >From idea to</span
                    >
                    <span class="text-gradient"> publication</span>
                </h2>
                <p
                    class="mx-auto max-w-2xl text-lg text-slate-500 dark:text-slate-400"
                >
                    Three clear stages guide every UM Digos College student from
                    their first title proposal to a published research paper.
                </p>
            </div>

            <!-- Steps -->
            <div class="relative grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Connector line (desktop): runs from badge-1 center to badge-3 center -->
                <!-- Badge center Y = card_top(0) - top-4(16px) + h-8/2(16px) = 0 = top:0 of grid -->
                <!-- Badge center X = left-8(32px) + w-8/2(16px) = 48px from each col left edge -->
                <!-- right = col_width - 48px = calc((100% - 4rem)/3 - 48px) -->
                <div
                    class="absolute hidden h-0.5 bg-gradient-to-r from-orange-400 via-teal-400 to-orange-400/50 lg:block dark:from-orange-600/60 dark:via-teal-600/60 dark:to-orange-600/30"
                    style="
                        top: 0;
                        left: 48px;
                        right: calc((100% - 4rem) / 3 - 48px);
                    "
                />

                <div
                    v-for="(step, i) in steps"
                    :key="step.number"
                    :class="['reveal', { visible: isVisible }]"
                    :style="{ transitionDelay: `${i * 150}ms` }"
                >
                    <div
                        class="relative h-full rounded-2xl border border-slate-200/80 bg-white p-8 shadow-sm transition-shadow duration-300 hover:shadow-xl dark:border-slate-800/80 dark:bg-slate-900"
                    >
                        <!-- Step number badge -->
                        <div
                            :class="[
                                'absolute -top-4 left-8 z-20 flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br text-xs font-black text-white shadow-lg',
                                step.color,
                                step.shadow,
                            ]"
                        >
                            {{ step.number }}
                        </div>

                        <!-- Icon -->
                        <div
                            :class="[
                                'mt-2 mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br shadow-md',
                                step.color,
                            ]"
                        >
                            <component
                                :is="step.icon"
                                class="h-7 w-7 text-white"
                            />
                        </div>

                        <!-- Text -->
                        <h3
                            class="mb-3 text-xl font-bold text-slate-900 dark:text-slate-100"
                        >
                            {{ step.title }}
                        </h3>
                        <p
                            class="mb-5 leading-relaxed text-slate-500 dark:text-slate-400"
                        >
                            {{ step.description }}
                        </p>

                        <!-- Highlights -->
                        <ul class="space-y-2">
                            <li
                                v-for="h in step.highlights"
                                :key="h"
                                class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400"
                            >
                                <CheckCircle2
                                    class="h-4 w-4 shrink-0 text-teal-500"
                                />
                                {{ h }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
