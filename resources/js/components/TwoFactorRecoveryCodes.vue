<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import {
    Check,
    Copy,
    Eye,
    EyeOff,
    ListOrdered,
    RefreshCw,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { copy, copied } = useClipboard();
const { recoveryCodesList, fetchRecoveryCodes, errors, isRecoveryLoading } =
    useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const isRegenerating = ref(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const codesText = computed(() => recoveryCodesList.value.join('\n'));

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

const confirmRegenerate = (): void => {
    if (
        !window.confirm(
            'This will invalidate your current recovery codes. Each old code will stop working. Continue?',
        )
    ) {
        return;
    }

    isRegenerating.value = true;
    router.post(
        regenerateRecoveryCodes.url(),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRegenerating.value = false;
            },
            onSuccess: () => {
                fetchRecoveryCodes();
            },
        },
    );
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <Card class="w-full">
        <CardHeader class="space-y-1">
            <CardTitle class="flex flex-wrap items-center gap-2 text-base">
                <ListOrdered class="size-4 shrink-0 text-muted-foreground" />
                Recovery codes
            </CardTitle>
            <CardDescription>
                If you lose your phone, each of these one-time codes works
                <span class="font-medium text-foreground">once</span> when you
                can’t get a TOTP code. Store a copy in a password manager, not a
                screenshot in plain sight.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-stretch sm:justify-between"
            >
                <Button
                    :disabled="isRecoveryLoading"
                    class="h-11 min-h-11 w-full sm:w-auto"
                    @click="toggleRecoveryCodesVisibility"
                >
                    <component
                        :is="isRecoveryCodesVisible ? EyeOff : Eye"
                        class="size-4"
                    />
                    {{ isRecoveryCodesVisible ? 'Hide' : 'Show' }} codes
                </Button>

                <div
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row"
                >
                    <Button
                        type="button"
                        variant="secondary"
                        class="h-11 min-h-11 w-full sm:min-w-[10rem]"
                        :disabled="!codesText"
                        @click="copy(codesText)"
                    >
                        <Check v-if="copied" class="size-4 text-emerald-600" />
                        <Copy v-else class="size-4" />
                        {{ copied ? 'Copied' : 'Copy all' }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 min-h-11 w-full sm:min-w-[10rem]"
                        :disabled="isRegenerating"
                        @click="confirmRegenerate"
                    >
                        <RefreshCw
                            class="size-4"
                            :class="isRegenerating && 'animate-spin'"
                        />
                        Regenerate
                    </Button>
                </div>
            </div>
            <p
                v-if="isRecoveryCodesVisible"
                class="mt-2 text-xs text-amber-800 dark:text-amber-200/90"
            >
                Regenerating is immediate and cannot be undone; save the new
                list before closing this page.
            </p>
            <div
                :class="[
                    'relative overflow-hidden transition-all duration-300',
                    isRecoveryCodesVisible
                        ? 'mt-3 h-auto max-h-[480px] opacity-100'
                        : 'mt-0 h-0 max-h-0 opacity-0',
                ]"
            >
                <div v-if="errors?.length" class="pt-1">
                    <AlertError :errors="errors" />
                </div>
                <div v-else class="space-y-3 pt-1">
                    <div
                        ref="recoveryCodeSectionRef"
                        class="rounded-xl border border-border bg-muted/50 p-4"
                    >
                        <div
                            v-if="
                                !recoveryCodesList.length && isRecoveryLoading
                            "
                            class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                        >
                            <div
                                v-for="n in 8"
                                :key="n"
                                class="h-5 animate-pulse rounded bg-muted-foreground/15"
                            />
                        </div>
                        <ol
                            v-else
                            class="grid list-decimal grid-cols-1 gap-x-6 gap-y-1.5 pl-4 font-mono text-sm sm:grid-cols-2"
                        >
                            <li
                                v-for="(code, index) in recoveryCodesList"
                                :key="index"
                                class="pl-1 select-none marker:font-sans marker:text-xs marker:text-muted-foreground"
                            >
                                {{ code }}
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
