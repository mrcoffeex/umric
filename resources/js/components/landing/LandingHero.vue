<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Check, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { useCountUp } from '@/composables/useCountUp';
import { useScrollReveal } from '@/composables/useScrollReveal';
import { dashboard, login, register } from '@/routes';

const props = defineProps<{
    canRegister: boolean;
    stats?: {
        papers: number;
        students: number;
        departments: number;
    };
}>();

const trackingId = ref('');
const trackingError = ref('');
const isSearching = ref(false);
const page = usePage();
const branding = useBranding();

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

const { target: statsRef, isVisible: statsVisible } = useScrollReveal(0.2);
const paperCount = useCountUp(
    computed(() => props.stats?.papers ?? 0),
    statsVisible,
    2000,
);
const studentCount = useCountUp(
    computed(() => props.stats?.students ?? 0),
    statsVisible,
    2000,
);
const deptCount = useCountUp(
    computed(() => props.stats?.departments ?? 0),
    statsVisible,
    1200,
);

const pipelineSteps = [
    { n: '01', label: 'Title proposal', status: 'complete' as const },
    { n: '02', label: 'Chapters', status: 'complete' as const },
    { n: '03', label: 'Panel review', status: 'current' as const },
    { n: '04', label: 'Oral defense', status: 'upcoming' as const },
    { n: '05', label: 'Publication', status: 'upcoming' as const },
];
</script>

