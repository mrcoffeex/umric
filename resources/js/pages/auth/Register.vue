<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Briefcase,
    ChevronRight,
    GraduationCap,
    Mail,
    User,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useBranding } from '@/composables/useBranding';
import { login, privacy, terms } from '@/routes';
import { google } from '@/routes/auth';
import { store } from '@/routes/register';

type Role = 'student' | 'faculty';
type Method = 'google' | 'email';
type Step = 'role' | 'method' | 'details';

const page = usePage();
const branding = useBranding();
const termsError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)
            ?.terms_accepted,
);

const emailError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.email,
);

const registerTitle = computed(() => `Register - ${branding.value.name}`);

const step = ref<Step>('role');
const role = ref<Role | null>(null);
const method = ref<Method | null>(null);
const agreedToTerms = ref(false);

const hasFormErrors = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;

    if (!errors) {
        return false;
    }

    // Google login failures only set `email` — keep the role picker visible.
    const keys = Object.keys(errors).filter((key) => key !== 'email');

    return keys.length > 0;
});

// Return to the details step when the server sends validation errors.
watch(
    hasFormErrors,
    (hasErrors) => {
        if (hasErrors) {
            role.value ??= 'student';
            method.value = 'email';
            step.value = 'details';
        }
    },
    { immediate: true },
);

const stepIndex = computed(() => {
    if (step.value === 'role') {
        return 1;
    }

    if (step.value === 'method') {
        return 2;
    }

    return 3;
});

const heading = computed(() => {
    if (step.value === 'role') {
        return {
            title: 'Create your account',
            description: 'First, tell us what kind of account you need.',
        };
    }

    if (step.value === 'method') {
        return {
            title: 'How do you want to sign up?',
            description:
                role.value === 'faculty'
                    ? 'Faculty accounts need approval after you register.'
                    : 'Choose Google or email to continue as a student.',
        };
    }

    return {
        title:
            method.value === 'google'
                ? 'Continue with Google'
                : 'Complete your details',
        description:
            method.value === 'google'
                ? 'Accept the terms, then continue with your Google account.'
                : 'Fill in your details to finish creating your account.',
    };
});

function selectRole(next: Role) {
    role.value = next;
    step.value = 'method';
}

function selectMethod(next: Method) {
    method.value = next;
    step.value = 'details';
}

function goBack() {
    if (step.value === 'details') {
        step.value = 'method';
        agreedToTerms.value = false;

        return;
    }

    if (step.value === 'method') {
        step.value = 'role';
        method.value = null;
    }
}

