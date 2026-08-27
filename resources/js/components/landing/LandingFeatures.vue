<script setup lang="ts">
import {
    CalendarCheck,
    ClipboardList,
    FolderOpen,
    GraduationCap,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: titleRef, isVisible: titleVisible } = useScrollReveal(0.1);

const features = [
    {
        icon: ClipboardList,
        title: 'Title proposal tracking',
        description:
            'Submit research title proposals and monitor approval through department and adviser review.',
    },
    {
        icon: GraduationCap,
        title: 'Stage-by-stage progress',
        description:
            'From title approval and chapter submissions to panel review, oral defense, and publication — every milestone is visible.',
    },
    {
        icon: Users,
        title: 'Adviser and panel management',
        description:
            'Assign research advisers, add panel members, and coordinate roles across the research process.',
    },
    {
        icon: FolderOpen,
        title: 'Chapter and document uploads',
        description:
            'Submit chapters, manuscripts, and revisions. Keep a complete archive of research files for the office.',
    },
    {
        icon: CalendarCheck,
        title: 'Defense scheduling',
        description:
            'Coordinate oral and final defense schedules with panel members and stay current on upcoming dates.',
    },
    {
        icon: ShieldCheck,
        title: 'Secure institutional records',
        description:
            'Role-based access keeps student, adviser, and administrator records limited to what each role needs.',
    },
];

const cardRefs = features.map(() => useScrollReveal(0.05));
</script>

<template>
    <section
        id="features"
        class="scroll-mt-28 bg-white px-4 py-20 sm:px-6 sm:py-24"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="titleRef"
                :class="['reveal mb-14 text-center', { visible: titleVisible }]"
            >
                <p
                    class="mb-3 text-[11px] font-bold tracking-[0.18em] text-um-maroon uppercase"
                >
                    Research process
                </p>
                <h2
                    class="mb-4 font-display text-3xl font-extrabold tracking-tight text-um-heading sm:text-4xl"
                >
                    Built for every research stage
                </h2>
                <p
                    class="mx-auto max-w-2xl text-base leading-relaxed text-um-body"
                >
                    Each tool maps to the UM Digos College research paper
                    workflow — from an initial title to an officially recorded
                    paper.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(feature, i) in features"
                    :key="feature.title"
                    :ref="
                        (el) => {
                            if (el) {
                                cardRefs[i].target.value = el as HTMLElement;
                            }
                        }
                    "
                    :class="[
                        'reveal',
                        { visible: cardRefs[i].isVisible.value },
                    ]"
                >
                    <article
                        class="h-full border border-t-4 border-black/8 border-t-um-maroon bg-white p-6 shadow-sm"
                    >
                        <div
                            class="mb-4 flex h-10 w-10 items-center justify-center bg-um-maroon text-white"
                        >
                            <component :is="feature.icon" class="h-5 w-5" />
                        </div>
                        <h3
                            class="mb-2 font-display text-lg font-bold text-um-heading"
                        >
                            {{ feature.title }}
                        </h3>
                        <p class="text-sm leading-relaxed text-um-body">
                            {{ feature.description }}
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