<template>
    <section
        id="hero"
        class="scroll-mt-28 bg-um-wash px-4 pt-32 pb-16 sm:px-6 sm:pt-36 lg:px-8 lg:pb-20"
    >
        <div
            class="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2 lg:gap-16"
        >
            <div>
                <div class="mb-5 flex items-center gap-3 sm:gap-4">
                    <img
                        v-if="branding.logoUrl"
                        :src="branding.logoUrl"
                        :alt="branding.name"
                        class="h-14 w-14 shrink-0 object-contain sm:h-16 sm:w-16"
                    />
                    <p
                        class="text-[11px] font-bold tracking-[0.18em] text-um-maroon uppercase"
                    >
                        UM Digos College · Research and Innovation Center
                    </p>
                </div>
                <h1
                    class="mb-5 font-display text-4xl leading-[1.12] font-extrabold tracking-tight text-um-heading sm:text-5xl lg:text-[3.25rem]"
                >
                    Research tracked,
                    <span class="relative inline-block">
                        step by step.
                        <span
                            class="absolute inset-x-0 -bottom-1 h-[3px] bg-um-gold"
                        />
                    </span>
                </h1>
                <p
                    class="mb-8 max-w-xl text-base leading-relaxed text-um-body sm:text-lg"
                >
                    The campus research office for UM Digos College. Follow
                    every milestone from title proposal through panel review,
                    oral defense, and institutional publication.
                </p>

                <div
                    class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center"
                >
                    <template v-if="!page.props.auth.user">
                        <Link
                            v-if="canRegister"
                            :href="register.url()"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[3px] bg-um-gold px-6 text-sm font-bold text-white transition hover:bg-um-gold-hover active:opacity-90"
                        >
                            Submit research
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                        <Link
                            :href="login.url()"
                            class="inline-flex min-h-11 items-center justify-center rounded-[3px] border border-um-maroon px-6 text-sm font-bold text-um-maroon transition hover:bg-um-maroon hover:text-white"
                        >
                            Sign in
                        </Link>
                    </template>
                    <Link
                        v-else
                        :href="dashboard.url()"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[3px] bg-um-gold px-6 text-sm font-bold text-white transition hover:bg-um-gold-hover"
                    >
                        Go to dashboard
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <form
                    class="border border-black/8 bg-white p-2 shadow-sm"
                    @submit.prevent="searchPaper"
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <label class="sr-only" for="tracking-id"
                            >Tracking ID</label
                        >
                        <div
                            class="flex min-w-0 flex-1 items-center gap-3 px-3"
                        >
                            <Search
                                class="h-4 w-4 shrink-0 text-um-body"
                                aria-hidden="true"
                            />
                            <input
                                id="tracking-id"
                                v-model="trackingId"
                                type="text"
                                placeholder="Tracking ID (e.g. RP-XXXXXXXX)"
                                class="min-w-0 flex-1 bg-transparent py-2.5 text-base text-um-heading placeholder:text-um-body/70 focus:outline-none md:text-sm"
                                autocomplete="off"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="isSearching"
                            class="min-h-11 shrink-0 rounded-[3px] bg-um-maroon px-5 text-sm font-bold text-white transition hover:bg-um-maroon-deep disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ isSearching ? 'Searching…' : 'Track paper' }}
                        </button>
                    </div>
                </form>
                <p v-if="trackingError" class="mt-2 text-sm text-um-maroon">
                    {{ trackingError }}
                </p>
                <p class="mt-2 text-xs text-um-body">
                    No login required · Public tracking is anonymous
                </p>
            </div>

            <div
                class="border-t-4 border-um-maroon bg-white p-6 shadow-sm sm:p-8"
            >
                <div class="mb-6 flex items-start justify-between gap-3">
                    <div>
                        <p
                            class="text-[11px] font-bold tracking-[0.16em] text-um-maroon uppercase"
                        >
                            Research pipeline
                        </p>
                        <h2
                            class="mt-1 font-display text-xl font-bold text-um-heading"
                        >
                            Every milestone, visible
                        </h2>
                    </div>
                    <span
                        class="shrink-0 bg-um-gold px-2.5 py-1 text-[10px] font-bold tracking-wide text-white uppercase"
                    >
                        Live status
                    </span>
                </div>
                <ol class="space-y-3">
                    <li
                        v-for="step in pipelineSteps"
                        :key="step.n"
                        class="flex items-center gap-3 border px-3 py-2.5"
                        :class="
                            step.status === 'current'
                                ? 'border-um-maroon bg-um-wash'
                                : 'border-black/8'
                        "
                    >
                        <span
                            v-if="step.status === 'complete'"
                            class="flex h-8 w-8 shrink-0 items-center justify-center bg-um-gold text-white"
                        >
                            <Check class="h-4 w-4" stroke-width="3" />
                        </span>
                        <span
                            v-else
                            class="flex h-8 w-8 shrink-0 items-center justify-center text-xs font-bold"
                            :class="
                                step.status === 'current'
                                    ? 'bg-um-maroon text-white'
                                    : 'bg-um-wash text-um-body'
                            "
                        >
                            {{ step.n }}
                        </span>
                        <span
                            class="text-sm font-semibold"
                            :class="
                                step.status === 'upcoming'
                                    ? 'text-um-body'
                                    : 'text-um-heading'
                            "
                        >
                            {{ step.label }}
                        </span>
                    </li>
                </ol>
                <p class="mt-5 text-xs text-um-body">
                    RP-2026-···· · Panel review · Example pipeline
                </p>
            </div>
        </div>

        <div
            ref="statsRef"
            class="mx-auto mt-14 grid max-w-7xl grid-cols-2 gap-6 border-t border-black/8 pt-8 sm:grid-cols-4"
        >
            <div>
                <div
                    class="font-display text-2xl font-extrabold text-um-maroon tabular-nums"
                >
                    {{ paperCount.toLocaleString() }}+
                </div>
                <div
                    class="mt-1 text-xs font-semibold tracking-wide text-um-body uppercase"
                >
                    Papers tracked
                </div>
            </div>
            <div>
                <div
                    class="font-display text-2xl font-extrabold text-um-maroon tabular-nums"
                >
                    {{ studentCount.toLocaleString() }}+
                </div>
                <div
                    class="mt-1 text-xs font-semibold tracking-wide text-um-body uppercase"
                >
                    Student researchers
                </div>
            </div>
            <div>
                <div
                    class="font-display text-2xl font-extrabold text-um-maroon tabular-nums"
                >
                    {{ deptCount }}+
                </div>
                <div
                    class="mt-1 text-xs font-semibold tracking-wide text-um-body uppercase"
                >
                    Departments
                </div>
            </div>
            <div>
                <div
                    class="font-display text-2xl font-extrabold text-um-maroon tabular-nums"
                >
                    9
                </div>
                <div
                    class="mt-1 text-xs font-semibold tracking-wide text-um-body uppercase"
                >
                    Research stages
                </div>
            </div>
        </div>
    </section>
</template>
