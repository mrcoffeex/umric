<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { Briefcase, GraduationCap, Mail, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useBranding } from '@/composables/useBranding';
import { login, privacy, terms } from '@/routes';
import { store } from '@/routes/register';

const page = usePage();
const branding = useBranding();
const termsError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)
            ?.terms_accepted,
);

const registerTitle = computed(() => `Register - ${branding.value.name}`);

const roleTab = ref<'student' | 'faculty'>('student');
const agreedToTerms = ref(false);

function redirectToGoogle() {
    if (!agreedToTerms.value) {
        return;
    }

    window.location.href = `/auth/google?role=${roleTab.value}`;
}

defineOptions({
    layout: {
        title: 'Create your account',
        description: '',
    },
});
</script>

<template>
    <Head :title="registerTitle" />

    <!-- Heading -->
    <div class="mb-7 text-center">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
            Create your account
        </h2>
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
            Join {{ branding.name }} to track and manage research
        </p>
    </div>

    <!-- Role toggle -->
    <div
        class="mb-5 flex rounded-full border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800"
    >
        <button
            type="button"
            @click="roleTab = 'student'"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-full py-2 text-sm font-medium transition-all duration-200"
            :class="
                roleTab === 'student'
                    ? 'bg-white text-orange-600 shadow-sm dark:bg-gray-700 dark:text-orange-400'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
            "
        >
            <GraduationCap class="h-3.5 w-3.5" />
            Student
        </button>
        <button
            type="button"
            @click="roleTab = 'faculty'"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-full py-2 text-sm font-medium transition-all duration-200"
            :class="
                roleTab === 'faculty'
                    ? 'bg-white text-teal-600 shadow-sm dark:bg-gray-700 dark:text-teal-400'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
            "
        >
            <Briefcase class="h-3.5 w-3.5" />
            Faculty
        </button>
    </div>

    <!-- Terms: required for email signup and for Google sign-up -->
    <div
        class="mb-4 flex gap-2.5 rounded-xl border border-gray-200 bg-gray-50/80 p-3.5 text-left dark:border-gray-700 dark:bg-gray-800/50"
    >
        <div class="pt-0.5">
            <input
                id="terms-acceptance"
                v-model="agreedToTerms"
                type="checkbox"
                class="h-4 w-4 cursor-pointer rounded border-gray-300 text-orange-600 focus:ring-2 focus:ring-orange-500/30 dark:border-gray-600 dark:bg-gray-800"
            />
        </div>
        <label
            for="terms-acceptance"
            class="cursor-pointer text-sm leading-snug text-gray-600 dark:text-gray-300"
        >
            I have read and agree to the
            <Link
                :href="terms.url()"
                class="font-medium text-orange-600 underline decoration-orange-200 underline-offset-2 hover:text-orange-700 dark:text-orange-400 dark:decoration-orange-800"
                target="_blank"
                rel="noopener noreferrer"
                @click.stop
            >
                Terms &amp; Conditions
            </Link>
            and
            <Link
                :href="privacy.url()"
                class="font-medium text-orange-600 underline decoration-orange-200 underline-offset-2 hover:text-orange-700 dark:text-orange-400 dark:decoration-orange-800"
                target="_blank"
                rel="noopener noreferrer"
                @click.stop
            >
                Privacy Policy
            </Link>
            .
        </label>
    </div>
    <InputError v-if="termsError" :message="termsError" class="-mb-1" />

    <!-- Google Sign Up -->
    <button
        type="button"
        :disabled="!agreedToTerms"
        :class="!agreedToTerms ? 'cursor-not-allowed opacity-50' : ''"
        @click="redirectToGoogle"
        class="flex h-11 min-h-11 w-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 enabled:hover:border-gray-300 enabled:hover:bg-gray-50 enabled:hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:enabled:hover:border-gray-600 dark:enabled:hover:bg-gray-700"
    >
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
            <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
            />
            <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
            />
        </svg>
        Continue with Google
    </button>

    <!-- Divider -->
    <div class="relative my-5 flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs tracking-wide text-gray-400 uppercase"
            >or fill in manually</span
        >
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <!-- Hidden role input -->
        <input type="hidden" name="role" :value="roleTab" />
        <input
            v-if="agreedToTerms"
            type="hidden"
            name="terms_accepted"
            value="1"
        />

        <!-- Full Name -->
        <div class="grid gap-1.5">
            <label
                for="name"
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >Full name</label
            >
            <div class="relative">
                <User
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                />
                <input
                    id="name"
                    type="text"
                    name="name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Juan dela Cruz"
                    class="h-11 w-full rounded-xl border border-gray-200 bg-white pr-3 pl-9 text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-400/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                />
            </div>
            <InputError :message="errors.name" />
        </div>

        <!-- Email -->
        <div class="grid gap-1.5">
            <label
                for="email"
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >Email address</label
            >
            <div class="relative">
                <Mail
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                />
                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="you@umindanao.edu.ph"
                    class="h-11 w-full rounded-xl border border-gray-200 bg-white pr-3 pl-9 text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-400/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <!-- Password -->
        <div class="grid gap-1.5">
            <label
                for="password"
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >Password</label
            >
            <PasswordInput
                id="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Create a strong password"
                :tabindex="5"
                class="h-11 rounded-xl border-gray-200 dark:border-gray-700"
            />
            <InputError :message="errors.password" />
        </div>

        <!-- Confirm Password -->
        <div class="grid gap-1.5">
            <label
                for="password_confirmation"
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >Confirm password</label
            >
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Repeat your password"
                :tabindex="6"
                class="h-11 rounded-xl border-gray-200 dark:border-gray-700"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <!-- Submit -->
        <Button
            type="submit"
            :tabindex="7"
            :disabled="processing || !agreedToTerms"
            class="h-11 w-full rounded-xl font-semibold text-white shadow-sm transition-all duration-300"
            :style="
                roleTab === 'faculty'
                    ? 'background: linear-gradient(135deg, #0d9488, #0f766e)'
                    : 'background: linear-gradient(135deg, #f97316, #ea580c)'
            "
        >
            <Spinner v-if="processing" />
            Create Account
        </Button>

        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Already have an account?
            <TextLink
                :href="login()"
                class="font-semibold text-orange-600 hover:text-orange-500 dark:text-orange-400"
                :tabindex="8"
            >
                Log in
            </TextLink>
        </p>
    </Form>
</template>
