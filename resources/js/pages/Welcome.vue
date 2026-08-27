<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import LandingAudience from '@/components/landing/LandingAudience.vue';
import LandingCta from '@/components/landing/LandingCta.vue';
import LandingFeatures from '@/components/landing/LandingFeatures.vue';
import LandingFooter from '@/components/landing/LandingFooter.vue';
import LandingHero from '@/components/landing/LandingHero.vue';
import LandingHowItWorks from '@/components/landing/LandingHowItWorks.vue';
import LandingNavbar from '@/components/landing/LandingNavbar.vue';
import LandingShowcase from '@/components/landing/LandingShowcase.vue';
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
    () =>
        `${branding.value.name} — UM Digos College Research and Innovation Center`,
);
const welcomeDescription = computed(
    () =>
        `Official research paper tracking for UM Digos College. Follow every milestone from title proposal to publication with ${branding.value.name}.`,
);
</script>

<template>
    <Head :title="welcomeTitle">
        <meta name="description" :content="welcomeDescription" />
    </Head>

    <div class="min-h-screen bg-um-wash font-landing text-um-heading">
        <LandingNavbar :can-register="props.canRegister" />
        <LandingHero :can-register="props.canRegister" :stats="props.stats" />
        <LandingFeatures />
        <LandingShowcase />
        <LandingHowItWorks />
        <LandingAudience />
        <LandingCta />
        <LandingFooter />
    </div>
</template>
