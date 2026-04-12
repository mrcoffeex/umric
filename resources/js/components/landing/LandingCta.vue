<script setup lang="ts">
import {
    Mail,
    MapPin,
    Clock,
    Send,
    MessageSquare,
    CheckCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);

const form = ref({ name: '', email: '', role: '', message: '' });
const submitted = ref(false);
const submitting = ref(false);

async function handleSubmit() {
    if (!form.value.name || !form.value.email || !form.value.message) {
        return;
    }

    submitting.value = true;
    // Simulate async send
    await new Promise((r) => setTimeout(r, 900));
    submitted.value = true;
    submitting.value = false;
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
];
</script>

<template>
    <section
        id="contact"
        class="relative overflow-hidden px-4 py-16 sm:px-6 sm:py-28"
    >
        <!-- Background -->
        <div
            class="absolute inset-0 -z-10 bg-gradient-to-b from-white via-orange-50/30 to-white dark:from-slate-950 dark:via-slate-900 dark:to-slate-950"
        />
        <div
            class="absolute top-0 right-0 left-0 h-px bg-gradient-to-r from-transparent via-orange-300/40 to-transparent dark:via-orange-700/30"
        />
        <div
            class="absolute right-0 bottom-0 left-0 h-px bg-gradient-to-r from-transparent via-teal-300/40 to-transparent dark:via-teal-700/30"
        />

        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div
                ref="sectionRef"
                :class="['reveal mb-16 text-center', { visible: isVisible }]"
            >
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-orange-200/60 bg-orange-100 px-4 py-1.5 text-sm font-semibold text-orange-700 dark:border-orange-800/40 dark:bg-orange-950/50 dark:text-orange-400"
                >
                    <MessageSquare class="h-3.5 w-3.5" />
                    Get in touch
                </div>
                <h2 class="mb-5 text-4xl font-black tracking-tight sm:text-5xl">
                    <span class="text-slate-900 dark:text-white"
                        >Have a question</span
                    >
                    <span class="text-gradient"> about UMRIC?</span>
                </h2>
                <p
                    class="mx-auto max-w-2xl text-lg leading-relaxed text-slate-500 dark:text-slate-400"
                >
                    Reach out to the UM Digos College Research Office and we'll
                    get back to you as soon as we can.
                </p>
            </div>

            <div
                :class="[
                    'reveal grid gap-10 lg:grid-cols-5',
                    { visible: isVisible },
                ]"
            >
                <!-- Contact info -->
                <div class="space-y-5 lg:col-span-2">
                    <div
                        v-for="(info, i) in contactInfo"
                        :key="info.label"
                        class="flex items-start gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800/80 dark:bg-slate-900"
                        :style="{ transitionDelay: `${i * 80}ms` }"
                    >
                        <div
                            :class="[
                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br shadow-md',
                                info.color,
                                info.shadow,
                            ]"
                        >
                            <component
                                :is="info.icon"
                                class="h-5 w-5 text-white"
                            />
                        </div>
                        <div>
                            <div
                                class="mb-1 text-xs font-semibold tracking-wider text-slate-400 uppercase dark:text-slate-500"
                            >
                                {{ info.label }}
                            </div>
                            <div
                                class="text-sm leading-relaxed font-medium text-slate-700 dark:text-slate-300"
                            >
                                {{ info.value }}
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div
                        class="rounded-2xl border border-orange-100 bg-orange-50 p-5 dark:border-orange-900/40 dark:bg-orange-950/20"
                    >
                        <p
                            class="text-sm leading-relaxed text-orange-700 dark:text-orange-400"
                        >
                            For account issues, please contact your department's
                            research coordinator directly.
                        </p>
                    </div>
                </div>

                <!-- Contact form -->
                <div class="lg:col-span-3">
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-sm dark:border-slate-800/80 dark:bg-slate-900"
                    >
                        <!-- Success state -->
                        <div
                            v-if="submitted"
                            class="flex flex-col items-center justify-center gap-4 py-12 text-center"
                        >
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-950/50"
                            >
                                <CheckCircle
                                    class="h-8 w-8 text-teal-600 dark:text-teal-400"
                                />
                            </div>
                            <h3
                                class="text-xl font-bold text-slate-800 dark:text-slate-200"
                            >
                                Message sent!
                            </h3>
                            <p
                                class="max-w-sm text-slate-500 dark:text-slate-400"
                            >
                                Thank you for reaching out. The UM Digos
                                Research Office will respond within 1–2 business
                                days.
                            </p>
                            <button
                                @click="
                                    submitted = false;
                                    form = {
                                        name: '',
                                        email: '',
                                        role: '',
                                        message: '',
                                    };
                                "
                                class="mt-2 text-sm text-orange-600 hover:underline dark:text-orange-400"
                            >
                                Send another message
                            </button>
                        </div>

                        <!-- Form -->
                        <form
                            v-else
                            @submit.prevent="handleSubmit"
                            class="space-y-5"
                        >
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                        >Full Name
                                        <span class="text-orange-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g. Juan dela Cruz"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                        >Email Address
                                        <span class="text-orange-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="you@umdigos.edu.ph"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >Role</label
                                >
                                <select
                                    v-model="form.role"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <option value="" disabled>
                                        Select your role
                                    </option>
                                    <option value="student">
                                        Student Researcher
                                    </option>
                                    <option value="faculty">
                                        Faculty / Adviser
                                    </option>
                                    <option value="admin">
                                        Department Administrator
                                    </option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >Message
                                    <span class="text-orange-500"
                                        >*</span
                                    ></label
                                >
                                <textarea
                                    v-model="form.message"
                                    rows="5"
                                    placeholder="Describe your concern or question..."
                                    class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-orange-400 focus:ring-2 focus:ring-orange-400/50 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-600"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="
                                    submitting ||
                                    !form.name ||
                                    !form.email ||
                                    !form.message
                                "
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3.5 text-base font-bold text-white shadow-lg shadow-orange-500/25 transition-all duration-200 hover:scale-[1.01] hover:bg-orange-600 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Send class="h-4 w-4" />
                                {{ submitting ? 'Sending…' : 'Send Message' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
