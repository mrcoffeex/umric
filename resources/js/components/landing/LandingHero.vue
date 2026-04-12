<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Search, Zap, ChevronDown } from 'lucide-vue-next';
import { login, register, dashboard } from '@/routes';

defineProps<{
    canRegister: boolean;
    featuredPapers?: Array<{
        id: number;
        title: string;
        status: string;
        tracking_id: string;
    }>;
}>();

const trackingId = ref('');
const trackingError = ref('');
const isSearching = ref(false);
const page = usePage();

async function searchPaper() {
    if (!trackingId.value.trim()) {
        trackingError.value = 'Please enter a tracking ID';
        return;
    }
    isSearching.value = true;
    trackingError.value = '';
    try {
        const response = await fetch(
            `/track/${encodeURIComponent(trackingId.value.trim())}`,
        );
        if (response.ok) {
            window.location.href = `/track/${encodeURIComponent(trackingId.value.trim())}`;
        } else {
            trackingError.value =
                'Paper not found. Please check the tracking ID.';
        }
    } catch {
        trackingError.value = 'Error searching. Please try again.';
    } finally {
        isSearching.value = false;
    }
}

function scrollToFeatures() {
    document.getElementById('features')?.scrollIntoView({ behavior: 'smooth' });
}

const stats = [
    { value: '1,200+', label: 'Papers Tracked', color: 'text-orange-500' },
    { value: '800+', label: 'Student Researchers', color: 'text-teal-500' },
    { value: '12+', label: 'Departments', color: 'text-orange-500' },
    { value: '6', label: 'Research Stages', color: 'text-teal-500' },
];
</script>

