<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CheckCircle, Clock, Mail, MapPin, Send } from 'lucide-vue-next';
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
</script>

<template>
    <section
        id="contact"
        class="scroll-mt-28 bg-white px-4 py-20 sm:px-6 sm:py-24"
    >
        <div class="mx-auto max-w-7xl">
            <div
                ref="sectionRef"
                :class="['reveal mb-14 text-center', { visible: isVisible }]"
            >
                <p
                    class="mb-3 text-[11px] font-bold tracking-[0.18em] text-um-maroon uppercase"
                >
                    Contact the office
                </p>
                <h2
                    class="mb-4 font-display text-3xl font-extrabold tracking-tight text-um-heading sm:text-4xl"
                >
                    Questions about {{ branding.name }}?
                </h2>
                <p
                    class="mx-auto max-w-2xl text-base leading-relaxed text-um-body"
                >
                    Reach the UM Digos College Research Office and we will
                    respond as soon as we can.
                </p>
            </div>

            <div
                :class="[
                    'reveal grid gap-10 lg:grid-cols-5',
                    { visible: isVisible },
                ]"
            >
                <div class="space-y-4 lg:col-span-2">
                    <div
                        v-for="info in contactInfo"
                        :key="info.label"
                        class="flex items-start gap-4 border border-l-4 border-black/8 border-l-um-maroon bg-um-wash p-5"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center bg-um-maroon text-white"
                        >
                            <component :is="info.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <div
                                class="mb-1 text-[11px] font-bold tracking-[0.14em] text-um-maroon uppercase"
                            >
                                {{ info.label }}
                            </div>
                            <div
                                class="text-sm leading-relaxed font-medium text-um-heading"
                            >
                                {{ info.value }}
                            </div>
                        </div>
                    </div>
                    <p
                        class="border border-black/8 bg-um-wash p-5 text-sm leading-relaxed text-um-body"
                    >
                        For account issues, contact your department’s research
                        coordinator directly.
                    </p>
                </div>

                <div class="lg:col-span-3">
                    <div
                        class="border border-t-4 border-black/8 border-t-um-maroon bg-white p-6 shadow-sm sm:p-8"
                    >
                        <div
                            v-if="submitted"
                            class="flex flex-col items-center justify-center gap-4 py-12 text-center"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center bg-um-gold text-white"
                            >
                                <CheckCircle class="h-7 w-7" />
                            </div>
                            <h3
                                class="font-display text-xl font-bold text-um-heading"
                            >
                                Message sent
                            </h3>
                            <p class="max-w-sm text-um-body">
                                Thank you. The UM Digos Research Office will
                                respond within 1–2 business days.
                            </p>
                            <button
                                type="button"
                                class="mt-2 text-sm font-bold text-um-maroon hover:underline"
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
                                        class="mb-1.5 block text-sm font-semibold text-um-heading"
                                        >Full name
                                        <span class="text-um-maroon"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g. Juan dela Cruz"
                                        class="w-full rounded-[3px] border border-black/15 bg-um-wash px-4 py-3 text-base text-um-heading transition placeholder:text-um-body/70 focus:border-um-maroon focus:ring-2 focus:ring-um-maroon/15 focus:outline-none md:text-sm"
                                    />
                                    <p
                                        v-if="form.errors.name"
                                        class="mt-1 text-sm text-um-maroon"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-semibold text-um-heading"
                                        >Email address
                                        <span class="text-um-maroon"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="you@umdigos.edu.ph"
                                        class="w-full rounded-[3px] border border-black/15 bg-um-wash px-4 py-3 text-base text-um-heading transition placeholder:text-um-body/70 focus:border-um-maroon focus:ring-2 focus:ring-um-maroon/15 focus:outline-none md:text-sm"
                                    />
                                    <p
                                        v-if="form.errors.email"
                                        class="mt-1 text-sm text-um-maroon"
                                    >
                                        {{ form.errors.email }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-um-heading"
                                    >Role</label
                                >
                                <FormSelect
                                    v-model="form.role"
                                    class="w-full rounded-[3px] border border-black/15 bg-um-wash px-4 py-3 text-base text-um-heading transition focus:border-um-maroon focus:ring-2 focus:ring-um-maroon/15 focus:outline-none md:text-sm"
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
                                    class="mt-1 text-sm text-um-maroon"
                                >
                                    {{ form.errors.role }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-um-heading"
                                    >Message
                                    <span class="text-um-maroon">*</span></label
                                >
                                <textarea
                                    v-model="form.message"
                                    rows="5"
                                    placeholder="Describe your concern or question..."
                                    class="w-full resize-none rounded-[3px] border border-black/15 bg-um-wash px-4 py-3 text-base text-um-heading transition placeholder:text-um-body/70 focus:border-um-maroon focus:ring-2 focus:ring-um-maroon/15 focus:outline-none md:text-sm"
                                />
                                <p
                                    v-if="form.errors.message"
                                    class="mt-1 text-sm text-um-maroon"
                                >
                                    {{ form.errors.message }}
                                </p>
                            </div>

                            <button
                                type="submit"
                                :disabled="
                                    form.processing ||
                                    !form.name ||
                                    !form.email ||
                                    !form.message
                                "
                                class="flex min-h-11 w-full items-center justify-center gap-2 rounded-[3px] bg-um-gold px-6 text-base font-bold text-white transition hover:bg-um-gold-hover active:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
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
