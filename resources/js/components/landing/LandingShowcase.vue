<script setup lang="ts">
import { useScrollReveal } from '@/composables/useScrollReveal'
import { TrendingUp, FileText, Users, Bell } from 'lucide-vue-next'

const { target: sectionRef, isVisible } = useScrollReveal(0.1)

const mockPapers = [
    { id: 'RP-2026-IT-0047', title: 'Smart Attendance System Using Face Recognition for UMDC', status: 'Panel Review', category: 'Information Technology', progress: 70 },
    { id: 'RP-2026-ED-0021', title: 'Effectiveness of Blended Learning in Rural Elementary Schools in Davao del Sur', status: 'For Defense', category: 'Education', progress: 90 },
    { id: 'RP-2026-BA-0033', title: 'Impact of E-Commerce Adoption on MSMEs in Digos City', status: 'Published', category: 'Business Admin', progress: 100 },
]

const statusColor: Record<string, string> = {
    'Title Proposal': 'bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400',
    'Panel Review':   'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400',
    'For Defense':    'bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-400',
    'Published':      'bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-400',
}
</script>

<template>
    <section id="showcase" class="relative py-28 px-5 overflow-hidden">
        <!-- Gradient bg slab -->
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-orange-50 via-white to-teal-50/60 dark:from-slate-900 dark:via-slate-950 dark:to-teal-950/20" />
        <!-- Decorative circle -->
        <div class="absolute -right-40 top-1/4 w-[600px] h-[600px] rounded-full bg-gradient-to-br from-teal-400/10 to-orange-400/10 blur-3xl dark:from-teal-500/5 dark:to-orange-500/5 animate-spin-slow" />

        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['text-center mb-16 reveal', { visible: isVisible }]"
            >
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold mb-6
                    bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-400
                    border border-orange-200/60 dark:border-orange-800/40">
                    <TrendingUp class="w-3.5 h-3.5" />
                    Platform Preview
                </div>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight mb-5">
                    <span class="text-slate-900 dark:text-white">See UMRIC</span>
                    <span class="text-gradient-teal"> in action</span>
                </h2>
                <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    A dashboard that gives students and faculty full visibility over every active research paper at UM Digos College.
                </p>
            </div>

            <!-- Mock dashboard -->
            <div :class="['reveal-right', { visible: isVisible }]">
                <div class="rounded-2xl overflow-hidden shadow-2xl shadow-slate-900/15 dark:shadow-black/50 border border-slate-200/60 dark:border-slate-700/40">
                    <!-- Browser bar -->
                    <div class="bg-slate-100 dark:bg-slate-900 px-4 py-3 flex items-center gap-3 border-b border-slate-200/60 dark:border-slate-700/40">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-400/70" />
                            <div class="w-3 h-3 rounded-full bg-yellow-400/70" />
                            <div class="w-3 h-3 rounded-full bg-green-400/70" />
                        </div>
                        <div class="flex-1 max-w-xs mx-auto px-3 py-1 rounded-lg bg-white/70 dark:bg-slate-800/70 text-xs text-slate-500 dark:text-slate-500 font-mono text-center border border-slate-200/40 dark:border-slate-700/40">
                            umric.app / dashboard
                        </div>
                        <div class="w-16" />
                    </div>

                    <!-- Dashboard layout -->
                    <div class="grid md:grid-cols-[220px_1fr] bg-white dark:bg-slate-950 min-h-[520px]">
                        <!-- Sidebar -->
                        <div class="hidden md:block border-r border-slate-100 dark:border-slate-800 p-4 space-y-1">
                            <div class="px-3 py-2 rounded-lg bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400 text-sm font-semibold flex items-center gap-2.5">
                                <TrendingUp class="w-4 h-4" /> Dashboard
                            </div>
                            <div v-for="item in ['My Papers', 'Collaborations', 'Citations', 'Reports']" :key="item"
                                class="px-3 py-2 rounded-lg text-slate-500 dark:text-slate-500 text-sm flex items-center gap-2.5 hover:bg-slate-50 dark:hover:bg-slate-900 cursor-pointer transition">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700" />
                                {{ item }}
                            </div>
                            <!-- Divider -->
                            <div class="border-t border-slate-100 dark:border-slate-800 my-3" />
                            <div v-for="item in ['Settings', 'Help']" :key="item"
                                class="px-3 py-2 rounded-lg text-slate-400 dark:text-slate-600 text-sm cursor-pointer">
                                {{ item }}
                            </div>
                        </div>

                        <!-- Main content -->
                        <div class="p-6 space-y-5">
                            <!-- Top metrics -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <div v-for="m in [
                                    { label: 'Active Papers', value: '38', change: '+5 this term', icon: FileText, color: 'text-orange-500', bg: 'bg-orange-50 dark:bg-orange-950/30' },
                                    { label: 'For Defense',   value: '9',  change: '3 this week',  icon: Bell,     color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-950/30' },
                                    { label: 'Published',     value: '22', change: 'All-time',      icon: TrendingUp, color: 'text-teal-500',  bg: 'bg-teal-50 dark:bg-teal-950/30'  },
                                    { label: 'Students',      value: '61', change: 'This A.Y.',     icon: Users,    color: 'text-blue-500',  bg: 'bg-blue-50 dark:bg-blue-950/30'  },
                                ]" :key="m.label" :class="['rounded-xl p-4 border border-slate-100 dark:border-slate-800', m.bg]">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-slate-500 dark:text-slate-500">{{ m.label }}</span>
                                        <component :is="m.icon" :class="['w-4 h-4', m.color]" />
                                    </div>
                                    <div :class="['text-2xl font-black', m.color]">{{ m.value }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">{{ m.change }}</div>
                                </div>
                            </div>

                            <!-- Papers table -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Recent Papers</span>
                                    <span class="text-xs text-orange-500 font-medium cursor-pointer hover:underline">View all →</span>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <div v-for="paper in mockPapers" :key="paper.id" class="px-4 py-3.5 flex items-center gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-900/60 transition cursor-pointer">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ paper.title }}</div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-mono text-orange-500 dark:text-orange-400">{{ paper.id }}</span>
                                                <span class="text-[10px] text-slate-400">·</span>
                                                <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ paper.category }}</span>
                                            </div>
                                        </div>
                                        <!-- Progress bar -->
                                        <div class="hidden sm:block w-24">
                                            <div class="h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                                <div class="h-full rounded-full bg-gradient-to-r from-orange-400 to-teal-500 transition-all"
                                                    :style="{ width: paper.progress + '%' }" />
                                            </div>
                                            <div class="text-[9px] text-slate-400 mt-0.5 text-right">{{ paper.progress }}%</div>
                                        </div>
                                        <span :class="['text-[10px] font-semibold px-2.5 py-1 rounded-full shrink-0', statusColor[paper.status]]">
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
