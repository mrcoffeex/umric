<script setup lang="ts">
import type { TabsListProps } from 'reka-ui';
import { TabsList, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <TabsList
        data-slot="tabs-list"
        v-bind="forwarded"
        :class="
            cn(
                'inline-flex h-10 w-full items-center justify-start gap-0.5 overflow-x-auto rounded-lg border border-border/80 bg-muted/40 p-1 text-muted-foreground [scrollbar-width:none] [-ms-overflow-style:none] sm:flex-wrap sm:overflow-visible [&::-webkit-scrollbar]:hidden',
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