<template>
    <section
        id="hero"
        class="relative flex min-h-screen flex-col justify-center overflow-hidden px-5 pt-30 pb-16 sm:px-8 lg:px-12"
    >
        <!-- Animated gradient background -->
        <div class="absolute inset-0 -z-10">
            <!-- Base bg -->
            <div class="absolute inset-0 bg-slate-50 dark:bg-slate-950" />
            <!-- Gradient mesh -->
            <div
                class="absolute inset-0 opacity-40 dark:opacity-20"
                style="
                    background:
                        radial-gradient(
                            ellipse 80% 60% at 50% -10%,
                            rgba(249, 115, 22, 0.25) 0%,
                            transparent 60%
                        ),
                        radial-gradient(
                            ellipse 60% 50% at 100% 60%,
                            rgba(20, 184, 166, 0.2) 0%,
                            transparent 60%
                        ),
                        radial-gradient(
                            ellipse 60% 50% at -5% 70%,
                            rgba(249, 115, 22, 0.15) 0%,
                            transparent 60%
                        );
                "
            />
            <!-- Animated blobs -->
            <div
                class="animate-blob absolute top-1/4 -left-20 h-72 w-72 rounded-full bg-orange-400/20 blur-3xl dark:bg-orange-500/10"
                style="animation-delay: 0s"
            />
            <div
                class="animate-blob absolute top-1/3 right-0 h-96 w-96 rounded-full bg-teal-400/15 blur-3xl dark:bg-teal-500/8"
                style="animation-delay: 3s"
            />
            <div
                class="animate-blob absolute bottom-1/4 left-1/3 h-64 w-64 rounded-full bg-orange-300/15 blur-3xl dark:bg-orange-600/8"
                style="animation-delay: 6s"
            />
        </div>

        <div
            class="mx-auto flex w-full max-w-7xl flex-1 flex-col justify-center"
        >
            <div class="grid items-center gap-16 lg:grid-cols-2 lg:gap-12">
                <!-- Left: Text content -->
                <div class="text-center lg:text-left">
                    <!-- Headline -->
                    <h1
                        class="mb-6 text-5xl leading-[1.05] font-black tracking-tight sm:text-6xl lg:text-7xl"
                    >
                        <span class="text-slate-900 dark:text-white"
                            >Your Research,</span
                        >
                        <br />
                        <span class="text-gradient">Every Step.</span>
                    </h1>

                    <!-- Subtext -->
                    <p
                        class="mx-auto mb-10 max-w-lg text-lg leading-relaxed text-slate-600 sm:text-xl lg:mx-0 dark:text-slate-400"
                    >
                        Track every milestone of your student research — from
                        title proposal and chapter submissions, through panel
                        review and oral defense, all the way to final
                        publication.
                    </p>

                    <!-- CTAs -->
                    <div
                        class="mb-14 flex flex-col justify-center gap-4 sm:flex-row lg:justify-start"
                    >
                        <template v-if="!page.props.auth.user">
                            <Link v-if="canRegister" :href="register.url()">
                                <button
                                    class="group flex items-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-500/30 transition-all duration-200 hover:scale-[1.02] hover:from-orange-600 hover:to-orange-700 hover:shadow-orange-500/50 active:scale-[0.98]"
                                >
                                    Get Started
                                    <ArrowRight
                                        class="h-4 w-4 transition-transform group-hover:translate-x-1"
                                    />
                                </button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="dashboard.url()">
                                <button
                                    class="group flex items-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-teal-500 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-orange-500/30 transition-all duration-200 hover:scale-[1.02] hover:shadow-orange-500/50"
                                >
                                    Go to Dashboard
                                    <ArrowRight
                                        class="h-4 w-4 transition-transform group-hover:translate-x-1"
                                    />
                                </button>
                            </Link>
                        </template>
                    </div>

                    <!-- Stats row -->
                    <div
                        class="mx-auto grid max-w-lg grid-cols-2 gap-4 sm:grid-cols-4 lg:mx-0"
                    >
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="text-center lg:text-left"
                        >
                            <div :class="['text-2xl font-black', stat.color]">
                                {{ stat.value }}
                            </div>
                            <div
                                class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-500"
                            >
                                {{ stat.label }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Floating UI showcase -->
                <div
                    class="relative hidden justify-center lg:flex lg:justify-end"
                >
                    <!-- Main card -->
                    <div class="animate-float relative w-full max-w-md">
                        <!-- Browser chrome frame -->
                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200/60 shadow-2xl shadow-slate-900/20 dark:border-slate-700/60 dark:shadow-black/50"
                        >
                            <!-- Title bar -->
                            <div
                                class="flex items-center gap-2 border-b border-slate-200/60 bg-slate-100 px-4 py-3 dark:border-slate-700/60 dark:bg-slate-800"
                            >
                                <div class="flex gap-1.5">
                                    <div
                                        class="h-3 w-3 rounded-full bg-red-400/70"
                                    />
                                    <div
                                        class="h-3 w-3 rounded-full bg-yellow-400/70"
                                    />
                                    <div
                                        class="h-3 w-3 rounded-full bg-green-400/70"
                                    />
                                </div>
                                <div
                                    class="mx-3 flex-1 rounded-md bg-white/60 px-3 py-1 font-mono text-xs text-slate-400 dark:bg-slate-900/60 dark:text-slate-500"
                                >
                                    umric.university.edu
                                </div>
                            </div>
                            <!-- Content -->
                            <div
                                class="space-y-4 bg-white p-5 dark:bg-slate-900"
                            >
                                <!-- Header -->
                                <div class="flex items-center justify-between">
                                    <h3
                                        class="text-sm font-bold text-slate-800 dark:text-slate-200"
                                    >
                                        Track Paper
                                    </h3>
                                    <span
                                        class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-950/50 dark:text-green-400"
                                        >Live</span
                                    >
                                </div>
                                <!-- Paper card -->
                                <div
                                    class="rounded-xl border border-orange-100 bg-gradient-to-br from-orange-50 to-teal-50/50 p-4 dark:border-orange-900/40 dark:from-orange-950/30 dark:to-teal-950/20"
                                >
                                    <div
                                        class="mb-1 text-xs font-semibold text-orange-600 dark:text-orange-400"
                                    >
                                        RP-2026-IT-0047
                                    </div>
                                    <div
                                        class="mb-3 text-sm leading-snug font-semibold text-slate-800 dark:text-slate-200"
                                    >
                                        Smart Attendance System Using Face
                                        Recognition for UMDC
                                    </div>
                                    <!-- Status timeline -->
                                    <div class="flex items-center gap-1.5">
                                        <div
                                            v-for="(step, i) in [
                                                'Proposal',
                                                'Chapters',
                                                'Panel Review',
                                                'Defense',
                                            ]"
                                            :key="step"
                                            class="flex items-center gap-1.5"
                                        >
                                            <div
                                                :class="[
                                                    'h-2.5 w-2.5 rounded-full border-2 transition-all',
                                                    i <= 2
                                                        ? 'border-orange-500 bg-orange-500'
                                                        : 'border-slate-300 bg-transparent dark:border-slate-600',
                                                ]"
                                            />
                                            <div
                                                v-if="i < 3"
                                                :class="[
                                                    'h-0.5 w-6',
                                                    i < 2
                                                        ? 'bg-orange-400'
                                                        : 'bg-slate-200 dark:bg-slate-700',
                                                ]"
                                            />
                                        </div>
                                    </div>
                                    <div class="mt-1 flex justify-between">
                                        <div
                                            v-for="step in [
                                                'Proposal',
                                                'Chapters',
                                                'Panel Review',
                                                'Defense',
                                            ]"
                                            :key="step"
                                            class="text-[9px] text-slate-500 dark:text-slate-500"
                                        >
                                            {{ step }}
                                        </div>
                                    </div>
                                </div>
                                <!-- Activity feed -->
                                <div class="space-y-2">
                                    <div
                                        v-for="item in [
                                            {
                                                text: 'Panel review scheduled by Dr. Santos',
                                                time: '2h ago',
                                                color: 'bg-teal-400',
                                            },
                                            {
                                                text: 'Chapter 3 revision requested',
                                                time: '1d ago',
                                                color: 'bg-orange-400',
                                            },
                                            {
                                                text: 'Title proposal approved',
                                                time: '2w ago',
                                                color: 'bg-blue-400',
                                            },
                                        ]"
                                        :key="item.text"
                                        class="flex items-start gap-2.5 text-xs"
                                    >
                                        <div
                                            :class="[
                                                'mt-1 h-1.5 w-1.5 shrink-0 rounded-full',
                                                item.color,
                                            ]"
                                        />
                                        <span
                                            class="flex-1 text-slate-600 dark:text-slate-400"
                                            >{{ item.text }}</span
                                        >
                                        <span
                                            class="shrink-0 text-slate-400 dark:text-slate-600"
                                            >{{ item.time }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating badge card -->
                        <div
                            class="glass-card animate-float-slow absolute -bottom-5 -left-6 rounded-xl px-4 py-3 shadow-xl"
                            style="animation-delay: 1.5s"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-teal-500 to-teal-600"
                                >
                                    <Zap class="h-4 w-4 text-white" />
                                </div>
                                <div>
                                    <div
                                        class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                    >
                                        Real-time Updates
                                    </div>
                                    <div
                                        class="text-[10px] text-slate-500 dark:text-slate-500"
                                    >
                                        No refresh needed
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating stat card -->
                        <div
                            class="glass-card animate-float-slow absolute -top-4 -right-4 rounded-xl px-4 py-3 shadow-xl"
                            style="animation-delay: 0.8s"
                        >
                            <div
                                class="mb-0.5 text-xs font-medium text-slate-500 dark:text-slate-500"
                            >
                                New this month
                            </div>
                            <div class="text-2xl font-black text-orange-500">
                                +142
                            </div>
                            <div
                                class="text-[10px] text-slate-500 dark:text-slate-500"
                            >
                                papers submitted
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick track widget -->
        <div class="mx-auto mt-14 w-full max-w-2xl px-1">
            <div
                class="relative flex gap-2 rounded-2xl border border-slate-200/80 bg-white p-1.5 shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900 dark:shadow-black/20"
            >
                <div class="flex flex-1 items-center gap-3 pl-3">
                    <Search
                        class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500"
                    />
                    <input
                        v-model="trackingId"
                        @keyup.enter="searchPaper"
                        type="text"
                        placeholder="Enter tracking ID  (e.g. RP-XXXXXXXX)"
                        class="flex-1 bg-transparent py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none dark:text-slate-200 dark:placeholder-slate-600"
                    />
                </div>
                <button
                    @click="searchPaper"
                    :disabled="isSearching"
                    class="rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 px-5 py-2.5 text-sm font-semibold whitespace-nowrap text-white shadow-md shadow-orange-500/20 transition-all duration-200 hover:scale-[1.02] hover:from-orange-600 hover:to-orange-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ isSearching ? 'Searching…' : 'Track Paper' }}
                </button>
            </div>
            <p
                v-if="trackingError"
                class="mt-2 text-center text-sm text-red-500"
            >
                {{ trackingError }}
            </p>
            <p
                class="mt-2 text-center text-xs text-slate-400 dark:text-slate-600"
            >
                No login required · Public tracking is anonymous
            </p>
        </div>

        <!-- Scroll indicator -->
        <div class="mt-16 flex justify-center">
            <button
                @click="scrollToFeatures"
                class="flex flex-col items-center gap-2 text-slate-400 transition-colors hover:text-orange-500"
            >
                <span class="text-xs font-medium">Explore features</span>
                <ChevronDown class="h-5 w-5 animate-bounce" />
            </button>
        </div>
    </section>
</template>
