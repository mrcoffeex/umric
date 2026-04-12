<script setup lang="ts">
import { ref } from 'vue'
import { Mail, MapPin, Clock, Send, MessageSquare, CheckCircle } from 'lucide-vue-next'
import { useScrollReveal } from '@/composables/useScrollReveal'

const { target: sectionRef, isVisible } = useScrollReveal(0.1)

const form = ref({ name: '', email: '', role: '', message: '' })
const submitted = ref(false)
const submitting = ref(false)

async function handleSubmit() {
    if (!form.value.name || !form.value.email || !form.value.message) return
    submitting.value = true
    // Simulate async send
    await new Promise((r) => setTimeout(r, 900))
    submitted.value = true
    submitting.value = false
}

const contactInfo = [
    {
        icon: Mail,
        label: 'Email',
        value: 'research@umdigos.edu.ph',
        color: 'from-orange-500 to-orange-600',
        shadow: 'shadow-orange-500/20',
    },
    {
        icon: MapPin,
        label: 'Address',
        value: 'UM Digos College, Digos City, Davao del Sur, Philippines',
        color: 'from-teal-500 to-teal-600',
        shadow: 'shadow-teal-500/20',
    },
    {
        icon: Clock,
        label: 'Office Hours',
        value: 'Monday – Friday, 8:00 AM – 5:00 PM',
        color: 'from-orange-400 to-teal-500',
        shadow: 'shadow-orange-500/20',
    },
]
</script>

<template>
    <section id="contact" class="py-28 px-5 relative overflow-hidden">
        <!-- Background -->
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-white via-orange-50/30 to-white dark:from-slate-950 dark:via-slate-900 dark:to-slate-950" />
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-orange-300/40 to-transparent dark:via-orange-700/30" />
        <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-teal-300/40 to-transparent dark:via-teal-700/30" />

        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['text-center mb-16 reveal', { visible: isVisible }]"
            >
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold mb-6
                    bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-400
                    border border-orange-200/60 dark:border-orange-800/40">
                    <MessageSquare class="w-3.5 h-3.5" />
                    Get in touch
                </div>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight mb-5">
                    <span class="text-slate-900 dark:text-white">Have a question</span>
                    <span class="text-gradient"> about UMRIC?</span>
                </h2>
                <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    Reach out to the UM Digos College Research Office and we'll get back to you as soon as we can.
                </p>
            </div>

            <div :class="['grid lg:grid-cols-5 gap-10 reveal', { visible: isVisible }]">
                <!-- Contact info -->
                <div class="lg:col-span-2 space-y-5">
                    <div
                        v-for="(info, i) in contactInfo"
                        :key="info.label"
                        class="flex items-start gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-sm"
                        :style="{ transitionDelay: `${i * 80}ms` }"
                    >
                        <div :class="['w-11 h-11 rounded-xl bg-gradient-to-br flex items-center justify-center shrink-0 shadow-md', info.color, info.shadow]">
                            <component :is="info.icon" class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">{{ info.label }}</div>
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ info.value }}</div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="p-5 rounded-2xl bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/40">
                        <p class="text-sm text-orange-700 dark:text-orange-400 leading-relaxed">
                            For account issues, please contact your department's research coordinator directly.
                        </p>
                    </div>
                </div>

                <!-- Contact form -->
                <div class="lg:col-span-3">
                    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-sm p-8">
                        <!-- Success state -->
                        <div v-if="submitted" class="flex flex-col items-center justify-center py-12 text-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-teal-100 dark:bg-teal-950/50 flex items-center justify-center">
                                <CheckCircle class="w-8 h-8 text-teal-600 dark:text-teal-400" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-200">Message sent!</h3>
                            <p class="text-slate-500 dark:text-slate-400 max-w-sm">
                                Thank you for reaching out. The UM Digos Research Office will respond within 1–2 business days.
                            </p>
                            <button
                                @click="submitted = false; form = { name: '', email: '', role: '', message: '' }"
                                class="mt-2 text-sm text-orange-600 dark:text-orange-400 hover:underline"
                            >
                                Send another message
                            </button>
                        </div>

                        <!-- Form -->
                        <form v-else @submit.prevent="handleSubmit" class="space-y-5">
                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Full Name <span class="text-orange-500">*</span></label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g. Juan dela Cruz"
                                        class="w-full px-4 py-3 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400 transition"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Address <span class="text-orange-500">*</span></label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="you@umdigos.edu.ph"
                                        class="w-full px-4 py-3 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400 transition"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Role</label>
                                <select
                                    v-model="form.role"
                                    class="w-full px-4 py-3 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400 transition"
                                >
                                    <option value="" disabled>Select your role</option>
                                    <option value="student">Student Researcher</option>
                                    <option value="faculty">Faculty / Adviser</option>
                                    <option value="admin">Department Administrator</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Message <span class="text-orange-500">*</span></label>
                                <textarea
                                    v-model="form.message"
                                    rows="5"
                                    placeholder="Describe your concern or question..."
                                    class="w-full px-4 py-3 rounded-xl text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-400/50 focus:border-orange-400 transition resize-none"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting || !form.name || !form.email || !form.message"
                                class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-base font-bold text-white
                                    bg-gradient-to-r from-orange-500 to-orange-600
                                    shadow-lg shadow-orange-500/25
                                    hover:from-orange-600 hover:to-orange-700
                                    disabled:opacity-50 disabled:cursor-not-allowed
                                    transition-all duration-200 hover:scale-[1.01] active:scale-[0.99]"
                            >
                                <Send class="w-4 h-4" />
                                {{ submitting ? 'Sending…' : 'Send Message' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
