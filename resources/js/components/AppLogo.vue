<script setup lang="ts">
import { FlaskConical } from 'lucide-vue-next';
import { useBranding } from '@/composables/useBranding';

const branding = useBranding();
</script>

<template>
    <div class="flex min-w-0 items-center gap-2">
        <div
            v-if="branding.logoUrl"
            class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted"
        >
            <img
                :src="branding.logoUrl"
                :alt="branding.name"
                class="size-full object-contain"
            />
        </div>
        <div
            v-else
            class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500 shadow"
        >
            <FlaskConical class="size-4 text-white" />
        </div>
        <div class="ml-1 grid min-w-0 flex-1 text-left text-sm">
            <span class="truncate font-black tracking-tight text-orange-500">{{
                branding.name
            }}</span>
            <span
                v-if="branding.tagline"
                class="truncate text-[10px] leading-tight text-muted-foreground"
                >{{ branding.tagline }}</span
            >
        </div>
    </div>
</template>
