<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Mail, MapPin, Clock, Send, CheckCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { useBranding } from '@/composables/useBranding';
import { useScrollReveal } from '@/composables/useScrollReveal';
import contact from '@/routes/contact';
import FormSelect from '../FormSelect.vue';

const { target: sectionRef, isVisible } = useScrollReveal(0.1);
const branding = useBranding();

const form = useForm({
    name: '',
    email: '',
    role: '',
    message: '',
});

const submitted = ref(false);

function handleSubmit() {
    form.clearErrors();
    form.post(contact.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            submitted.value = true;
            form.reset();
        },
    });
}

function sendAnother() {
    submitted.value = false;
    form.clearErrors();
}

const contactInfo = [
    {
        icon: Mail,
        label: 'Email',
        value: 'research@umdigos.edu.ph',
        href: 'mailto:research@umdigos.edu.ph',
    },
    {
        icon: MapPin,
        label: 'Address',
        value: 'UM Digos College, Digos City, Davao del Sur, Philippines',
    },
    {
        icon: Clock,
        label: 'Office hours',
        value: 'Monday – Friday, 8:00 AM – 5:00 PM',
    },
];

const fieldClass =
    'w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-800 placeholder-slate-400 transition focus:border-orange-400 focus:ring-2 focus:ring-orange-400/40 focus:outline-none md:text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:placeholder-slate-600';
</script>

<template>
    <section
        id="contact"
        class="scroll-mt-24 px-4 py-20 sm:px-6 sm:py-24 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="['reveal mb-12 max-w-2xl', { visible: isVisible }]"
            >
                <p
                    class="mb-3 text-sm font-semibold tracking-wide text-orange-600 uppercase dark:text-orange-400"
                >
                    Contact
                </p>
                <h2
                    class="font-display mb-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                >
                    Questions about {{ branding.name }}?
                </h2>
                <p class="text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">
                    Reach the UM Digos College Research Office and we’ll respond
                    as soon as we can.
                </p>
            </div>

            <div
                :class="[
                    'reveal grid gap-10 lg:grid-cols-5',
                    { visible: isVisible },
                ]"
            >
                <div class="space-y-6 lg:col-span-2">
                    <div
                        v-for="info in contactInfo"
                        :key="info.label"
                        class="flex gap-4"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-orange-600 dark:bg-slate-800 dark:text-orange-400"
                        >
                            <component :is="info.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <p
                                class="mb-1 text-xs font-semibold tracking-wider text-slate-400 uppercase"
                            >
                                {{ info.label }}
                            </p>
                            <a
                                v-if="info.href"
                                :href="info.href"
                                class="text-sm font-medium text-slate-800 underline-offset-2 hover:underline dark:text-slate-200"
                            >
                                {{ info.value }}
                            </a>
                            <p
                                v-else
                                class="text-sm leading-relaxed font-medium text-slate-800 dark:text-slate-200"
                            >
                                {{ info.value }}
                            </p>
                        </div>
                    </div>

                    <p
                        class="border-l-2 border-orange-400 pl-4 text-sm leading-relaxed text-slate-600 dark:text-slate-400"
                    >
                        For account issues, contact your department’s research
                        coordinator directly.
                    </p>
                </div>

                <div class="lg:col-span-3">
                    <div
                        class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <div
                            v-if="submitted"
                            class="flex flex-col items-center gap-4 py-10 text-center"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-950/50"
                            >
                                <CheckCircle
                                    class="h-7 w-7 text-teal-600 dark:text-teal-400"
                                />
                            </div>
                            <h3
                                class="font-display text-xl font-bold text-slate-900 dark:text-white"
                            >
                                Message sent
                            </h3>
                            <p class="max-w-sm text-slate-600 dark:text-slate-400">
                                Thank you. The Research Office typically
                                responds within 1–2 business days.
                            </p>
                            <button
                                type="button"
                                class="mt-1 min-h-11 text-sm font-semibold text-orange-600 hover:underline dark:text-orange-400"
                                @click="sendAnother"
                            >
                                Send another message
                            </button>
                        </div>

                        <form
                            v-else
                            class="space-y-5"
                            @submit.prevent="handleSubmit"
                        >
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                        >Full name
                                        <span class="text-orange-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g. Juan dela Cruz"
                                        :class="fieldClass"
                                        autocomplete="name"
                                    />
                                    <p
                                        v-if="form.errors.name"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                        >Email
                                        <span class="text-orange-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="you@umdigos.edu.ph"
                                        :class="fieldClass"
                                        autocomplete="email"
                                    />
                                    <p
                                        v-if="form.errors.email"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.email }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                    >Role</label
                                >
                                <FormSelect
                                    v-model="form.role"
                                    :class="fieldClass"
                                >
                                    <option value="" disabled>
                                        Select your role
                                    </option>
                                    <option value="student">
                                        Student researcher
                                    </option>
                                    <option value="faculty">
                                        Faculty / adviser
                                    </option>
                                    <option value="admin">
                                        Department administrator
                                    </option>
                                    <option value="other">Other</option>
                                </FormSelect>
                                <p
                                    v-if="form.errors.role"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.role }}
                                </p>
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
                                    placeholder="Describe your question or concern…"
                                    :class="fieldClass + ' resize-none'"
                                />
                                <p
                                    v-if="form.errors.message"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.message }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50 active:scale-[0.99]"
                                :disabled="
                                    form.processing ||
                                    !form.name ||
                                    !form.email ||
                                    !form.message
                                "
                            >
                                <Send class="h-4 w-4" />
                                {{
                                    form.processing
                                        ? 'Sending…'
                                        : 'Send message'
                                }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
