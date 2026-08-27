<script setup lang="ts">
import { GraduationCap, UserCheck, Building2 } from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const roles = [
    {
        icon: GraduationCap,
        title: 'Students',
        description:
            'Submit proposals, upload chapters, follow panel feedback, and know exactly where your paper stands.',
        points: [
            'Clear stage status',
            'Document uploads',
            'Defense schedule visibility',
        ],
    },
    {
        icon: UserCheck,
        title: 'Advisers & panel',
        description:
            'Review submissions, leave structured feedback, and coordinate defense without chasing files over email.',
        points: [
            'Assigned papers in one place',
            'Review & comments',
            'Panel coordination',
        ],
    },
    {
        icon: Building2,
        title: 'Research office',
        description:
            'Oversee department activity, keep institutional records secure, and publish completed work with confidence.',
        points: [
            'Department oversight',
            'Role-based access',
            'Publication pipeline',
        ],
    },
];

const roleRefs = roles.map(() => useScrollReveal(0.05));

const colleges = [
    'Information Technology',
    'College of Education',
    'Business Administration',
    'College of Nursing',
    'Engineering',
    'Arts & Sciences',
    'Criminal Justice',
    'Hospitality Management',
];
</script>

<template>
    <section
        id="audience"
        class="scroll-mt-24 bg-slate-50 px-4 py-20 sm:px-6 sm:py-24 lg:px-8 dark:bg-slate-900/40"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="['reveal mb-14 max-w-2xl', { visible: isVisible }]"
            >
                <p
                    class="mb-3 text-sm font-semibold tracking-wide text-teal-700 uppercase dark:text-teal-400"
                >
                    Who it’s for
                </p>
                <h2
                    class="font-display mb-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                >
                    One platform for the research team
                </h2>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">
                    Built around the people who move research forward at UM
                    Digos College — not generic SaaS personas.
                </p>
            </div>

            <div class="mb-16 grid grid-cols-1 gap-10 md:grid-cols-3">
                <div
                    v-for="(role, i) in roles"
                    :key="role.title"
                    :ref="
                        (el) => {
                            if (el)
                                roleRefs[i].target.value = el as HTMLElement;
                        }
                    "
                    :class="['reveal', { visible: roleRefs[i].isVisible.value }]"
                    :style="{ transitionDelay: `${i * 80}ms` }"
                >
                    <div
                        class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-white"
                    >
                        <component :is="role.icon" class="h-6 w-6" />
                    </div>
                    <h3
                        class="font-display mb-2 text-xl font-bold text-slate-900 dark:text-white"
                    >
                        {{ role.title }}
                    </h3>
                    <p class="mb-5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ role.description }}
                    </p>
                    <ul class="space-y-2">
                        <li
                            v-for="point in role.points"
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

            <div :class="['reveal', { visible: isVisible }]">
                <p
                    class="mb-5 text-xs font-semibold tracking-widest text-slate-500 uppercase"
                >
                    Colleges & programs at UM Digos College
                </p>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="college in colleges"
                        :key="college"
                        class="rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-sm font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400"
                    >
                        {{ college }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>
