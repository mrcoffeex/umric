<script setup lang="ts">
import {
    ClipboardList,
    Users,
    FolderOpen,
    CalendarCheck,
    GraduationCap,
    ShieldCheck,
} from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: titleRef, isVisible: titleVisible } = useScrollReveal(0.1);

const features = [
    {
        icon: ClipboardList,
        title: 'Title proposal tracking',
        description:
            'Submit research titles and follow approval through department and adviser review.',
    },
    {
        icon: GraduationCap,
        title: 'Stage-by-stage progress',
        description:
            'Chapters, panel review, defense, and publication — every milestone stays visible.',
    },
    {
        icon: Users,
        title: 'Adviser & panel roles',
        description:
            'Assign advisers and panel members so the right people see the right work.',
    },
    {
        icon: FolderOpen,
        title: 'Document archive',
        description:
            'Upload chapters, manuscripts, and revisions in one organized research record.',
    },
    {
        icon: CalendarCheck,
        title: 'Defense scheduling',
        description:
            'Coordinate oral and final defense dates with clear notifications for the team.',
    },
    {
        icon: ShieldCheck,
        title: 'Role-based access',
        description:
            'Students, advisers, and administrators only see what their role requires.',
    },
];

const cardRefs = features.map(() => useScrollReveal(0.05));
</script>

<template>
    <section
        id="features"
        class="scroll-mt-24 px-4 py-20 sm:px-6 sm:py-24 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="titleRef"
                :class="['reveal mb-14 max-w-2xl', { visible: titleVisible }]"
            >
                <p
                    class="mb-3 text-sm font-semibold tracking-wide text-teal-700 uppercase dark:text-teal-400"
                >
                    Capabilities
                </p>
                <h2
                    class="font-display mb-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                >
                    Built for every research stage
                </h2>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">
                    Features map to the UM Digos College research workflow —
                    from the first title idea to institutional publication.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-x-10 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(feature, i) in features"
                    :key="feature.title"
                    :ref="
                        (el) => {
                            if (el)
                                cardRefs[i].target.value = el as HTMLElement;
                        }
                    "
                    :class="['reveal', { visible: cardRefs[i].isVisible.value }]"
                    :style="{ transitionDelay: `${i * 60}ms` }"
                >
                    <div
                        class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-orange-500/10 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400"
                    >
                        <component :is="feature.icon" class="h-5 w-5" />
                    </div>
                    <h3
                        class="font-display mb-2 text-lg font-bold text-slate-900 dark:text-slate-100"
                    >
                        {{ feature.title }}
                    </h3>
                    <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
