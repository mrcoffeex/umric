<script setup lang="ts">
import { BarChart3, CheckCircle2, FileUp, UserPlus } from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const steps = [
    {
        number: '01',
        icon: UserPlus,
        title: 'Register and submit a title proposal',
        description:
            'Create a student account, complete the title form, and notify the assigned adviser.',
        highlights: [
            'Student account setup',
            'Title proposal form',
            'Adviser notification',
        ],
    },
    {
        number: '02',
        icon: FileUp,
        title: 'Complete chapters and panel review',
        description:
            'Upload chapters and revisions stage by stage. Keep panel comments in one place.',
        highlights: [
            'Chapter-by-chapter upload',
            'Panel feedback tracking',
            'Pre-defense clearance',
        ],
    },
    {
        number: '03',
        icon: BarChart3,
        title: 'Defend and publish',
        description:
            'Schedule oral defense, submit the final manuscript, and record the paper with the college research office.',
        highlights: [
            'Defense scheduling',
            'Final manuscript upload',
            'Repository publication',
        ],
    },
];

const stepRefs = steps.map(() => useScrollReveal(0.05));
</script>

<template>
    <section
        id="how-it-works"
        class="scroll-mt-28 bg-white px-4 py-20 sm:px-6 sm:py-24"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="['reveal mb-14 text-center', { visible: isVisible }]"
            >
                <p
                    class="mb-3 text-[11px] font-bold tracking-[0.18em] text-um-maroon uppercase"
                >
                    The research lifecycle
                </p>
                <h2
                    class="mb-4 font-display text-3xl font-extrabold tracking-tight text-um-heading sm:text-4xl"
                >
                    From idea to publication
                </h2>
                <p
                    class="mx-auto max-w-2xl text-base leading-relaxed text-um-body"
                >
                    Three stages guide UM Digos College students from a first
                    title proposal to a recorded research paper.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    v-for="(step, i) in steps"
                    :key="step.number"
                    :ref="
                        (el) => {
                            if (el) {
                                stepRefs[i].target.value = el as HTMLElement;
                            }
                        }
                    "
                    :class="[
                        'reveal',
                        { visible: stepRefs[i].isVisible.value },
                    ]"
                >
                    <article
                        class="relative h-full border border-t-4 border-black/8 border-t-um-maroon bg-white p-7 shadow-sm"
                    >
                        <div
                            class="mb-5 flex items-center justify-between gap-3"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center bg-um-maroon text-white"
                            >
                                <component :is="step.icon" class="h-6 w-6" />
                            </div>
                            <span
                                class="font-display text-2xl font-extrabold text-um-gold"
                            >
                                {{ step.number }}
                            </span>
                        </div>
                        <h3
                            class="mb-3 font-display text-xl font-bold text-um-heading"
                        >
                            {{ step.title }}
                        </h3>
                        <p class="mb-5 leading-relaxed text-um-body">
                            {{ step.description }}
                        </p>
                        <ul class="space-y-2">
                            <li
                                v-for="item in step.highlights"
                                :key="item"
                                class="flex items-center gap-2 text-sm text-um-heading"
                            >
                                <CheckCircle2
                                    class="h-4 w-4 shrink-0 text-um-gold"
                                />
                                {{ item }}
                            </li>
                        </ul>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
