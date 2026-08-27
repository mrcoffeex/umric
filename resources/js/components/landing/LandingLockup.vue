<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

withDefaults(
    defineProps<{
        inverted?: boolean;
    }>(),
    {
        inverted: false,
    },
);

const branding = useBranding();
</script>

<template>
    <span class="flex min-w-0 items-center gap-3">
        <span
            v-if="branding.logoUrl"
            class="flex h-11 w-11 shrink-0 items-center justify-center sm:h-12 sm:w-12"
        >
            <img
                :src="branding.logoUrl"
                :alt="branding.name"
                class="h-full w-full object-contain"
            />
        </span>
        <span
            v-else
            class="relative flex h-11 w-11 shrink-0 items-center justify-center bg-um-maroon sm:h-12 sm:w-12"
            aria-hidden="true"
        >
            <span class="absolute inset-y-0 left-0 w-[3px] bg-um-gold" />
            <span
                class="font-display text-[11px] font-extrabold tracking-[0.14em] text-white"
            >
                UM
            </span>
        </span>
        <span class="min-w-0 text-left leading-tight">
            <span
                class="block truncate font-display text-base font-extrabold tracking-tight uppercase"
                :class="inverted ? 'text-white' : 'text-um-maroon'"
            >
                {{ branding.name }}
            </span>
            <span
                class="block truncate text-[10px] font-semibold tracking-[0.08em] uppercase"
                :class="inverted ? 'text-um-gold' : 'text-um-body'"
            >
                Research and Innovation Center
            </span>
        </span>
    </span>
</template>
