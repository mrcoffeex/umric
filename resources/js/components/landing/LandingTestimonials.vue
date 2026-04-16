<script setup lang="ts">
import {
    Star,
    Quote,
    BookOpen,
    FlaskConical,
    Microscope,
    Atom,
} from 'lucide-vue-next';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const reviews = [
    {
        paperId: 'RP-2026-IT-0047',
        paperTitle:
            'Smart Attendance Monitoring System Using Face Recognition for UMDC',
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
        paperTitle:
            'Effectiveness of Blended Learning in Elementary Schools in Davao del Sur',
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
        paperTitle:
            'Academic Stress and Coping Mechanisms of Nursing Students at UM Digos',
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
];

const cardRefs = reviews.map(() => useScrollReveal(0.05));

const logos = [
    'Information Technology',
    'College of Education',
    'Business Administration',
    'College of Nursing',
    'Engineering',
    'College of Arts',
    'Criminal Justice',
    'Hospitality Management',
];

const logoItems = [...logos, ...logos];
</script>

<template>
    <section id="testimonials" class="relative overflow-hidden px-5 py-28">
        <!-- Background -->
        <div
            class="absolute inset-0 -z-10 bg-gradient-to-b from-white via-orange-50/30 to-white dark:from-slate-950 dark:via-orange-950/10 dark:to-slate-950"
        />

        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['reveal mb-20 text-center', { visible: isVisible }]"
            >
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-teal-200/60 bg-teal-100 px-4 py-1.5 text-sm font-semibold text-teal-700 dark:border-teal-800/40 dark:bg-teal-950/50 dark:text-teal-400"
                >
                    <BookOpen class="h-3.5 w-3.5" />
                    Peer Review Highlights
                </div>
                <h2 class="mb-5 text-4xl font-black tracking-tight sm:text-5xl">
                    <span class="text-slate-900 dark:text-white"
                        >Real research,</span
                    >
                    <span class="text-gradient"> real reviews</span>
                </h2>
                <p
                    class="mx-auto max-w-2xl text-lg text-slate-500 dark:text-slate-400"
                >
                    Panel review highlights from UM Digos College research
                    papers tracked and managed through UMRIC.
                </p>
            </div>

            <!-- Research review cards -->
            <div
                class="mb-20 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div
                    v-for="(r, i) in reviews"
                    :key="r.paperId"
                    :ref="
                        (el) => {
                            if (el)
                                cardRefs[i].target.value = el as HTMLElement;
                        }
                    "
                    :class="['reveal', { visible: cardRefs[i].isVisible.value }]"
                    :style="{ transitionDelay: `${i * 100}ms` }"
                    class="flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-800/80 dark:bg-slate-900"
                >
                    <!-- Paper identity -->
                    <div class="mb-4 flex items-start gap-3">
                        <div
                            :class="[
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br',
                                r.iconBg,
                            ]"
                        >
                            <component
                                :is="r.icon"
                                class="h-4 w-4 text-white"
                            />
                        </div>
                        <div class="min-w-0">
                            <div
                                class="font-mono text-[10px] font-semibold text-orange-500 dark:text-orange-400"
                            >
                                {{ r.paperId }}
                            </div>
                            <div
                                class="mt-0.5 line-clamp-2 text-xs leading-snug font-semibold text-slate-700 dark:text-slate-300"
                            >
                                {{ r.paperTitle }}
                            </div>
                        </div>
                    </div>

                    <!-- Category badge -->
                    <div class="mb-4">
                        <span
                            class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-semibold text-teal-700 dark:bg-teal-950/50 dark:text-teal-400"
                        >
                            {{ r.category }}
                        </span>
                    </div>

                    <!-- Quote icon -->
                    <Quote
                        class="mb-3 h-6 w-6 text-orange-200 dark:text-orange-900"
                    />

                    <!-- Review excerpt -->
                    <p
                        class="mb-5 flex-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        "{{ r.quote }}"
                    </p>

                    <!-- Stars -->
                    <div class="mb-4 flex gap-0.5">
                        <Star
                            v-for="s in r.rating"
                            :key="s"
                            class="h-3.5 w-3.5 fill-orange-400 text-orange-400"
                        />
                    </div>

                    <!-- Reviewer -->
                    <div
                        class="flex items-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-800"
                    >
                        <div
                            :class="[
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-[10px] font-bold text-white',
                                r.avatarBg,
                            ]"
                        >
                            {{ r.avatar }}
                        </div>
                        <div>
                            <div
                                class="text-xs font-semibold text-slate-800 dark:text-slate-200"
                            >
                                {{ r.reviewer }}
                            </div>
                            <div
                                class="text-[10px] text-slate-400 dark:text-slate-500"
                            >
                                {{ r.reviewerRole }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo ticker -->
            <div :class="['reveal', { visible: isVisible }]">
                <p
                    class="mb-8 text-center text-xs font-semibold tracking-widest text-slate-400 uppercase dark:text-slate-600"
                >
                    Used at leading institutions worldwide
                </p>
                <div class="relative overflow-hidden">
                    <!-- Fade masks -->
                    <div
                        class="pointer-events-none absolute top-0 bottom-0 left-0 z-10 w-20 bg-gradient-to-r from-white to-transparent dark:from-slate-950"
                    />
                    <div
                        class="pointer-events-none absolute top-0 right-0 bottom-0 z-10 w-20 bg-gradient-to-l from-white to-transparent dark:from-slate-950"
                    />

                    <div class="group animate-ticker flex w-max gap-10 hover:[animation-play-state:paused]">
                        <div
                            v-for="(logo, i) in logoItems"
                            :key="`${logo}-${i}`"
                            class="flex shrink-0 items-center gap-2 rounded-xl border border-slate-200/60 bg-slate-100/70 px-5 py-2.5 text-sm font-semibold whitespace-nowrap text-slate-500 dark:border-slate-700/40 dark:bg-slate-800/60 dark:text-slate-500"
                        >
                            <div
                                class="h-2 w-2 rounded-full bg-gradient-to-br from-orange-400 to-teal-400"
                            />
                            {{ logo }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
