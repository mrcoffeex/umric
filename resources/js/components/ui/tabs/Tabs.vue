<script setup lang="ts">
import type { TabsRootEmits, TabsRootProps } from 'reka-ui';
import { TabsRoot, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<TabsRootProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<TabsRootEmits>();
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TabsRoot
        data-slot="tabs"
        v-bind="forwarded"
        :class="cn('flex w-full flex-col gap-0', props.class)"
    >
        <slot />
    </TabsRoot>
</template>
