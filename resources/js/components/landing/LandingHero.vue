<script setup lang="ts">
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ArrowRight, Search, Zap, ChevronDown } from 'lucide-vue-next'
import { login, register, dashboard } from '@/routes'

defineProps<{
    canRegister: boolean
    featuredPapers?: Array<{
        id: number
        title: string
        status: string
        tracking_id: string
    }>
}>()

const trackingId = ref('')
const trackingError = ref('')
const isSearching = ref(false)
const page = usePage()

async function searchPaper() {
    if (!trackingId.value.trim()) {
        trackingError.value = 'Please enter a tracking ID'
        return
    }
    isSearching.value = true
    trackingError.value = ''
    try {
        const response = await fetch(`/track/${encodeURIComponent(trackingId.value.trim())}`)
        if (response.ok) {
            window.location.href = `/track/${encodeURIComponent(trackingId.value.trim())}`
        } else {
            trackingError.value = 'Paper not found. Please check the tracking ID.'
        }
    } catch {
        trackingError.value = 'Error searching. Please try again.'
    } finally {
        isSearching.value = false
    }
}

function scrollToFeatures() {
    document.getElementById('features')?.scrollIntoView({ behavior: 'smooth' })
}

const stats = [
    { value: '1,200+', label: 'Papers Tracked', color: 'text-orange-500' },
    { value: '800+', label: 'Student Researchers', color: 'text-teal-500' },
    { value: '12+', label: 'Departments', color: 'text-orange-500' },
    { value: '6', label: 'Research Stages', color: 'text-teal-500' },
]
</script>

