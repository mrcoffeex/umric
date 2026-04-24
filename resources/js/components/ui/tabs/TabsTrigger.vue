<script setup lang="ts">
import type { TabsTriggerProps } from 'reka-ui';
import { TabsTrigger, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <TabsTrigger
        data-slot="tabs-trigger"
        v-bind="forwarded"
        :class="
            cn(
                'inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-md px-2.5 py-1.5 text-xs font-medium text-muted-foreground transition-all focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm',
                props.class,
            )
        "
    >
        <slot />
    </TabsTrigger>
</template>
