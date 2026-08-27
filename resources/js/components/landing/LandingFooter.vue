<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, MapPin } from 'lucide-vue-next';
import LandingLockup from '@/components/landing/LandingLockup.vue';
import { useBranding } from '@/composables/useBranding';
import { documentation, faq, privacy, terms } from '@/routes';

const branding = useBranding();

function scrollTo(id: string) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

const officeLinks = [
    { label: 'Research process', section: 'features' },
    { label: 'How it works', section: 'how-it-works' },
    { label: 'Who it is for', section: 'who-its-for' },
    { label: 'Contact', section: 'contact' },
];

const resourceLinks = [
    { label: 'Documentation', route: documentation() },
    { label: 'FAQ', route: faq() },
];

const legalLinks = [
    { label: 'Terms & Conditions', route: terms() },
    { label: 'Privacy Policy', route: privacy() },
];
</script>

<template>
    <footer class="bg-um-navy px-4 pt-16 pb-8 text-white sm:px-6">
        <div class="mx-auto max-w-7xl">
            <div class="mb-12 h-1 w-16 bg-um-gold" />
            <div
                class="mb-12 grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div class="lg:col-span-1">
                    <button
                        type="button"
                        class="mb-4"
                        @click="scrollTo('hero')"
                    >
                        <LandingLockup inverted />
                    </button>
                    <p class="max-w-xs text-sm leading-relaxed text-white/70">
                        <template v-if="branding.tagline">{{
                            branding.tagline
                        }}</template>
                        <template v-else>
                            Official research paper tracking for UM Digos
                            College — from title proposal to publication.
                        </template>
                    </p>
                    <div class="mt-5 space-y-2 text-sm text-white/70">
                        <p class="flex items-start gap-2">
                            <MapPin
                                class="mt-0.5 h-4 w-4 shrink-0 text-um-gold"
                            />
                            Digos City, Davao del Sur, Philippines
                        </p>
                        <p class="flex items-start gap-2">
                            <Mail
                                class="mt-0.5 h-4 w-4 shrink-0 text-um-gold"
                            />
                            research@umdigos.edu.ph
                        </p>
                    </div>
                </div>

                <div>
                    <h4
                        class="mb-4 text-[11px] font-bold tracking-[0.16em] text-um-gold uppercase"
                    >
                        Research office
                    </h4>
                    <ul class="space-y-3">
                        <li v-for="link in officeLinks" :key="link.label">
                            <button
                                type="button"
                                class="text-sm text-white/75 transition hover:text-white"
                                @click="scrollTo(link.section)"
                            >
                                {{ link.label }}
                            </button>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4
                        class="mb-4 text-[11px] font-bold tracking-[0.16em] text-um-gold uppercase"
                    >
                        Resources
                    </h4>
                    <ul class="space-y-3">
                        <li v-for="link in resourceLinks" :key="link.label">
                            <Link
                                :href="link.route.url"
                                class="text-sm text-white/75 transition hover:text-white"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4
                        class="mb-4 text-[11px] font-bold tracking-[0.16em] text-um-gold uppercase"
                    >
                        Legal
                    </h4>
                    <ul class="space-y-3">
                        <li v-for="link in legalLinks" :key="link.label">
                            <Link
                                :href="link.route.url"
                                class="text-sm text-white/75 transition hover:text-white"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="flex flex-col items-start justify-between gap-3 border-t border-white/15 pt-6 sm:flex-row sm:items-center"
            >
                <p class="text-xs text-white/55">
                    &copy; {{ new Date().getFullYear() }} University of Mindanao
                    Digos College · Research and Innovation Center
                </p>
                <p class="text-xs text-white/55">Digos City, Davao del Sur</p>
            </div>
        </div>
    </footer>
</template>
