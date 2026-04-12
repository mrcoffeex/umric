<script setup lang="ts">
import { ClipboardList, Users, FolderOpen, CalendarCheck, GraduationCap, ShieldCheck, Lock } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

const { target: titleRef, isVisible: titleVisible } = useScrollReveal(0.1)

const features = [
    {
        icon: ClipboardList,
        title: 'Title Proposal Tracking',
        description: 'Submit research title proposals and monitor their approval status through the department and adviser review pipeline.',
        color: 'from-orange-500 to-orange-600',
        glow: 'group-hover:shadow-orange-500/25',
        border: 'group-hover:border-orange-200 dark:group-hover:border-orange-800/60',
    },
    {
        icon: GraduationCap,
        title: 'Stage-by-Stage Progress',
        description: 'From title approval and chapter submissions to panel review, oral defense, and institutional publication — every milestone is visible.',
        color: 'from-teal-500 to-teal-600',
        glow: 'group-hover:shadow-teal-500/25',
        border: 'group-hover:border-teal-200 dark:group-hover:border-teal-800/60',
    },
    {
        icon: Users,
        title: 'Adviser & Panel Management',
        description: 'Assign research advisers, add panel members, and coordinate roles across all stages of the research process.',
        color: 'from-orange-400 to-rose-500',
        glow: 'group-hover:shadow-orange-500/25',
        border: 'group-hover:border-orange-200 dark:group-hover:border-orange-800/60',
    },
    {
        icon: FolderOpen,
        title: 'Chapter & Document Uploads',
        description: 'Submit chapters, full manuscripts, and revision documents. Maintain a complete and organized archive of all research files.',
        color: 'from-teal-400 to-cyan-500',
        glow: 'group-hover:shadow-teal-500/25',
        border: 'group-hover:border-teal-200 dark:group-hover:border-teal-800/60',
    },
    {
        icon: CalendarCheck,
        title: 'Defense Scheduling',
        description: 'Coordinate oral and final defense schedules with panel members and receive automated notifications for upcoming deadlines.',
        color: 'from-orange-500 to-amber-500',
        glow: 'group-hover:shadow-orange-500/25',
        border: 'group-hover:border-orange-200 dark:group-hover:border-orange-800/60',
    },
    {
        icon: ShieldCheck,
        title: 'Secure Institutional Records',
        description: 'All research records are securely stored. Role-based access ensures students, advisers, and administrators see only what they need.',
        color: 'from-teal-500 to-emerald-500',
        glow: 'group-hover:shadow-teal-500/25',
        border: 'group-hover:border-teal-200 dark:group-hover:border-teal-800/60',
    },
]

// Individual card reveal refs
const cardRefs = features.map(() => useScrollReveal(0.05))
</script>

<template>
    <section id="features" class="relative py-28 px-5 overflow-hidden">
        <!-- Subtle grid bg -->
        <div class="absolute inset-0 -z-10 dark:opacity-[0.03] opacity-[0.04]"
            style="background-image: linear-gradient(rgba(249,115,22,0.8) 1px, transparent 1px), linear-gradient(90deg, rgba(249,115,22,0.8) 1px, transparent 1px); background-size: 60px 60px"
        />

        <div class="max-w-7xl mx-auto">
            <!-- Section header -->
            <div
                ref="titleRef"
                :class="['text-center mb-20 reveal', { visible: titleVisible }]"
            >
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold mb-6
                    bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-400
                    border border-teal-200/60 dark:border-teal-800/40">
                    <Lock class="w-3.5 h-3.5" />
                    Designed for UMDC research
                </div>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight mb-5">
                    <span class="text-slate-900 dark:text-white">Built for</span>
                    <span class="text-gradient"> every research stage</span>
                </h2>
                <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    Every feature maps directly to the UM Digos College research paper workflow — from an initial title idea to an officially published paper.
                </p>
            </div>

            <!-- Feature cards grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(feature, i) in features"
                    :key="feature.title"
                    :ref="(el) => { if (el) cardRefs[i].target.value = el as HTMLElement }"
                    :class="[
                        'reveal',
                        { visible: cardRefs[i].isVisible.value }
                    ]"
                    :style="{ transitionDelay: `${i * 80}ms` }"
                >
                    <div
                        :class="[
                            'group h-full p-7 rounded-2xl border transition-all duration-300 cursor-default',
                            'bg-white dark:bg-slate-900',
                            'border-slate-200/80 dark:border-slate-800/80',
                            feature.border,
                            'hover:shadow-xl', feature.glow,
                            'hover:-translate-y-1',
                        ]"
                    >
                        <!-- Icon -->
                        <div :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center mb-5 shadow-md group-hover:scale-110 transition-transform duration-300', feature.color]">
                            <component :is="feature.icon" class="w-6 h-6 text-white" />
                        </div>

                        <!-- Text -->
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2.5">
                            {{ feature.title }}
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            {{ feature.description }}
                        </p>

                        <!-- Arrow (on hover) -->
                        <div class="mt-5 flex items-center gap-1.5 text-xs font-semibold text-orange-500 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                            Learn more <span>→</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
