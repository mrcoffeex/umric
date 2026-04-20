<script setup lang="ts">
import { TriangleAlert } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useConfirm } from '@/composables/useConfirm';

const { state, choose } = useConfirm();
</script>

<template>
    <Dialog :open="state.open" @update:open="(v) => !v && choose(false)">
        <DialogContent :show-close-button="false" class="sm:max-w-sm">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div
                        :class="[
                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                            state.destructive
                                ? 'bg-red-100 dark:bg-red-950/40'
                                : 'bg-amber-100 dark:bg-amber-950/40',
                        ]"
                    >
                        <TriangleAlert
                            :class="[
                                'h-4 w-4',
                                state.destructive
                                    ? 'text-red-500'
                                    : 'text-amber-500',
                            ]"
                        />
                    </div>
                    <DialogTitle>{{ state.title }}</DialogTitle>
                </div>
                <DialogDescription class="pt-1 pl-12">
                    {{ state.message }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-2">
                <Button
                    variant="outline"
                    class="flex-1 sm:flex-none"
                    @click="choose(false)"
                >
                    {{ state.cancelLabel }}
                </Button>
                <Button
                    :variant="state.destructive ? 'destructive' : 'default'"
                    class="flex-1 sm:flex-none"
                    @click="choose(true)"
                >
                    {{ state.confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
