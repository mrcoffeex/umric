<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LandingNavbar from '@/components/landing/LandingNavbar.vue';
import LandingHero from '@/components/landing/LandingHero.vue';
import LandingFeatures from '@/components/landing/LandingFeatures.vue';
import LandingShowcase from '@/components/landing/LandingShowcase.vue';
import LandingHowItWorks from '@/components/landing/LandingHowItWorks.vue';
import LandingTestimonials from '@/components/landing/LandingTestimonials.vue';
import LandingCta from '@/components/landing/LandingCta.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import { onMounted } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

interface Props {
    canRegister: boolean;
    featuredPapers?: Array<{
        id: number;
        title: string;
        description: string;
        status: string;
        category: { name: string };
        tracking_id: string;
    }>;
    categories?: Array<{ id: number; name: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    canRegister: true,
});

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
    <Head title="UMRIC - Research Paper Tracking &amp; Management">
        <meta
            name="description"
            content="Submit, track and collaborate on research papers in real-time. UMRIC — built for academics."
        />
    </Head>

    <div
        class="min-h-screen bg-white text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100"
    >
        <LandingNavbar :can-register="props.canRegister" />
        <LandingHero
            :can-register="props.canRegister"
            :featured-papers="props.featuredPapers"
        />
        <LandingFeatures />
        <LandingShowcase />
        <LandingHowItWorks />
        <LandingTestimonials />
        <LandingCta />
        <LandingFooter />
    </div>
</template>
