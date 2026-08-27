<script setup lang="ts">
import { BookOpen, GraduationCap, Landmark, Users } from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const audiences = [
    {
        icon: GraduationCap,
        title: 'Student researchers',
        description:
            'Submit a title, upload chapters, prepare for defense, and follow the paper with a public tracking ID.',
    },
    {
        icon: Users,
        title: 'Faculty advisers and panel',
        description:
            'Review submissions, record comments, and coordinate defense schedules without scattered email threads.',
    },
    {
        icon: Landmark,
        title: 'Research office',
        description:
            'Keep campus research records in one place — from proposal through institutional publication.',
    },
];

const programs = [
    'Information Technology',
    'College of Education',
    'Business Administration',
    'College of Nursing',
    'Criminal Justice',
    'Hospitality Management',
];
</script>

<template>
    <section
        id="who-its-for"
        class="scroll-mt-28 bg-um-wash px-4 py-20 sm:px-6 sm:py-24"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="['reveal mb-14 text-center', { visible: isVisible }]"
            >
                <p
                    class="mb-3 text-[11px] font-bold tracking-[0.18em] text-um-maroon uppercase"
                >
                    Who the office serves
                </p>
                <h2
                    class="mb-4 font-display text-3xl font-extrabold tracking-tight text-um-heading sm:text-4xl"
                >
                    A campus research office, not a product pitch
                </h2>
                <p
                    class="mx-auto max-w-2xl text-base leading-relaxed text-um-body"
                >
                    UMRIC is the University of Mindanao Research and Innovation
                    Center at UM Digos College. It supports the people already
                    doing the work.
                </p>
            </div>

            <div
                :class="[
                    'reveal grid grid-cols-1 gap-5 md:grid-cols-3',
                    { visible: isVisible },
                ]"
            >
                <article
                    v-for="item in audiences"
                    :key="item.title"
                    class="border border-t-4 border-black/8 border-t-um-maroon bg-white p-6 shadow-sm"
                >
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center bg-um-maroon text-white"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                    </div>
                    <h3
                        class="mb-2 font-display text-lg font-bold text-um-heading"
                    >
                        {{ item.title }}
                    </h3>
                    <p class="text-sm leading-relaxed text-um-body">
                        {{ item.description }}
                    </p>
                </article>
            </div>

            <div :class="['reveal mt-12', { visible: isVisible }]">
                <p
                    class="mb-4 flex items-center justify-center gap-2 text-center text-[11px] font-bold tracking-[0.16em] text-um-maroon uppercase"
                >
                    <BookOpen class="h-3.5 w-3.5" />
                    Serving UM Digos College programs
                </p>
                <ul class="flex flex-wrap justify-center gap-2">
                    <li
                        v-for="program in programs"
                        :key="program"
                        class="border border-black/8 bg-white px-3 py-2 text-sm font-semibold text-um-heading"
                    >
                        {{ program }}
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