function redirectToGoogle() {
    if (!agreedToTerms.value || !role.value) {
        return;
    }

    window.location.href = google.url({
        query: { role: role.value },
    });
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

    <!-- Progress -->
    <div class="mb-6">
        <div class="mb-2 flex items-center justify-between gap-2">
            <p
                class="text-[11px] font-bold tracking-[0.14em] text-um-maroon uppercase"
            >
                Step {{ stepIndex }} of 3
            </p>
            <button
                v-if="step !== 'role'"
                type="button"
                class="inline-flex min-h-9 items-center gap-1 text-sm font-medium text-gray-500 transition hover:text-um-maroon dark:text-gray-400"
                @click="goBack"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back
            </button>
        </div>
        <div class="flex gap-1.5">
            <span
                v-for="n in 3"
                :key="n"
                class="h-1 flex-1 rounded-full transition-colors"
                :class="
                    n <= stepIndex
                        ? 'bg-um-maroon'
                        : 'bg-gray-200 dark:bg-gray-700'
                "
            />
        </div>
    </div>

    <!-- Heading -->
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
            {{ heading.title }}
        </h2>
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
            {{ heading.description }}
        </p>
    </div>

    <!-- Step 1: account type -->
    <div v-if="step === 'role'" class="flex flex-col gap-3">
        <InputError
            v-if="emailError"
            :message="emailError"
            class="text-center"
        />

        <button
            type="button"
            class="group flex min-h-11 w-full items-center gap-4 rounded-[3px] border border-black/10 bg-white p-4 text-left transition hover:border-um-maroon hover:bg-um-maroon/[0.03] dark:border-gray-700 dark:bg-gray-800 dark:hover:border-um-gold"
            @click="selectRole('student')"
        >
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center bg-um-maroon text-white"
            >
                <GraduationCap class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span
                    class="block font-semibold text-gray-900 dark:text-gray-50"
                    >Student</span
                >
                <span
                    class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400"
                    >Submit and track research papers</span
                >
            </span>
            <ChevronRight
                class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:text-um-maroon dark:text-gray-600"
            />
        </button>

        <button
            type="button"
            class="group flex min-h-11 w-full items-center gap-4 rounded-[3px] border border-black/10 bg-white p-4 text-left transition hover:border-um-maroon hover:bg-um-maroon/[0.03] dark:border-gray-700 dark:bg-gray-800 dark:hover:border-um-gold"
            @click="selectRole('faculty')"
        >
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center bg-um-navy text-white"
            >
                <Briefcase class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span
                    class="block font-semibold text-gray-900 dark:text-gray-50"
                    >Faculty</span
                >
                <span
                    class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400"
                    >Advise classes and review research</span
                >
            </span>
            <ChevronRight
                class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:text-um-maroon dark:text-gray-600"
            />
        </button>

        <p class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
            Already have an account?
            <TextLink
                :href="login()"
                class="font-semibold text-primary hover:text-primary/80"
            >
                Log in
            </TextLink>
        </p>
    </div>

    <!-- Step 2: login method -->
    <div v-else-if="step === 'method'" class="flex flex-col gap-3">
        <p
            class="mb-1 text-center text-xs font-semibold tracking-wide text-um-body uppercase"
        >
            {{ role === 'faculty' ? 'Faculty' : 'Student' }} account
        </p>

        <button
            type="button"
            class="group flex min-h-11 w-full items-center gap-4 rounded-[3px] border border-black/10 bg-white p-4 text-left transition hover:border-um-maroon hover:bg-um-maroon/[0.03] dark:border-gray-700 dark:bg-gray-800 dark:hover:border-um-gold"
            @click="selectMethod('google')"
        >
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center border border-black/8 bg-white"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
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
            </span>
            <span class="min-w-0 flex-1">
                <span
                    class="block font-semibold text-gray-900 dark:text-gray-50"
                    >Continue with Google</span
                >
                <span
                    class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400"
                    >Faster sign-up with your Gmail account</span
                >
            </span>
            <ChevronRight
                class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:text-um-maroon dark:text-gray-600"
            />
        </button>

        <button
            type="button"
            class="group flex min-h-11 w-full items-center gap-4 rounded-[3px] border border-black/10 bg-white p-4 text-left transition hover:border-um-maroon hover:bg-um-maroon/[0.03] dark:border-gray-700 dark:bg-gray-800 dark:hover:border-um-gold"
            @click="selectMethod('email')"
        >
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center bg-um-gold text-white"
            >
                <Mail class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span
                    class="block font-semibold text-gray-900 dark:text-gray-50"
                    >Continue with email</span
                >
                <span
                    class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400"
                    >Create an account with email and password</span
                >
            </span>
            <ChevronRight
                class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:text-um-maroon dark:text-gray-600"
            />
        </button>
    </div>

    <!-- Step 3: terms + Google or email form -->
    <div v-else class="flex flex-col gap-4">
        <div
            class="flex flex-wrap items-center justify-center gap-2 text-xs font-semibold tracking-wide text-um-body uppercase"
        >
            <span>{{ role === 'faculty' ? 'Faculty' : 'Student' }}</span>
            <span class="text-gray-300">·</span>
            <span>{{ method === 'google' ? 'Google' : 'Email' }}</span>
        </div>

        <div
            class="flex gap-2.5 rounded-[3px] border border-gray-200 bg-gray-50/80 p-3.5 text-left dark:border-gray-700 dark:bg-gray-800/50"
        >
            <div class="pt-0.5">
                <input
                    id="terms-acceptance"
                    v-model="agreedToTerms"
                    type="checkbox"
                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary/30 dark:border-gray-600 dark:bg-gray-800"
                />
            </div>
            <label
                for="terms-acceptance"
                class="cursor-pointer text-sm leading-snug text-gray-600 dark:text-gray-300"
            >
                I have read and agree to the
                <Link
                    :href="terms.url()"
                    class="font-medium text-primary underline decoration-primary/30 underline-offset-2 hover:text-primary/80"
                    target="_blank"
                    rel="noopener noreferrer"
                    @click.stop
                >
                    Terms &amp; Conditions
                </Link>
                and
                <Link
                    :href="privacy.url()"
                    class="font-medium text-primary underline decoration-primary/30 underline-offset-2 hover:text-primary/80"
                    target="_blank"
                    rel="noopener noreferrer"
                    @click.stop
                >
                    Privacy Policy
                </Link>
                .
            </label>
        </div>
        <InputError v-if="termsError" :message="termsError" />

        <template v-if="method === 'google'">
            <button
                type="button"
                :disabled="!agreedToTerms"
                :class="!agreedToTerms ? 'cursor-not-allowed opacity-50' : ''"
                class="flex h-11 min-h-11 w-full items-center justify-center gap-3 rounded-[3px] border border-gray-200 bg-white text-sm font-medium text-gray-700 shadow-sm transition-all duration-150 enabled:hover:border-gray-300 enabled:hover:bg-gray-50 enabled:hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:enabled:hover:border-gray-600 dark:enabled:hover:bg-gray-700"
                @click="redirectToGoogle"
            >
                <svg
                    class="h-4 w-4 shrink-0"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
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
        </template>

        <Form
            v-else
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <input type="hidden" name="role" :value="role ?? 'student'" />
            <input
                v-if="agreedToTerms"
                type="hidden"
                name="terms_accepted"
                value="1"
            />

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
                        class="h-11 w-full rounded-[3px] border border-gray-200 bg-white pr-3 pl-9 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                    />
                </div>
                <InputError :message="errors.name" />
            </div>

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
                        class="h-11 w-full rounded-[3px] border border-gray-200 bg-white pr-3 pl-9 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

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
                    class="h-11 rounded-[3px] border-gray-200 dark:border-gray-700"
                />
                <InputError :message="errors.password" />
            </div>

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
                    class="h-11 rounded-[3px] border-gray-200 dark:border-gray-700"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                :disabled="processing || !agreedToTerms"
                class="h-11 w-full rounded-[3px] font-semibold"
            >
                <Spinner v-if="processing" />
                Create Account
            </Button>
        </Form>

        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Already have an account?
            <TextLink
                :href="login()"
                class="font-semibold text-primary hover:text-primary/80"
            >
                Log in
            </TextLink>
        </p>
    </div>
</template>
