<script setup lang="ts">
import { FlaskConical, Twitter, Github, Mail } from 'lucide-vue-next';

function scrollTo(id: string) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

const footerLinks = {
    Platform: [
        { label: 'Features', section: 'features' },
        { label: 'Showcase', section: 'showcase' },
        { label: 'How It Works', section: 'how-it-works' },
    ],
    Reviews: [{ label: 'Research Reviews', section: 'testimonials' }],
    Contact: [{ label: 'Contact Us', section: 'contact' }],
    Legal: [
        { label: 'Privacy Policy', href: '#' },
        { label: 'Terms of Service', href: '#' },
        { label: 'Cookie Policy', href: '#' },
    ],
};
</script>

<template>
    <footer
        class="bg-slate-100 px-5 pt-16 pb-8 text-slate-600 dark:bg-slate-950 dark:text-slate-400"
    >
        <div class="mx-auto max-w-7xl">
            <!-- Top grid -->
            <div
                class="mb-12 grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-5"
            >
                <!-- Brand column -->
                <div class="lg:col-span-2">
                    <button
                        @click="scrollTo('hero')"
                        class="group mb-4 flex items-center gap-2"
                    >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500 shadow-md transition-transform group-hover:scale-105"
                        >
                            <FlaskConical class="h-4 w-4 text-white" />
                        </div>
                        <span class="text-gradient text-xl font-black"
                            >UMRIC</span
                        >
                    </button>
                    <p
                        class="max-w-xs text-sm leading-relaxed text-slate-500 dark:text-slate-500"
                    >
                        Official research paper tracking system of UM Digos
                        College &mdash; from title proposal to publication.
                    </p>
                    <!-- Social -->
                    <div class="mt-6 flex gap-3">
                        <a
                            v-for="social in [
                                { icon: Twitter, label: 'Twitter', href: '#' },
                                { icon: Github, label: 'GitHub', href: '#' },
                                { icon: Mail, label: 'Email', href: '#' },
                            ]"
                            :key="social.label"
                            :href="social.href"
                            :aria-label="social.label"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-slate-100 text-slate-600 transition-all duration-200 hover:border-orange-300 hover:bg-orange-100 hover:text-orange-500 dark:border-slate-700/60 dark:bg-slate-800/80 dark:text-slate-400 dark:hover:border-orange-600/40 dark:hover:bg-orange-950/30 dark:hover:text-orange-400"
                        >
                            <component :is="social.icon" class="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <!-- Link columns -->
                <div v-for="(links, group) in footerLinks" :key="group">
                    <h4
                        class="mb-4 text-sm font-semibold text-slate-700 dark:text-slate-300"
                    >
                        {{ group }}
                    </h4>
                    <ul class="space-y-3">
                        <li v-for="link in links" :key="link.label">
                            <button
                                v-if="'section' in link"
                                @click="scrollTo(link.section)"
                                class="text-sm text-slate-500 transition-colors hover:text-orange-500 dark:hover:text-orange-400"
                            >
                                {{ link.label }}
                            </button>
                            <a
                                v-else
                                :href="link.href"
                                class="text-sm text-slate-500 transition-colors hover:text-orange-500 dark:hover:text-orange-400"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div
                class="flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-8 sm:flex-row dark:border-slate-800/80"
            >
                <p class="text-xs text-slate-500 dark:text-slate-600">
                    &copy; {{ new Date().getFullYear() }} UMRIC. All rights
                    reserved.
                </p>
                <div
                    class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-600"
                >
                    Made with
                    <span class="text-orange-500">♥</span>
                    for researchers worldwide
                </div>
            </div>
        </div>
    </footer>
</template>
