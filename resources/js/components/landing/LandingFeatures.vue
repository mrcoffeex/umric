<script setup lang="ts">
import {
    ClipboardList,
    Users,
    FolderOpen,
    CalendarCheck,
    GraduationCap,
    ShieldCheck,
    Lock,
} from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: titleRef, isVisible: titleVisible } = useScrollReveal(0.1);

const features = [
    {
        icon: ClipboardList,
        title: 'Title Proposal Tracking',
        description:
            'Submit research title proposals and monitor their approval status through the department and adviser review pipeline.',
        color: 'from-orange-500 to-orange-600',
        glow: 'group-hover:shadow-orange-500/25',
        border: 'group-hover:border-orange-200 dark:group-hover:border-orange-800/60',
    },
    {
        icon: GraduationCap,
        title: 'Stage-by-Stage Progress',
        description:
            'From title approval and chapter submissions to panel review, oral defense, and institutional publication — every milestone is visible.',
        color: 'from-teal-500 to-teal-600',
        glow: 'group-hover:shadow-teal-500/25',
        border: 'group-hover:border-teal-200 dark:group-hover:border-teal-800/60',
    },
    {
        icon: Users,
        title: 'Adviser & Panel Management',
        description:
            'Assign research advisers, add panel members, and coordinate roles across all stages of the research process.',
        color: 'from-orange-400 to-rose-500',
        glow: 'group-hover:shadow-orange-500/25',
        border: 'group-hover:border-orange-200 dark:group-hover:border-orange-800/60',
    },
    {
        icon: FolderOpen,
        title: 'Chapter & Document Uploads',
        description:
            'Submit chapters, full manuscripts, and revision documents. Maintain a complete and organized archive of all research files.',
        color: 'from-teal-400 to-cyan-500',
        glow: 'group-hover:shadow-teal-500/25',
        border: 'group-hover:border-teal-200 dark:group-hover:border-teal-800/60',
    },
    {
        icon: CalendarCheck,
        title: 'Defense Scheduling',
        description:
            'Coordinate oral and final defense schedules with panel members and receive automated notifications for upcoming deadlines.',
        color: 'from-orange-500 to-amber-500',
        glow: 'group-hover:shadow-orange-500/25',
        border: 'group-hover:border-orange-200 dark:group-hover:border-orange-800/60',
    },
    {
        icon: ShieldCheck,
        title: 'Secure Institutional Records',
        description:
            'All research records are securely stored. Role-based access ensures students, advisers, and administrators see only what they need.',
        color: 'from-teal-500 to-emerald-500',
        glow: 'group-hover:shadow-teal-500/25',
        border: 'group-hover:border-teal-200 dark:group-hover:border-teal-800/60',
    },
];

// Individual card reveal refs
const cardRefs = features.map(() => useScrollReveal(0.05));
</script>

<template>
    <section id="features" class="relative overflow-hidden px-5 py-28">
        <!-- Subtle grid bg -->
        <div
            class="absolute inset-0 -z-10 opacity-[0.04] dark:opacity-[0.03]"
            style="
                background-image:
                    linear-gradient(
                        rgba(249, 115, 22, 0.8) 1px,
                        transparent 1px
                    ),
                    linear-gradient(
                        90deg,
                        rgba(249, 115, 22, 0.8) 1px,
                        transparent 1px
                    );
                background-size: 60px 60px;
            "
        />

        <div class="mx-auto max-w-7xl">
            <!-- Section header -->
            <div
                ref="titleRef"
                :class="['reveal mb-20 text-center', { visible: titleVisible }]"
            >
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-teal-200/60 bg-teal-100 px-4 py-1.5 text-sm font-semibold text-teal-700 dark:border-teal-800/40 dark:bg-teal-950/50 dark:text-teal-400"
                >
                    <Lock class="h-3.5 w-3.5" />
                    Designed for UMDC research
                </div>
                <h2 class="mb-5 text-4xl font-black tracking-tight sm:text-5xl">
                    <span class="text-slate-900 dark:text-white"
                        >Built for</span
                    >
                    <span class="text-gradient"> every research stage</span>
                </h2>
                <p
                    class="mx-auto max-w-2xl text-lg leading-relaxed text-slate-500 dark:text-slate-400"
                >
                    Every feature maps directly to the UM Digos College research
                    paper workflow — from an initial title idea to an officially
                    published paper.
                </p>
            </div>

            <!-- Feature cards grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(feature, i) in features"
                    :key="feature.title"
                    :ref="
                        (el) => {
                            if (el)
                                cardRefs[i].target.value = el as HTMLElement;
                        }
                    "
                    :class="[
                        'reveal',
                        { visible: cardRefs[i].isVisible.value },
                    ]"
                    :style="{ transitionDelay: `${i * 80}ms` }"
                >
                    <div
                        :class="[
                            'group h-full cursor-default rounded-2xl border p-7 transition-all duration-300',
                            'bg-white dark:bg-slate-900',
                            'border-slate-200/80 dark:border-slate-800/80',
                            feature.border,
                            'hover:shadow-xl',
                            feature.glow,
                            'hover:-translate-y-1',
                        ]"
                    >
                        <!-- Icon -->
                        <div
                            :class="[
                                'mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br shadow-md transition-transform duration-300 group-hover:scale-110',
                                feature.color,
                            ]"
                        >
                            <component
                                :is="feature.icon"
                                class="h-6 w-6 text-white"
                            />
                        </div>

                        <!-- Text -->
                        <h3
                            class="mb-2.5 text-lg font-bold text-slate-900 dark:text-slate-100"
                        >
                            {{ feature.title }}
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-slate-500 dark:text-slate-400"
                        >
                            {{ feature.description }}
                        </p>

                        <!-- Arrow (on hover) -->
                        <div
                            class="mt-5 flex -translate-x-2 items-center gap-1.5 text-xs font-semibold text-orange-500 opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100"
                        >
                            Learn more <span>→</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
