<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
    required?: boolean;
    disabled?: boolean;
    id?: string;
}>();

const model = defineModel<string | number | null | undefined>();
</script>

<template>
    <div class="relative">
        <select
            :id="props.id"
            v-model="model"
            :required="props.required"
            :disabled="props.disabled"
            :class="
                cn(
                    'w-full appearance-none rounded-xl border border-input bg-background py-2.5 pr-9 pl-3.5 text-sm text-foreground shadow-xs transition-[color,box-shadow] outline-none',
                    'focus:border-orange-400 focus:ring-2 focus:ring-orange-400/30',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    'dark:bg-input/30 dark:hover:bg-input/50',
                    props.class,
                )
            "
        >
            <slot />
        </select>
        <ChevronDown
            class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground transition-transform duration-200 select-none"
            aria-hidden="true"
        />
    </div>
</template>
