<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCircle2, Eye, EyeOff, Lock } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);

// Show/hide password toggle
const showPassword = ref(false);

// Password strength indicator
const password = ref('');
const passwordStrength = ref<'weak' | 'fair' | 'strong' | 'very-strong'>(
    'weak',
);

function checkPasswordStrength(pwd: string) {
    if (pwd.length === 0) {
        passwordStrength.value = 'weak';

        return;
    }

    let strength = 0;

    if (pwd.length >= 8) {
        strength++;
    }

    if (pwd.length >= 12) {
        strength++;
    }

    if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) {
        strength++;
    }

    if (/[0-9]/.test(pwd)) {
        strength++;
    }

    if (/[^a-zA-Z0-9]/.test(pwd)) {
        strength++;
    }

    if (strength <= 1) {
        passwordStrength.value = 'weak';
    } else if (strength <= 2) {
        passwordStrength.value = 'fair';
    } else if (strength <= 3) {
        passwordStrength.value = 'strong';
    } else {
        passwordStrength.value = 'very-strong';
    }
}

function updatePassword(value: string) {
    password.value = value;
    checkPasswordStrength(value);
}

const strengthColors = {
    weak: 'bg-red-500',
    fair: 'bg-orange-500',
    strong: 'bg-blue-500',
    'very-strong': 'bg-green-500',
};

const strengthLabels = {
    weak: 'Weak',
    fair: 'Fair',
    strong: 'Strong',
    'very-strong': 'Very strong',
};
</script>

<template>
    <Head title="Reset password" />

    <!-- Heading -->
    <div class="mb-8 flex flex-col items-center gap-3 text-center">
        <div
            class="rounded-full bg-gradient-to-br from-orange-50 to-orange-100 p-3 dark:from-orange-900/20 dark:to-orange-800/20"
        >
            <Lock
                class="h-6 w-6 text-orange-600 dark:text-orange-400"
                :stroke-width="2"
            />
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Create a new password
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Choose a strong password to secure your account
            </p>
        </div>
    </div>

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
    >
        <div class="flex flex-col gap-4">
            <!-- Email input (readonly) -->
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
                    readonly
                    v-model="inputEmail"
                    class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400"
                />
            </div>

            <!-- Password input -->
            <div class="grid gap-1.5">
                <label
                    for="password"
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >New password</label
                >
                <div class="relative">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        @input="
                            (e) =>
                                updatePassword(
                                    (e.target as HTMLInputElement).value,
                                )
                        "
                        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 pr-10 text-sm text-gray-900 placeholder:text-gray-400 focus:border-orange-400 focus:ring-2 focus:ring-orange-400/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400"
                        :aria-label="
                            showPassword ? 'Hide password' : 'Show password'
                        "
                        :tabindex="-1"
                    >
                        <Eye v-if="showPassword" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                    </button>
                </div>
                <InputError :message="errors.password" />

                <!-- Password strength indicator -->
                <div class="mt-2 space-y-2">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-gray-700"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-200"
                                :class="strengthColors[passwordStrength]"
                                :style="{
                                    width: `${(Object.keys(strengthColors).indexOf(passwordStrength) + 1) * 25}%`,
                                }"
                            />
                        </div>
                        <span
                            class="text-xs font-medium"
                            :class="{
                                'text-red-600 dark:text-red-400':
                                    passwordStrength === 'weak',
                                'text-orange-600 dark:text-orange-400':
                                    passwordStrength === 'fair',
                                'text-blue-600 dark:text-blue-400':
                                    passwordStrength === 'strong',
                                'text-green-600 dark:text-green-400':
                                    passwordStrength === 'very-strong',
                            }"
                        >
                            {{ strengthLabels[passwordStrength] }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Use at least 8 characters with uppercase, lowercase,
                        numbers, and symbols
                    </p>
                </div>
            </div>

            <!-- Confirm password input -->
            <div class="grid gap-1.5">
                <label
                    for="password_confirmation"
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    Confirm password
                </label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="h-10 border-gray-200 focus:border-orange-400 focus:ring-orange-400/20 dark:border-gray-600"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Submit button -->
            <button
                type="submit"
                :disabled="processing"
                class="relative mt-2 flex h-11 w-full items-center justify-center rounded-lg font-semibold text-white shadow-sm transition-all hover:shadow-md disabled:opacity-60"
                style="background: linear-gradient(135deg, #f97316, #ea580c)"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" class="mr-2" />
                {{ processing ? 'Resetting password...' : 'Reset password' }}
            </button>

            <!-- Success criteria checklist -->
            <div
                v-if="password"
                class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
            >
                <p
                    class="mb-3 text-xs font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400"
                >
                    Password requirements
                </p>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs">
                        <CheckCircle2
                            :class="{
                                'h-4 w-4 text-green-500': password.length >= 8,
                                'h-4 w-4 text-gray-300 dark:text-gray-600':
                                    password.length < 8,
                            }"
                        />
                        <span
                            :class="{
                                'text-gray-700 dark:text-gray-300':
                                    password.length >= 8,
                                'text-gray-500 dark:text-gray-500':
                                    password.length < 8,
                            }"
                        >
                            At least 8 characters
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <CheckCircle2
                            :class="{
                                'h-4 w-4 text-green-500':
                                    /[a-z]/.test(password) &&
                                    /[A-Z]/.test(password),
                                'h-4 w-4 text-gray-300 dark:text-gray-600':
                                    !/[a-z]/.test(password) ||
                                    !/[A-Z]/.test(password),
                            }"
                        />
                        <span
                            :class="{
                                'text-gray-700 dark:text-gray-300':
                                    /[a-z]/.test(password) &&
                                    /[A-Z]/.test(password),
                                'text-gray-500 dark:text-gray-500':
                                    !/[a-z]/.test(password) ||
                                    !/[A-Z]/.test(password),
                            }"
                        >
                            Mix of uppercase and lowercase letters
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <CheckCircle2
                            :class="{
                                'h-4 w-4 text-green-500': /[0-9]/.test(
                                    password,
                                ),
                                'h-4 w-4 text-gray-300 dark:text-gray-600':
                                    !/[0-9]/.test(password),
                            }"
                        />
                        <span
                            :class="{
                                'text-gray-700 dark:text-gray-300':
                                    /[0-9]/.test(password),
                                'text-gray-500 dark:text-gray-500':
                                    !/[0-9]/.test(password),
                            }"
                        >
                            At least one number
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <CheckCircle2
                            :class="{
                                'h-4 w-4 text-green-500': /[^a-zA-Z0-9]/.test(
                                    password,
                                ),
                                'h-4 w-4 text-gray-300 dark:text-gray-600':
                                    !/[^a-zA-Z0-9]/.test(password),
                            }"
                        />
                        <span
                            :class="{
                                'text-gray-700 dark:text-gray-300':
                                    /[^a-zA-Z0-9]/.test(password),
                                'text-gray-500 dark:text-gray-500':
                                    !/[^a-zA-Z0-9]/.test(password),
                            }"
                        >
                            At least one special character
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </Form>
</template>
