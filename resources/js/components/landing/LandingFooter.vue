<script setup lang="ts">
import { FlaskConical, Twitter, Github, Mail } from 'lucide-vue-next'

function scrollTo(id: string) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

const footerLinks = {
    Platform: [
        { label: 'Features', section: 'features' },
        { label: 'Showcase', section: 'showcase' },
        { label: 'How It Works', section: 'how-it-works' },
    ],
    Reviews: [
        { label: 'Research Reviews', section: 'testimonials' },
    ],
    Contact: [
        { label: 'Contact Us', section: 'contact' },
    ],
    Legal: [
        { label: 'Privacy Policy', href: '#' },
        { label: 'Terms of Service', href: '#' },
        { label: 'Cookie Policy', href: '#' },
    ],
}
</script>

<template>
    <footer class="bg-slate-950 text-slate-400 pt-16 pb-8 px-5">
        <div class="max-w-7xl mx-auto">
            <!-- Top grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
                <!-- Brand column -->
                <div class="lg:col-span-2">
                    <button @click="scrollTo('hero')" class="flex items-center gap-2 mb-4 group">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-500 to-teal-500 flex items-center justify-center shadow-md group-hover:scale-105 transition-transform">
                            <FlaskConical class="w-4 h-4 text-white" />
                        </div>
                        <span class="text-xl font-black text-gradient">UMRIC</span>
                    </button>
                    <p class="text-sm leading-relaxed text-slate-500 max-w-xs">
                        Official research paper tracking system of UM Digos College &mdash;
                        from title proposal to publication.
                    </p>
                    <!-- Social -->
                    <div class="flex gap-3 mt-6">
                        <a
                            v-for="social in [
                                { icon: Twitter, label: 'Twitter', href: '#' },
                                { icon: Github, label: 'GitHub', href: '#' },
                                { icon: Mail, label: 'Email', href: '#' },
                            ]"
                            :key="social.label"
                            :href="social.href"
                            :aria-label="social.label"
                            class="w-9 h-9 rounded-lg bg-slate-800/80 border border-slate-700/60 flex items-center justify-center text-slate-400 hover:text-orange-400 hover:border-orange-600/40 hover:bg-orange-950/30 transition-all duration-200"
                        >
                            <component :is="social.icon" class="w-4 h-4" />
                        </a>
                    </div>
                </div>

                <!-- Link columns -->
                <div v-for="(links, group) in footerLinks" :key="group">
                    <h4 class="text-sm font-semibold text-slate-300 mb-4">{{ group }}</h4>
                    <ul class="space-y-3">
                        <li v-for="link in links" :key="link.label">
                            <button
                                v-if="'section' in link"
                                @click="scrollTo(link.section)"
                                class="text-sm text-slate-500 hover:text-orange-400 transition-colors"
                            >
                                {{ link.label }}
                            </button>
                            <a
                                v-else
                                :href="link.href"
                                class="text-sm text-slate-500 hover:text-orange-400 transition-colors"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="border-t border-slate-800/80 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-xs text-slate-600">
                    &copy; {{ new Date().getFullYear() }} UMRIC. All rights reserved.
                </p>
                <div class="flex items-center gap-1.5 text-xs text-slate-600">
                    Made with
                    <span class="text-orange-500">♥</span>
                    for researchers worldwide
                </div>
            </div>
        </div>
    </footer>
</template>
