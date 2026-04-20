<script setup lang="ts">
import {
    AlertCircle,
    CheckCircle2,
    Info,
    TriangleAlert,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAlert } from '@/composables/useAlert';

const { state, close } = useAlert();

const variantIcon = {
    info: Info,
    success: CheckCircle2,
    warning: TriangleAlert,
    danger: AlertCircle,
};

const variantIconClass: Record<string, string> = {
    info: 'text-blue-500',
    success: 'text-green-500',
    warning: 'text-amber-500',
    danger: 'text-red-500',
};

const variantButtonVariant: Record<string, 'default' | 'destructive'> = {
    info: 'default',
    success: 'default',
    warning: 'default',
    danger: 'destructive',
};
</script>

<template>
    <Dialog :open="state.open" @update:open="(v) => !v && close()">
        <DialogContent :show-close-button="false" class="sm:max-w-sm">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <component
                        :is="variantIcon[state.variant]"
                        :class="[
                            'h-5 w-5 shrink-0',
                            variantIconClass[state.variant],
                        ]"
                    />
                    <DialogTitle>{{ state.title }}</DialogTitle>
                </div>
                <DialogDescription class="pt-1 pl-8">
                    {{ state.message }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    :variant="variantButtonVariant[state.variant]"
                    class="w-full sm:w-auto"
                    @click="close"
                >
                    {{ state.confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
