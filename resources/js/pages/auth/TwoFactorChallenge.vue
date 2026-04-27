<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { KeyRound, LifeBuoy, Shield } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Use a recovery code',
            description:
                'Enter one of the one-time codes you saved when you turned on 2FA.',
            buttonText: 'Back to 6-digit app code',
        };
    }

    return {
        title: 'Check your authenticator',
        description:
            'Open your authenticator app and enter the 6-digit code for this account.',
        buttonText: 'Use a recovery code instead',
    };
});

defineOptions({
    layout: {
        title: '',
        description: '',
    },
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};

const code = ref<string>('');
</script>

<template>
    <Head title="Sign in: two-factor" />

    <div class="mb-7 text-center">
        <div
            class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-teal-100 dark:from-orange-950/50 dark:to-teal-950/50"
        >
            <Shield
                v-if="!showRecoveryInput"
                class="size-6 text-orange-600 dark:text-orange-400"
            />
            <KeyRound v-else class="size-6 text-teal-600 dark:text-teal-400" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
            {{ authConfigContent.title }}
        </h2>
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
            {{ authConfigContent.description }}
        </p>
    </div>

    <div class="space-y-5">
        <template v-if="!showRecoveryInput">
            <Form
                v-bind="store.form()"
                class="space-y-5"
                reset-on-error
                @error="code = ''"
                #default="{ errors, processing, clearErrors }"
            >
                <input type="hidden" name="code" :value="code" />
                <div class="flex flex-col items-stretch space-y-3">
                    <div class="flex w-full max-w-sm justify-center">
                        <InputOTP
                            id="otp"
                            v-model="code"
                            :maxlength="6"
                            :disabled="processing"
                            autofocus
                        >
                            <InputOTPGroup>
                                <InputOTPSlot
                                    v-for="index in 6"
                                    :key="index"
                                    :index="index - 1"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="errors.code" />
                </div>
                <Button
                    type="submit"
                    class="h-11 min-h-11 w-full font-semibold"
                    :disabled="processing || code.length < 6"
                >
                    {{ processing ? 'Verifying…' : 'Continue' }}
                </Button>
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                    <button
                        type="button"
                        class="text-orange-600 underline decoration-orange-200 underline-offset-4 transition hover:text-orange-700 dark:text-orange-400 dark:decoration-orange-800 dark:hover:text-orange-300"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <LifeBuoy class="size-3.5 shrink-0" />
                            {{ authConfigContent.buttonText }}
                        </span>
                    </button>
                </p>
            </Form>
        </template>

        <template v-else>
            <Form
                v-bind="store.form()"
                class="space-y-5"
                reset-on-error
                #default="{ errors, processing, clearErrors }"
            >
                <div class="space-y-2">
                    <Input
                        name="recovery_code"
                        type="text"
                        autocomplete="one-time-code"
                        placeholder="e.g. xxxx-xxxx-xx"
                        :autofocus="showRecoveryInput"
                        :disabled="processing"
                        class="h-12 min-h-12 font-mono text-base tracking-wider"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Type or paste the code exactly; spaces are usually fine
                        to remove.
                    </p>
                </div>
                <InputError :message="errors.recovery_code" />
                <Button
                    type="submit"
                    class="h-11 min-h-11 w-full font-semibold"
                    :disabled="processing"
                >
                    {{ processing ? 'Verifying…' : 'Continue' }}
                </Button>
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                    <button
                        type="button"
                        class="text-orange-600 underline decoration-orange-200 underline-offset-4 transition hover:text-orange-700 dark:text-orange-400 dark:decoration-orange-800 dark:hover:text-orange-300"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        {{ authConfigContent.buttonText }}
                    </button>
                </p>
            </Form>
        </template>
    </div>
</template>