<template>
    <section
        id="hero"
        class="relative min-h-screen flex flex-col justify-center overflow-hidden pt-30 pb-16 px-5 sm:px-8 lg:px-12"
    >
        <!-- Animated gradient background -->
        <div class="absolute inset-0 -z-10">
            <!-- Base bg -->
            <div class="absolute inset-0 bg-slate-50 dark:bg-slate-950" />
            <!-- Gradient mesh -->
            <div class="absolute inset-0 opacity-40 dark:opacity-20"
                style="background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(249,115,22,0.25) 0%, transparent 60%), radial-gradient(ellipse 60% 50% at 100% 60%, rgba(20,184,166,0.2) 0%, transparent 60%), radial-gradient(ellipse 60% 50% at -5% 70%, rgba(249,115,22,0.15) 0%, transparent 60%)"
            />
            <!-- Animated blobs -->
            <div class="absolute top-1/4 -left-20 w-72 h-72 rounded-full bg-orange-400/20 dark:bg-orange-500/10 blur-3xl animate-blob" style="animation-delay:0s" />
            <div class="absolute top-1/3 right-0 w-96 h-96 rounded-full bg-teal-400/15 dark:bg-teal-500/8 blur-3xl animate-blob" style="animation-delay:3s" />
            <div class="absolute bottom-1/4 left-1/3 w-64 h-64 rounded-full bg-orange-300/15 dark:bg-orange-600/8 blur-3xl animate-blob" style="animation-delay:6s" />
        </div>

        <div class="max-w-7xl mx-auto w-full flex-1 flex flex-col justify-center">
            <div class="grid lg:grid-cols-2 gap-16 lg:gap-12 items-center">
                <!-- Left: Text content -->
                <div class="text-center lg:text-left">

                    <!-- Headline -->
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black leading-[1.05] mb-6 tracking-tight">
                        <span class="text-slate-900 dark:text-white">Your Research,</span>
                        <br />
                        <span class="text-gradient">Every Step.</span>
                    </h1>

                    <!-- Subtext -->
                    <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-400 mb-10 max-w-lg mx-auto lg:mx-0 leading-relaxed">
                        Track every milestone of your student research — from title proposal and chapter submissions, through panel review and oral defense, all the way to final publication.
                    </p>

                    <!-- CTAs -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-14">
                        <template v-if="!page.props.auth.user">
                            <Link v-if="canRegister" :href="register.url()">
                                <button class="group flex items-center gap-2 px-7 py-3.5 rounded-xl text-base font-semibold text-white
                                    bg-gradient-to-r from-orange-500 to-orange-600
                                    shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50
                                    hover:from-orange-600 hover:to-orange-700
                                    transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                                    Get Started
                                    <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="dashboard.url()">
                                <button class="group flex items-center gap-2 px-7 py-3.5 rounded-xl text-base font-semibold text-white
                                    bg-gradient-to-r from-orange-500 to-teal-500
                                    shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50
                                    transition-all duration-200 hover:scale-[1.02]">
                                    Go to Dashboard
                                    <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                                </button>
                            </Link>
                        </template>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-lg mx-auto lg:mx-0">
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="text-center lg:text-left"
                        >
                            <div :class="['text-2xl font-black', stat.color]">{{ stat.value }}</div>
                            <div class="text-xs font-medium text-slate-500 dark:text-slate-500 mt-0.5">{{ stat.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Floating UI showcase -->
                <div class="relative hidden lg:flex justify-center lg:justify-end">
                    <!-- Main card -->
                    <div class="relative w-full max-w-md animate-float">
                        <!-- Browser chrome frame -->
                        <div class="rounded-2xl overflow-hidden shadow-2xl shadow-slate-900/20 dark:shadow-black/50 border border-slate-200/60 dark:border-slate-700/60">
                            <!-- Title bar -->
                            <div class="px-4 py-3 bg-slate-100 dark:bg-slate-800 flex items-center gap-2 border-b border-slate-200/60 dark:border-slate-700/60">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-red-400/70" />
                                    <div class="w-3 h-3 rounded-full bg-yellow-400/70" />
                                    <div class="w-3 h-3 rounded-full bg-green-400/70" />
                                </div>
                                <div class="flex-1 mx-3 px-3 py-1 rounded-md bg-white/60 dark:bg-slate-900/60 text-xs text-slate-400 dark:text-slate-500 font-mono">
                                    umric.university.edu
                                </div>
                            </div>
                            <!-- Content -->
                            <div class="p-5 bg-white dark:bg-slate-900 space-y-4">
                                <!-- Header -->
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Track Paper</h3>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-400">Live</span>
                                </div>
                                <!-- Paper card -->
                                <div class="p-4 rounded-xl bg-gradient-to-br from-orange-50 to-teal-50/50 dark:from-orange-950/30 dark:to-teal-950/20 border border-orange-100 dark:border-orange-900/40">
                                    <div class="text-xs font-semibold text-orange-600 dark:text-orange-400 mb-1">RP-2026-IT-0047</div>
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-3 leading-snug">
                                        Smart Attendance System Using Face Recognition for UMDC
                                    </div>
                                    <!-- Status timeline -->
                                    <div class="flex items-center gap-1.5">
                                        <div v-for="(step, i) in ['Proposal','Chapters','Panel Review','Defense']" :key="step"
                                            class="flex items-center gap-1.5">
                                            <div :class="[
                                                'w-2.5 h-2.5 rounded-full border-2 transition-all',
                                                i <= 2
                                                    ? 'bg-orange-500 border-orange-500'
                                                    : 'bg-transparent border-slate-300 dark:border-slate-600'
                                            ]" />
                                            <div v-if="i < 3" :class="[
                                                'h-0.5 w-6',
                                                i < 2 ? 'bg-orange-400' : 'bg-slate-200 dark:bg-slate-700'
                                            ]" />
                                        </div>
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <div v-for="step in ['Proposal','Chapters','Panel Review','Defense']" :key="step"
                                            class="text-[9px] text-slate-500 dark:text-slate-500">{{ step }}</div>
                                    </div>
                                </div>
                                <!-- Activity feed -->
                                <div class="space-y-2">
                                    <div v-for="item in [
                                        { text: 'Panel review scheduled by Dr. Santos', time: '2h ago', color: 'bg-teal-400' },
                                        { text: 'Chapter 3 revision requested', time: '1d ago', color: 'bg-orange-400' },
                                        { text: 'Title proposal approved', time: '2w ago', color: 'bg-blue-400' },
                                    ]" :key="item.text" class="flex items-start gap-2.5 text-xs">
                                        <div :class="['w-1.5 h-1.5 rounded-full mt-1 shrink-0', item.color]" />
                                        <span class="text-slate-600 dark:text-slate-400 flex-1">{{ item.text }}</span>
                                        <span class="text-slate-400 dark:text-slate-600 shrink-0">{{ item.time }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating badge card -->
                        <div class="absolute -bottom-5 -left-6 glass-card rounded-xl px-4 py-3 shadow-xl animate-float-slow" style="animation-delay:1.5s">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                                    <Zap class="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Real-time Updates</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-500">No refresh needed</div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating stat card -->
                        <div class="absolute -top-4 -right-4 glass-card rounded-xl px-4 py-3 shadow-xl animate-float-slow" style="animation-delay:0.8s">
                            <div class="text-xs font-medium text-slate-500 dark:text-slate-500 mb-0.5">New this month</div>
                            <div class="text-2xl font-black text-orange-500">+142</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-500">papers submitted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick track widget -->
        <div class="max-w-2xl mx-auto w-full mt-14 px-1">
            <div class="relative rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-white dark:bg-slate-900 shadow-lg shadow-slate-900/5 dark:shadow-black/20 p-1.5 flex gap-2">
                <div class="flex items-center gap-3 flex-1 pl-3">
                    <Search class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" />
                    <input
                        v-model="trackingId"
                        @keyup.enter="searchPaper"
                        type="text"
                        placeholder="Enter tracking ID  (e.g. RP-XXXXXXXX)"
                        class="flex-1 bg-transparent py-2.5 text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none"
                    />
                </div>
                <button
                    @click="searchPaper"
                    :disabled="isSearching"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white
                        bg-gradient-to-r from-orange-500 to-orange-600
                        shadow-md shadow-orange-500/20
                        hover:from-orange-600 hover:to-orange-700
                        disabled:opacity-60 disabled:cursor-not-allowed
                        transition-all duration-200 hover:scale-[1.02] whitespace-nowrap"
                >
                    {{ isSearching ? 'Searching…' : 'Track Paper' }}
                </button>
            </div>
            <p v-if="trackingError" class="text-red-500 text-sm mt-2 text-center">{{ trackingError }}</p>
            <p class="text-center text-xs text-slate-400 dark:text-slate-600 mt-2">No login required · Public tracking is anonymous</p>
        </div>

        <!-- Scroll indicator -->
        <div class="flex justify-center mt-16">
            <button
                @click="scrollToFeatures"
                class="flex flex-col items-center gap-2 text-slate-400 hover:text-orange-500 transition-colors"
            >
                <span class="text-xs font-medium">Explore features</span>
                <ChevronDown class="w-5 h-5 animate-bounce" />
            </button>
        </div>
    </section>
</template>
