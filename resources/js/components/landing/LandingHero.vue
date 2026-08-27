<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { dashboard, login, register } from '@/routes';

defineProps<{
    canRegister: boolean;
}>();

const branding = useBranding();
const trackingId = ref('');
const trackingError = ref('');
const isSearching = ref(false);
const page = usePage();

const pipelineStages = [
    { label: 'Title proposal', status: 'done' },
    { label: 'Chapters', status: 'done' },
    { label: 'Panel review', status: 'active' },
    { label: 'Oral defense', status: 'upcoming' },
    { label: 'Publication', status: 'upcoming' },
];

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
</script>

<template>
    <section
        id="hero"
        class="relative overflow-hidden pt-24 pb-10 sm:pt-28 sm:pb-14 lg:min-h-[calc(100svh-0px)] lg:pb-16"
    >
        <div class="landing-mesh absolute inset-0 -z-10 bg-slate-50 dark:bg-slate-950" />

        <div
            class="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:gap-8 lg:px-8"
        >
            <!-- Copy column -->
            <div class="relative z-10 lg:col-span-5">
                <p
                    class="hero-stagger-1 font-display mb-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                >
                    {{ branding.name }}
                </p>

                <h1
                    class="hero-stagger-2 font-display mb-4 text-4xl leading-[1.08] font-extrabold tracking-tight text-slate-900 sm:text-5xl dark:text-white"
                >
                    Research tracked,
                    <span class="text-shimmer">step by step.</span>
                </h1>

                <p
                    class="hero-stagger-3 mb-8 max-w-md text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400"
                >
                    {{
                        branding.tagline ||
                        'From title proposal to publication — one workflow for students, advisers, and the research office.'
                    }}
                </p>

                <div
                    class="hero-stagger-4 mb-8 flex flex-col gap-3 sm:flex-row sm:items-center"
                >
                    <template v-if="!page.props.auth.user">
                        <Link
                            v-if="canRegister"
                            :href="register.url()"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/25 transition hover:bg-orange-600 active:scale-[0.98]"
                        >
                            Get Started
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="login.url()"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white/80 px-6 py-3 text-sm font-semibold text-slate-800 transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100 dark:hover:bg-slate-900"
                        >
                            Sign in
                        </Link>
                    </template>
                    <Link
                        v-else
                        :href="dashboard.url()"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-teal-600/25 transition hover:bg-teal-700 active:scale-[0.98]"
                    >
                        Go to Dashboard
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <!-- Tracking search (kept in hero per plan) -->
                <div class="hero-stagger-5 w-full max-w-lg">
                    <label class="sr-only" for="landing-track-id"
                        >Paper tracking ID</label
                    >
                    <div
                        class="flex flex-col gap-2 rounded-2xl border border-slate-200/90 bg-white/90 p-1.5 shadow-lg shadow-slate-900/5 sm:flex-row sm:items-center dark:border-slate-700/80 dark:bg-slate-900/90 dark:shadow-black/20"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-3 px-3">
                            <Search
                                class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500"
                            />
                            <input
                                id="landing-track-id"
                                v-model="trackingId"
                                type="text"
                                placeholder="Tracking ID (e.g. RP-XXXXXXXX)"
                                class="min-w-0 flex-1 bg-transparent py-2.5 text-base text-slate-800 placeholder-slate-400 focus:outline-none sm:text-sm dark:text-slate-200 dark:placeholder-slate-600"
                                @keyup.enter="searchPaper"
                            />
                        </div>
                        <button
                            type="button"
                            class="min-h-11 rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-semibold whitespace-nowrap text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-60 active:scale-[0.98]"
                            :disabled="isSearching"
                            @click="searchPaper"
                        >
                            {{ isSearching ? 'Searching…' : 'Track Paper' }}
                        </button>
                    </div>
                    <p
                        v-if="trackingError"
                        class="mt-2 text-sm text-red-500"
                        role="alert"
                    >
                        {{ trackingError }}
                    </p>
                    <p
                        class="mt-2 text-xs text-slate-500 dark:text-slate-500"
                    >
                        No login required · Public tracking is anonymous
                    </p>
                </div>
            </div>

            <!-- Full-bleed pipeline visual -->
            <div
                class="hero-card-enter relative lg:col-span-7 lg:-mr-8 xl:-mr-16"
            >
                <div
                    class="relative overflow-hidden border-y border-slate-200/80 bg-slate-900 text-slate-100 sm:rounded-l-3xl sm:border sm:border-r-0 dark:border-slate-700/60"
                    aria-hidden="true"
                >
                    <div
                        class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(249,115,22,0.28),transparent_55%),radial-gradient(ellipse_at_bottom_left,rgba(20,184,166,0.22),transparent_50%)]"
                    />
                    <div
                        class="absolute inset-0 opacity-[0.07]"
                        style="
                            background-image: linear-gradient(
                                    rgba(255, 255, 255, 0.9) 1px,
                                    transparent 1px
                                ),
                                linear-gradient(
                                    90deg,
                                    rgba(255, 255, 255, 0.9) 1px,
                                    transparent 1px
                                );
                            background-size: 48px 48px;
                        "
                    />

                    <div class="relative px-5 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                        <div class="mb-8 flex items-end justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.2em] text-orange-300/90 uppercase"
                                >
                                    Research pipeline
                                </p>
                                <p
                                    class="font-display mt-1 text-2xl font-bold tracking-tight sm:text-3xl"
                                >
                                    Every milestone, visible
                                </p>
                            </div>
                            <span
                                class="hidden rounded-full border border-teal-400/30 bg-teal-400/10 px-3 py-1 text-xs font-semibold text-teal-300 sm:inline"
                            >
                                Live status
                            </span>
                        </div>

                        <ol class="space-y-3">
                            <li
                                v-for="(stage, i) in pipelineStages"
                                :key="stage.label"
                                class="landing-pipeline-stage flex items-center gap-4 rounded-2xl border px-4 py-3.5 backdrop-blur-sm"
                                :class="
                                    stage.status === 'active'
                                        ? 'border-orange-400/40 bg-orange-500/15'
                                        : stage.status === 'done'
                                          ? 'border-white/10 bg-white/5'
                                          : 'border-white/5 bg-white/[0.03]'
                                "
                                :style="{ animationDelay: `${0.35 + i * 0.08}s` }"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold"
                                    :class="
                                        stage.status === 'active'
                                            ? 'bg-orange-500 text-white'
                                            : stage.status === 'done'
                                              ? 'bg-teal-500/90 text-white'
                                              : 'bg-slate-700 text-slate-300'
                                    "
                                >
                                    {{
                                        stage.status === 'done'
                                            ? '✓'
                                            : String(i + 1).padStart(2, '0')
                                    }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold">
                                        {{ stage.label }}
                                    </p>
                                    <p
                                        class="text-xs capitalize"
                                        :class="
                                            stage.status === 'active'
                                                ? 'text-orange-200'
                                                : 'text-slate-400'
                                        "
                                    >
                                        {{
                                            stage.status === 'done'
                                                ? 'Completed'
                                                : stage.status === 'active'
                                                  ? 'In progress'
                                                  : 'Upcoming'
                                        }}
                                    </p>
                                </div>
                                <span
                                    v-if="stage.status === 'active'"
                                    class="hidden h-2 w-2 shrink-0 rounded-full bg-orange-400 sm:block"
                                    style="
                                        box-shadow: 0 0 0 4px
                                            rgba(249, 115, 22, 0.25);
                                    "
                                />
                            </li>
                        </ol>

                        <p
                            class="mt-8 font-mono text-[11px] tracking-wide text-slate-500"
                        >
                            RP-2026-•••• · Panel review · Updated today
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
