<script setup lang="ts">
import { useScrollReveal } from '@/composables/useScrollReveal';
import { TrendingUp, FileText, Users, Bell } from 'lucide-vue-next';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const mockPapers = [
    {
        id: 'RP-2026-IT-0047',
        title: 'Smart Attendance System Using Face Recognition for UMDC',
        status: 'Panel Review',
        category: 'Information Technology',
        progress: 70,
    },
    {
        id: 'RP-2026-ED-0021',
        title: 'Effectiveness of Blended Learning in Rural Elementary Schools in Davao del Sur',
        status: 'For Defense',
        category: 'Education',
        progress: 90,
    },
    {
        id: 'RP-2026-BA-0033',
        title: 'Impact of E-Commerce Adoption on MSMEs in Digos City',
        status: 'Published',
        category: 'Business Admin',
        progress: 100,
    },
];

const statusColor: Record<string, string> = {
    'Title Proposal':
        'bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400',
    'Panel Review':
        'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400',
    'For Defense':
        'bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-400',
    Published:
        'bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-400',
};
</script>

<template>
    <section id="showcase" class="relative overflow-hidden px-5 py-28">
        <!-- Gradient bg slab -->
        <div
            class="absolute inset-0 -z-10 bg-gradient-to-br from-orange-50 via-white to-teal-50/60 dark:from-slate-900 dark:via-slate-950 dark:to-teal-950/20"
        />
        <!-- Decorative circle -->
        <div
            class="animate-spin-slow absolute top-1/4 -right-40 h-[600px] w-[600px] rounded-full bg-gradient-to-br from-teal-400/10 to-orange-400/10 blur-3xl dark:from-teal-500/5 dark:to-orange-500/5"
        />

        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['reveal mb-16 text-center', { visible: isVisible }]"
            >
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-orange-200/60 bg-orange-100 px-4 py-1.5 text-sm font-semibold text-orange-700 dark:border-orange-800/40 dark:bg-orange-950/50 dark:text-orange-400"
                >
                    <TrendingUp class="h-3.5 w-3.5" />
                    Platform Preview
                </div>
                <h2 class="mb-5 text-4xl font-black tracking-tight sm:text-5xl">
                    <span class="text-slate-900 dark:text-white"
                        >See UMRIC</span
                    >
                    <span class="text-gradient-teal"> in action</span>
                </h2>
                <p
                    class="mx-auto max-w-2xl text-lg leading-relaxed text-slate-500 dark:text-slate-400"
                >
                    A dashboard that gives students and faculty full visibility
                    over every active research paper at UM Digos College.
                </p>
            </div>

            <!-- Mock dashboard -->
            <div :class="['reveal-right', { visible: isVisible }]">
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200/60 shadow-2xl shadow-slate-900/15 dark:border-slate-700/40 dark:shadow-black/50"
                >
                    <!-- Browser bar -->
                    <div
                        class="flex items-center gap-3 border-b border-slate-200/60 bg-slate-100 px-4 py-3 dark:border-slate-700/40 dark:bg-slate-900"
                    >
                        <div class="flex gap-1.5">
                            <div class="h-3 w-3 rounded-full bg-red-400/70" />
                            <div
                                class="h-3 w-3 rounded-full bg-yellow-400/70"
                            />
                            <div class="h-3 w-3 rounded-full bg-green-400/70" />
                        </div>
                        <div
                            class="mx-auto max-w-xs flex-1 rounded-lg border border-slate-200/40 bg-white/70 px-3 py-1 text-center font-mono text-xs text-slate-500 dark:border-slate-700/40 dark:bg-slate-800/70 dark:text-slate-500"
                        >
                            umric.app / dashboard
                        </div>
                        <div class="w-16" />
                    </div>

                    <!-- Dashboard layout -->
                    <div
                        class="grid min-h-[520px] bg-white md:grid-cols-[220px_1fr] dark:bg-slate-950"
                    >
                        <!-- Sidebar -->
                        <div
                            class="hidden space-y-1 border-r border-slate-100 p-4 md:block dark:border-slate-800"
                        >
                            <div
                                class="flex items-center gap-2.5 rounded-lg bg-orange-50 px-3 py-2 text-sm font-semibold text-orange-600 dark:bg-orange-950/30 dark:text-orange-400"
                            >
                                <TrendingUp class="h-4 w-4" /> Dashboard
                            </div>
                            <div
                                v-for="item in [
                                    'My Papers',
                                    'Collaborations',
                                    'Citations',
                                    'Reports',
                                ]"
                                :key="item"
                                class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-50 dark:text-slate-500 dark:hover:bg-slate-900"
                            >
                                <div
                                    class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-slate-700"
                                />
                                {{ item }}
                            </div>
                            <!-- Divider -->
                            <div
                                class="my-3 border-t border-slate-100 dark:border-slate-800"
                            />
                            <div
                                v-for="item in ['Settings', 'Help']"
                                :key="item"
                                class="cursor-pointer rounded-lg px-3 py-2 text-sm text-slate-400 dark:text-slate-600"
                            >
                                {{ item }}
                            </div>
                        </div>

                        <!-- Main content -->
                        <div class="space-y-5 p-6">
                            <!-- Top metrics -->
                            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                                <div
                                    v-for="m in [
                                        {
                                            label: 'Active Papers',
                                            value: '38',
                                            change: '+5 this term',
                                            icon: FileText,
                                            color: 'text-orange-500',
                                            bg: 'bg-orange-50 dark:bg-orange-950/30',
                                        },
                                        {
                                            label: 'For Defense',
                                            value: '9',
                                            change: '3 this week',
                                            icon: Bell,
                                            color: 'text-amber-500',
                                            bg: 'bg-amber-50 dark:bg-amber-950/30',
                                        },
                                        {
                                            label: 'Published',
                                            value: '22',
                                            change: 'All-time',
                                            icon: TrendingUp,
                                            color: 'text-teal-500',
                                            bg: 'bg-teal-50 dark:bg-teal-950/30',
                                        },
                                        {
                                            label: 'Students',
                                            value: '61',
                                            change: 'This A.Y.',
                                            icon: Users,
                                            color: 'text-blue-500',
                                            bg: 'bg-blue-50 dark:bg-blue-950/30',
                                        },
                                    ]"
                                    :key="m.label"
                                    :class="[
                                        'rounded-xl border border-slate-100 p-4 dark:border-slate-800',
                                        m.bg,
                                    ]"
                                >
                                    <div
                                        class="mb-2 flex items-center justify-between"
                                    >
                                        <span
                                            class="text-xs font-medium text-slate-500 dark:text-slate-500"
                                            >{{ m.label }}</span
                                        >
                                        <component
                                            :is="m.icon"
                                            :class="['h-4 w-4', m.color]"
                                        />
                                    </div>
                                    <div
                                        :class="[
                                            'text-2xl font-black',
                                            m.color,
                                        ]"
                                    >
                                        {{ m.value }}
                                    </div>
                                    <div
                                        class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-600"
                                    >
                                        {{ m.change }}
                                    </div>
                                </div>
                            </div>

                            <!-- Papers table -->
                            <div
                                class="overflow-hidden rounded-xl border border-slate-100 dark:border-slate-800"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900"
                                >
                                    <span
                                        class="text-sm font-semibold text-slate-700 dark:text-slate-300"
                                        >Recent Papers</span
                                    >
                                    <span
                                        class="cursor-pointer text-xs font-medium text-orange-500 hover:underline"
                                        >View all →</span
                                    >
                                </div>
                                <div
                                    class="divide-y divide-slate-100 dark:divide-slate-800"
                                >
                                    <div
                                        v-for="paper in mockPapers"
                                        :key="paper.id"
                                        class="flex cursor-pointer items-center gap-4 px-4 py-3.5 transition hover:bg-slate-50/60 dark:hover:bg-slate-900/60"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200"
                                            >
                                                {{ paper.title }}
                                            </div>
                                            <div
                                                class="mt-0.5 flex items-center gap-2"
                                            >
                                                <span
                                                    class="font-mono text-[10px] text-orange-500 dark:text-orange-400"
                                                    >{{ paper.id }}</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400"
                                                    >·</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-400 dark:text-slate-500"
                                                    >{{ paper.category }}</span
                                                >
                                            </div>
                                        </div>
                                        <!-- Progress bar -->
                                        <div class="hidden w-24 sm:block">
                                            <div
                                                class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                            >
                                                <div
                                                    class="h-full rounded-full bg-gradient-to-r from-orange-400 to-teal-500 transition-all"
                                                    :style="{
                                                        width:
                                                            paper.progress +
                                                            '%',
                                                    }"
                                                />
                                            </div>
                                            <div
                                                class="mt-0.5 text-right text-[9px] text-slate-400"
                                            >
                                                {{ paper.progress }}%
                                            </div>
                                        </div>
                                        <span
                                            :class="[
                                                'shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold',
                                                statusColor[paper.status],
                                            ]"
                                        >
                                            {{ paper.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
