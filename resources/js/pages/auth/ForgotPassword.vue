<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Key } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <!-- Heading -->
    <div class="mb-8 flex flex-col items-center gap-3 text-center">
        <div
            class="rounded-full bg-gradient-to-br from-orange-50 to-orange-100 p-3 dark:from-orange-900/20 dark:to-orange-800/20"
        >
            <Key
                class="h-6 w-6 text-orange-600 dark:text-orange-400"
                :stroke-width="2"
            />
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Reset your password
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Enter your email address and we'll send you a link to reset your
                password
            </p>
        </div>
    </div>

    <!-- Success message -->
    <div
        v-if="status"
        class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900/30 dark:bg-green-900/10"
    >
        <p class="text-sm font-medium text-green-700 dark:text-green-400">
            {{ status }}
        </p>
    </div>

    <Form v-bind="email.form()" v-slot="{ errors, processing }">
        <div class="flex flex-col gap-4">
            <!-- Email input -->
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
                    autocomplete="off"
                    placeholder="you@umindanao.edu.ph"
                    class="h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:outline-none dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                    :class="
                        errors.email
                            ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20 dark:border-red-500'
                            : 'border-gray-200 focus:border-orange-400 focus:ring-orange-400/20 dark:border-gray-600'
                    "
                />
                <InputError :message="errors.email" />
            </div>

            <!-- Submit button -->
            <button
                type="submit"
                :disabled="processing"
                class="relative mt-2 flex h-11 w-full items-center justify-center rounded-lg font-semibold text-white shadow-sm transition-all hover:shadow-md disabled:opacity-60"
                style="background: linear-gradient(135deg, #f97316, #ea580c)"
                data-test="email-password-reset-link-button"
            >
                <Spinner v-if="processing" class="mr-2" />
                {{
                    processing
                        ? 'Sending reset link...'
                        : 'Send password reset link'
                }}
            </button>

            <!-- Back to login link -->
            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                Remember your password?
                <TextLink
                    :href="login()"
                    class="font-medium text-orange-600 hover:text-orange-500 dark:text-orange-400"
                >
                    Back to sign in
                </TextLink>
            </p>
        </div>
    </Form>
</template>
