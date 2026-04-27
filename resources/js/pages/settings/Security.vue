<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    KeyRound,
    Shield,
    ShieldCheck,
    ShieldOff,
    Smartphone,
} from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { edit } from '@/routes/security';
import { disable, enable } from '@/routes/two-factor';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Update password"
            description="Ensure your account is using a long, random password to stay secure"
        />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Current password"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="New password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Save password
                </Button>
            </div>
        </Form>
    </div>

    <template v-if="canManageTwoFactor">
        <Separator class="md:hidden" />
        <div class="space-y-4 pt-2 md:pt-0">
            <Heading
                variant="small"
                title="Two-factor authentication"
                description="Add a second step at sign-in with an authenticator app. Strongly recommended for admin and faculty accounts."
            />

            <Card class="overflow-hidden">
                <CardHeader
                    class="space-y-3 border-b border-border/60 bg-muted/30 py-4 sm:py-5"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Smartphone
                                    v-if="twoFactorEnabled"
                                    class="size-5"
                                />
                                <ShieldOff v-else class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <CardTitle class="text-base"
                                    >Authenticator app
                                </CardTitle>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    Time-based 6-digit codes (TOTP) from apps
                                    like Google Authenticator or Authy.
                                </p>
                            </div>
                        </div>
                        <Badge
                            :variant="
                                twoFactorEnabled ? 'success' : 'secondary'
                            "
                            class="w-fit shrink-0 sm:ml-2"
                        >
                            {{ twoFactorEnabled ? 'On' : 'Off' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4 pt-5">
                    <div v-if="!twoFactorEnabled" class="space-y-4">
                        <ul
                            class="list-inside list-disc space-y-1.5 text-sm text-muted-foreground"
                        >
                            <li>
                                After you enable, you’ll scan a QR code or enter
                                a key once
                            </li>
                            <li>
                                Each time you sign in, you’ll enter a short code
                                from the app
                            </li>
                        </ul>

                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:flex-wrap"
                        >
                            <Button
                                v-if="hasSetupData"
                                class="inline-flex h-11 w-full min-w-[12rem] items-center justify-center gap-2 sm:w-auto"
                                @click="showSetupModal = true"
                            >
                                <ShieldCheck class="size-4" />
                                Continue setup
                            </Button>
                            <Form
                                v-else
                                v-bind="enable.form()"
                                @success="showSetupModal = true"
                                #default="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    :disabled="processing"
                                    class="inline-flex h-11 w-full min-w-[12rem] items-center justify-center gap-2 sm:w-auto"
                                >
                                    <KeyRound class="size-4" />
                                    {{
                                        processing ? 'Starting…' : 'Enable 2FA'
                                    }}
                                </Button>
                            </Form>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <Alert
                            class="border-emerald-200 bg-emerald-50/80 dark:border-emerald-900/50 dark:bg-emerald-950/30"
                        >
                            <Shield
                                class="text-emerald-600 dark:text-emerald-400"
                            />
                            <AlertTitle>Protection is active</AlertTitle>
                            <AlertDescription>
                                Sign-in from a new device or browser will ask
                                for your password and a code from your
                                authenticator app. Keep recovery codes below in
                                a safe place in case you lose your phone.
                            </AlertDescription>
                        </Alert>

                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center"
                        >
                            <Form
                                v-bind="disable.form()"
                                class="w-full sm:w-auto"
                                #default="{ processing }"
                            >
                                <Button
                                    variant="outline"
                                    class="h-11 w-full border-destructive/40 text-destructive hover:bg-destructive/10 sm:w-auto"
                                    type="submit"
                                    :disabled="processing"
                                >
                                    Turn off 2FA
                                </Button>
                            </Form>
                        </div>

                        <TwoFactorRecoveryCodes />
                    </div>
                </CardContent>
            </Card>
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </template>
</template>
