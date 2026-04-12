<script setup lang="ts">
import { Star, Quote, BookOpen, FlaskConical, Microscope, Atom } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

const { target: sectionRef, isVisible } = useScrollReveal(0.1)

const reviews = [
    {
        paperId: 'RP-2026-IT-0047',
        paperTitle: 'Smart Attendance Monitoring System Using Face Recognition for UMDC',
        category: 'Information Technology',
        icon: Atom,
        iconBg: 'from-orange-400 to-orange-600',
        reviewer: 'Dr. Maria Santos',
        reviewerRole: 'Panel Chair, BSIT Department',
        avatar: 'MS',
        avatarBg: 'from-orange-400 to-orange-600',
        rating: 5,
        quote: 'Strong integration of OpenCV and real-time database sync. Accuracy results across the test population are well-documented and statistically sound.',
    },
    {
        paperId: 'RP-2026-ED-0023',
        paperTitle: 'Effectiveness of Blended Learning in Elementary Schools in Davao del Sur',
        category: 'Education',
        icon: FlaskConical,
        iconBg: 'from-teal-400 to-teal-600',
        reviewer: 'Prof. Liza Macaraeg',
        reviewerRole: 'Research Adviser, College of Education',
        avatar: 'LM',
        avatarBg: 'from-teal-400 to-teal-600',
        rating: 5,
        quote: 'The comparative study across six schools demonstrates robust sampling. Findings have direct implications for DepEd policy in rural communities.',
    },
    {
        paperId: 'RP-2026-BA-0015',
        paperTitle: 'Impact of E-Commerce Adoption on MSMEs in Digos City',
        category: 'Business Administration',
        icon: Microscope,
        iconBg: 'from-orange-500 to-teal-400',
        reviewer: 'Dr. Ramon Dela Cruz',
        reviewerRole: 'Panel Member, College of Business',
        avatar: 'RD',
        avatarBg: 'from-orange-500 to-teal-400',
        rating: 5,
        quote: 'Clear research variables and well-validated survey instruments. Recommendations are actionable and directly relevant to local entrepreneurs.',
    },
    {
        paperId: 'RP-2026-NU-0031',
        paperTitle: 'Academic Stress and Coping Mechanisms of Nursing Students at UM Digos',
        category: 'Nursing',
        icon: BookOpen,
        iconBg: 'from-teal-500 to-cyan-500',
        reviewer: 'Prof. Ana Reyes',
        reviewerRole: 'Research Coordinator, College of Nursing',
        avatar: 'AR',
        avatarBg: 'from-teal-500 to-cyan-500',
        rating: 5,
        quote: 'The study addresses a timely concern in nursing education. The mixed-methods approach adds depth and the statistical treatment is appropriate.',
    },
]

const logos = [
    'Information Technology', 'College of Education', 'Business Administration', 'College of Nursing',
    'Engineering', 'College of Arts', 'Criminal Justice', 'Hospitality Management',
]

const logoItems = [...logos, ...logos]
</script>

<template>
    <section id="testimonials" class="py-28 px-5 relative overflow-hidden">
        <!-- Background -->
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-white via-orange-50/30 to-white dark:from-slate-950 dark:via-orange-950/10 dark:to-slate-950" />

        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['text-center mb-20 reveal', { visible: isVisible }]"
            >
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold mb-6
                    bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-400
                    border border-teal-200/60 dark:border-teal-800/40">
                    <BookOpen class="w-3.5 h-3.5" />
                    Peer Review Highlights
                </div>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight mb-5">
                    <span class="text-slate-900 dark:text-white">Real research,</span>
                    <span class="text-gradient"> real reviews</span>
                </h2>
                <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
                    Panel review highlights from UM Digos College research papers tracked and managed through UMRIC.
                </p>
            </div>

            <!-- Research review cards -->
            <div :class="['grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-20 reveal', { visible: isVisible }]">
                <div
                    v-for="(r, i) in reviews"
                    :key="r.paperId"
                    :style="{ transitionDelay: `${i * 100}ms` }"
                    class="flex flex-col p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                >
                    <!-- Paper identity -->
                    <div class="flex items-start gap-3 mb-4">
                        <div :class="['w-9 h-9 rounded-xl bg-gradient-to-br flex items-center justify-center shrink-0', r.iconBg]">
                            <component :is="r.icon" class="w-4 h-4 text-white" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-mono font-semibold text-orange-500 dark:text-orange-400">{{ r.paperId }}</div>
                            <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 leading-snug mt-0.5 line-clamp-2">{{ r.paperTitle }}</div>
                        </div>
                    </div>

                    <!-- Category badge -->
                    <div class="mb-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-400">
                            {{ r.category }}
                        </span>
                    </div>

                    <!-- Quote icon -->
                    <Quote class="w-6 h-6 text-orange-200 dark:text-orange-900 mb-3" />

                    <!-- Review excerpt -->
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-5 flex-1">
                        "{{ r.quote }}"
                    </p>

                    <!-- Stars -->
                    <div class="flex gap-0.5 mb-4">
                        <Star v-for="s in r.rating" :key="s" class="w-3.5 h-3.5 fill-orange-400 text-orange-400" />
                    </div>

                    <!-- Reviewer -->
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div :class="['w-8 h-8 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-[10px] font-bold shrink-0', r.avatarBg]">
                            {{ r.avatar }}
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ r.reviewer }}</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ r.reviewerRole }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo ticker -->
            <div :class="['reveal', { visible: isVisible }]">
                <p class="text-center text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-600 mb-8">
                    Used at leading institutions worldwide
                </p>
                <div class="relative overflow-hidden">
                    <!-- Fade masks -->
                    <div class="absolute left-0 top-0 bottom-0 w-20 bg-gradient-to-r from-white dark:from-slate-950 to-transparent z-10 pointer-events-none" />
                    <div class="absolute right-0 top-0 bottom-0 w-20 bg-gradient-to-l from-white dark:from-slate-950 to-transparent z-10 pointer-events-none" />

                    <div class="flex gap-10 animate-ticker w-max">
                        <div
                            v-for="(logo, i) in logoItems"
                            :key="`${logo}-${i}`"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-100/70 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/40 shrink-0 text-sm font-semibold text-slate-500 dark:text-slate-500 whitespace-nowrap"
                        >
                            <div class="w-2 h-2 rounded-full bg-gradient-to-br from-orange-400 to-teal-400" />
                            {{ logo }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
