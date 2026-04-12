<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head } from '@inertiajs/vue3';
import { Briefcase, GraduationCap } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Sign in',
        description: '',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const roleTab = ref<'student' | 'faculty'>('student');

function redirectToGoogle() {
    window.location.href = `/auth/google?role=${roleTab.value}`;
}
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 rounded-lg px-4 py-3 text-sm font-medium"
        :class="
            status.includes('pending') || status.includes('approval')
                ? 'border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300'
                : 'text-center text-green-600'
        "
    >
        {{ status }}
    </div>

    <!-- Role toggle -->
    <div
        class="mb-6 flex rounded-full border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800"
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

    <!-- Contextual heading -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ roleTab === 'student' ? 'Student Sign In' : 'Faculty Sign In' }}
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{
                roleTab === 'student'
                    ? 'Access your research dashboard and track submissions'
                    : 'Manage research papers and student submissions'
            }}
        </p>
    </div>

    <!-- Google Sign In -->
    <button
        type="button"
        @click="redirectToGoogle"
        class="flex h-11 w-full items-center justify-center gap-3 rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-700 transition-all hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-600"
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

    <!-- OR divider -->
    <div class="relative my-5 flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs tracking-wide text-gray-400 uppercase">or</span>
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <div class="grid gap-1.5">
            <label
                for="email"
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >Email address</label
            >
            <input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                :tabindex="1"
                autocomplete="email"
                placeholder="you@umindanao.edu.ph"
                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-400/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-1.5">
            <div class="flex items-center justify-between">
                <label
                    for="password"
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Password</label
                >
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-xs text-orange-600 hover:text-orange-500"
                    :tabindex="5"
                >
                    Forgot password?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox id="remember" name="remember" :tabindex="3" />
            <label
                for="remember"
                class="cursor-pointer text-sm text-gray-600 dark:text-gray-400"
                >Remember me</label
            >
        </div>

        <Button
            type="submit"
            class="w-full font-semibold text-white"
            style="background: linear-gradient(135deg, #f97316, #ea580c)"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            Sign In
        </Button>

        <p
            v-if="canRegister"
            class="text-center text-sm text-gray-500 dark:text-gray-400"
        >
            Don't have an account?
            <TextLink
                :href="register()"
                :tabindex="5"
                class="font-medium text-orange-600 hover:text-orange-500"
                >Sign up</TextLink
            >
        </p>
    </Form>
</template>
