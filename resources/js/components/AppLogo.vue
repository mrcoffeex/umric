<script setup lang="ts">
import { FlaskConical } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-teal-500 shadow"
    >
        <FlaskConical class="size-4 text-white" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="truncate font-black tracking-tight text-orange-500"
            >UMRIC</span
        >
        <span class="truncate text-[10px] leading-tight text-muted-foreground"
            >UM Digos College</span
        >
    </div>
</template>
