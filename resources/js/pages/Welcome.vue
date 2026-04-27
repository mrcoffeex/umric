<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import LandingCta from '@/components/landing/LandingCta.vue';
import LandingFeatures from '@/components/landing/LandingFeatures.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LandingHero from '@/components/landing/LandingHero.vue';
import LandingHowItWorks from '@/components/landing/LandingHowItWorks.vue';
import LandingNavbar from '@/components/landing/LandingNavbar.vue';
import LandingShowcase from '@/components/landing/LandingShowcase.vue';
import LandingTestimonials from '@/components/landing/LandingTestimonials.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useBranding } from '@/composables/useBranding';

interface Props {
    canRegister: boolean;
    featuredPapers?: Array<{
        id: string;
        title: string;
        description: string;
        status: string;
        category: { name: string };
        tracking_id: string;
    }>;
    categories?: Array<{ id: string; name: string }>;
    stats?: {
        papers: number;
        students: number;
        departments: number;
    };
}

const props = withDefaults(defineProps<Props>(), {
    canRegister: true,
});

const branding = useBranding();
const welcomeTitle = computed(
    () => `${branding.value.name} - Research Paper Tracking & Management`,
);
const welcomeDescription = computed(
    () =>
        `Submit, track and collaborate on research papers in real-time. ${branding.value.name} — built for academics.`,
);

// Initialise theme from stored preference on mount
const { updateAppearance } = useAppearance();
onMounted(() => {
    const stored = localStorage.getItem('appearance') as
        | 'light'
        | 'dark'
        | 'system'
        | null;

    if (stored) {
        updateAppearance(stored);
    }
});
</script>

<template>
    <Head :title="welcomeTitle">
        <meta name="description" :content="welcomeDescription" />
    </Head>

    <div
        class="min-h-screen bg-white text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100"
    >
        <LandingNavbar :can-register="props.canRegister" />
        <LandingHero
            :can-register="props.canRegister"
            :featured-papers="props.featuredPapers"
            :stats="props.stats"
        />
        <LandingFeatures />
        <LandingShowcase />
        <LandingHowItWorks />
        <LandingTestimonials />
        <LandingCta />
        <LandingFooter />
    </div>
</template>
