<script setup lang="ts">
import type { TabsContentProps } from 'reka-ui';
import { TabsContent, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<TabsContentProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <TabsContent
        data-slot="tabs-content"
        v-bind="forwarded"
        :class="cn('min-h-[12rem] pt-0 outline-none', props.class)"
    >
        <slot />
    </TabsContent>
</template>
