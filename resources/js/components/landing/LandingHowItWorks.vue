<script setup lang="ts">
import { UserPlus, FileUp, BarChart3 } from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const steps = [
    {
        number: '01',
        icon: UserPlus,
        title: 'Register & submit a title',
        description:
            'Create your student account, file the title proposal, and notify your adviser automatically.',
        points: [
            'Student account setup',
            'Title proposal form',
            'Adviser notification',
        ],
    },
    {
        number: '02',
        icon: FileUp,
        title: 'Upload chapters & get feedback',
        description:
            'Submit work stage by stage, collect panel comments, and keep revisions in one place.',
        points: [
            'Chapter uploads',
            'Panel feedback',
            'Pre-defense clearance',
        ],
    },
    {
        number: '03',
        icon: BarChart3,
        title: 'Defend & publish',
        description:
            'Schedule defense, submit the final manuscript, and complete publication to the college repository.',
        points: [
            'Defense scheduling',
            'Final manuscript',
            'Repository publication',
        ],
    },
];

const stepRefs = steps.map(() => useScrollReveal(0.05));
</script>

<template>
    <section
        id="how-it-works"
        class="scroll-mt-24 px-4 py-20 sm:px-6 sm:py-24 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="['reveal mb-14 max-w-2xl', { visible: isVisible }]"
            >
                <p
                    class="mb-3 text-sm font-semibold tracking-wide text-orange-600 uppercase dark:text-orange-400"
                >
                    The research lifecycle
                </p>
                <h2
                    class="font-display mb-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                >
                    From idea to publication
                </h2>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">
                    Three clear stages guide every student from title proposal
                    to a published research paper.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-3 lg:gap-10">
                <div
                    v-for="(step, i) in steps"
                    :key="step.number"
                    :ref="
                        (el) => {
                            if (el)
                                stepRefs[i].target.value = el as HTMLElement;
                        }
                    "
                    :class="['reveal', { visible: stepRefs[i].isVisible.value }]"
                    :style="{ transitionDelay: `${i * 100}ms` }"
                >
                    <div class="mb-5 flex items-center gap-4">
                        <span
                            class="font-display text-4xl font-extrabold text-orange-500/90 tabular-nums"
                        >
                            {{ step.number }}
                        </span>
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-500/10 text-teal-700 dark:text-teal-400"
                        >
                            <component :is="step.icon" class="h-5 w-5" />
                        </span>
                    </div>
                    <h3
                        class="font-display mb-3 text-xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        {{ step.title }}
                    </h3>
                    <p class="mb-5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ step.description }}
                    </p>
                    <ul class="space-y-2 border-t border-slate-200/80 pt-5 dark:border-slate-800">
                        <li
                            v-for="point in step.points"
                            :key="point"
                            class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300"
                        >
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full bg-teal-500"
                            />
                            {{ point }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>
