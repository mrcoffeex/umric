<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { Check, CheckCircle2, Copy, ScanLine } from 'lucide-vue-next';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useAppearance } from '@/composables/useAppearance';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import type { TwoFactorConfigContent } from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const { resolvedAppearance } = useAppearance();

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const {
    qrCodeSvg,
    manualSetupKey,
    clearSetupData,
    fetchSetupData,
    errors,
    isSetupLoading,
} = useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: "You're all set",
            description:
                'Two-factor authentication is on. You’ll need a code from your app each time you sign in. Save your recovery codes in a safe place (see Security settings).',
            buttonText: 'Done',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Confirm with a code',
            description:
                'Enter the 6-digit code from your authenticator app. Codes refresh about every 30 seconds.',
            buttonText: 'Continue',
        };
    }

    return {
        title: 'Set up your authenticator',
        description:
            'Scan the QR code with an app like Google Authenticator, Microsoft Authenticator, or Authy, or add the key manually if you can’t use a camera.',
        buttonText: 'Continue',
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const closeEnabledModal = () => {
    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

watch(
    () => isOpen.value,
    async (isOpen) => {
        if (!isOpen) {
            resetModalState();

            return;
        }

        if (props.twoFactorEnabled) {
            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent
            class="max-h-[min(90dvh,640px)] gap-0 overflow-y-auto p-0 sm:max-w-lg"
        >
            <DialogHeader
                class="space-y-3 border-b border-border p-6 pb-4 text-center"
            >
                <div
                    v-if="!twoFactorEnabled && requiresConfirmation"
                    class="flex justify-center"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-muted px-3 py-1.5 text-xs font-medium text-muted-foreground"
                    >
                        Step {{ showVerificationStep ? 2 : 1 }} of 2
                    </span>
                </div>
                <div
                    class="mx-auto w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                >
                    <div
                        class="relative overflow-hidden rounded-full border border-border p-2.5"
                        :class="
                            twoFactorEnabled ? 'bg-emerald-500/10' : 'bg-muted'
                        "
                    >
                        <div
                            v-if="!twoFactorEnabled"
                            class="absolute inset-0 grid grid-cols-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`col-${i}`"
                                class="border-r border-border last:border-r-0"
                            />
                        </div>
                        <div
                            v-if="!twoFactorEnabled"
                            class="absolute inset-0 grid grid-rows-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`row-${i}`"
                                class="border-b border-border last:border-b-0"
                            />
                        </div>
                        <CheckCircle2
                            v-if="twoFactorEnabled"
                            class="relative z-20 mx-auto size-6 text-emerald-600"
                        />
                        <ScanLine
                            v-else
                            class="relative z-20 size-6 text-foreground"
                        />
                    </div>
                </div>
                <DialogTitle class="text-xl text-balance">{{
                    modalConfig.title
                }}</DialogTitle>
                <DialogDescription
                    class="px-0 text-center text-sm leading-relaxed text-balance"
                >
                    {{ modalConfig.description }}
                </DialogDescription>
            </DialogHeader>

            <div
                class="relative flex w-full flex-col items-stretch space-y-5 px-6 py-5"
            >
                <template v-if="twoFactorEnabled && !showVerificationStep">
                    <p class="text-center text-sm text-muted-foreground">
                        You can sign out and sign in again to confirm everything
                        works, or go back to your security settings.
                    </p>
                    <Button class="h-11 w-full" @click="closeEnabledModal">
                        {{ modalConfig.buttonText }}
                    </Button>
                </template>

                <template v-else-if="!showVerificationStep">
                    <AlertError v-if="errors?.length" :errors="errors" />
                    <div
                        v-else-if="isSetupLoading"
                        class="flex min-h-[220px] flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/30 py-10"
                    >
                        <Spinner class="size-8 text-muted-foreground" />
                        <p class="text-sm text-muted-foreground">
                            Preparing your setup key and QR code…
                        </p>
                    </div>
                    <template v-else>
                        <div class="space-y-3">
                            <p
                                class="text-center text-xs font-medium text-muted-foreground"
                            >
                                Scan with your phone’s camera in the
                                authenticator app
                            </p>
                            <div
                                class="relative mx-auto flex w-full max-w-[220px] items-center justify-center"
                            >
                                <div
                                    class="relative aspect-square w-full overflow-hidden rounded-xl border border-border bg-white shadow-sm"
                                >
                                    <div
                                        v-if="!qrCodeSvg"
                                        class="absolute inset-0 z-10 flex items-center justify-center bg-muted/80"
                                    >
                                        <Spinner class="size-6" />
                                    </div>
                                    <div v-else class="p-3">
                                        <div
                                            v-html="qrCodeSvg"
                                            class="flex aspect-square size-full items-center justify-center"
                                            :style="{
                                                filter:
                                                    resolvedAppearance ===
                                                    'dark'
                                                        ? 'invert(1) brightness(1.5)'
                                                        : undefined,
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="relative flex w-full items-center justify-center py-1"
                        >
                            <div
                                class="absolute inset-0 top-1/2 h-px w-full bg-border"
                            />
                            <span
                                class="relative bg-card px-3 text-xs font-medium text-muted-foreground"
                            >
                                Can’t scan? Use a setup key
                            </span>
                        </div>

                        <div class="w-full">
                            <label
                                for="twofactor-manual-key"
                                class="mb-1.5 block text-left text-xs font-medium text-muted-foreground"
                            >
                                Secret key (for manual entry)
                            </label>
                            <div
                                class="flex min-h-11 w-full items-stretch overflow-hidden rounded-lg border border-border"
                            >
                                <template v-if="manualSetupKey">
                                    <input
                                        id="twofactor-manual-key"
                                        type="text"
                                        readonly
                                        :value="manualSetupKey"
                                        class="min-w-0 flex-1 bg-background px-3 py-2.5 font-mono text-sm leading-tight break-all text-foreground"
                                        tabindex="-1"
                                    />
                                    <button
                                        type="button"
                                        :aria-label="
                                            copied
                                                ? 'Copied'
                                                : 'Copy secret key to clipboard'
                                        "
                                        @click="copy(manualSetupKey || '')"
                                        class="inline-flex min-w-11 shrink-0 items-center justify-center border-l border-border bg-muted/40 px-3 transition-colors hover:bg-muted"
                                    >
                                        <Check
                                            v-if="copied"
                                            class="size-4 text-emerald-600"
                                            aria-hidden="true"
                                        />
                                        <Copy
                                            v-else
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="flex w-full">
                            <Button
                                class="h-11 min-h-11 w-full"
                                :disabled="!qrCodeSvg || isSetupLoading"
                                @click="handleModalNextStep"
                            >
                                {{
                                    requiresConfirmation
                                        ? 'Continue to verification'
                                        : modalConfig.buttonText
                                }}
                            </Button>
                        </div>
                    </template>
                </template>

                <template v-else>
                    <Form
                        v-bind="confirm.form()"
                        error-bag="confirmTwoFactorAuthentication"
                        reset-on-error
                        @finish="code = ''"
                        @success="isOpen = false"
                        v-slot="{ errors, processing }"
                    >
                        <input type="hidden" name="code" :value="code" />
                        <div
                            ref="pinInputContainerRef"
                            class="relative w-full space-y-3"
                        >
                            <div
                                class="flex w-full flex-col items-center justify-center space-y-3 py-2"
                            >
                                <InputOTP
                                    id="otp"
                                    v-model="code"
                                    :maxlength="6"
                                    :disabled="processing"
                                >
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="index in 6"
                                            :key="index"
                                            :index="index - 1"
                                        />
                                    </InputOTPGroup>
                                </InputOTP>
                                <InputError :message="errors?.code" />
                            </div>

                            <div
                                class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:gap-3"
                            >
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-11 min-h-11 flex-1"
                                    @click="showVerificationStep = false"
                                    :disabled="processing"
                                >
                                    Back
                                </Button>
                                <Button
                                    type="submit"
                                    class="h-11 min-h-11 flex-1"
                                    :disabled="processing || code.length < 6"
                                >
                                    Verify &amp; enable
                                </Button>
                            </div>
                        </div>
                    </Form>
                </template>
            </div>
        </DialogContent>
    </Dialog>
</template>
