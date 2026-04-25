<script setup lang="ts">
import SignaturePad from 'signature_pad';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        disabled?: boolean;
    }>(),
    { disabled: false },
);

const emit = defineEmits<{
    change: [empty: boolean];
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let signaturePad: SignaturePad | null = null;

function emitEmptyState() {
    emit('change', signaturePad?.isEmpty() ?? true);
}

function layoutCanvas() {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const w = canvas.offsetWidth;
    const h = canvas.offsetHeight;

    if (w === 0 || h === 0) {
        return;
    }

    canvas.width = w * ratio;
    canvas.height = h * ratio;
    const ctx = canvas.getContext('2d');

    if (ctx) {
        ctx.scale(ratio, ratio);
    }
}

onMounted(() => {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    void nextTick(() => {
        layoutCanvas();
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(15, 23, 42)',
        });
        signaturePad.addEventListener('endStroke', emitEmptyState);
        emitEmptyState();
    });
});

onBeforeUnmount(() => {
    if (signaturePad) {
        signaturePad.off();
        signaturePad = null;
    }
});

watch(
    () => props.disabled,
    (d) => {
        if (signaturePad) {
            if (d) {
                signaturePad.off();
            } else {
                signaturePad.on();
            }
        }
    },
);

function clear() {
    signaturePad?.clear();
    emitEmptyState();
}

function isEmpty(): boolean {
    return signaturePad?.isEmpty() ?? true;
}

function getPngDataUrl(): string {
    if (!signaturePad || signaturePad.isEmpty()) {
        return '';
    }

    return signaturePad.toDataURL('image/png');
}

defineExpose({ clear, isEmpty, getPngDataUrl });
</script>

<template>
    <div
        class="w-full"
        :class="disabled ? 'pointer-events-none opacity-60' : ''"
    >
        <div
            class="overflow-hidden rounded-lg border border-border bg-white dark:border-zinc-700 dark:bg-zinc-950"
        >
            <canvas
                ref="canvasRef"
                class="block h-40 w-full max-w-full touch-none"
            />
        </div>
        <div class="mt-2 flex justify-end">
            <button
                type="button"
                class="text-xs font-medium text-muted-foreground underline-offset-4 hover:underline disabled:opacity-50"
                :disabled="disabled"
                @click="clear"
            >
                Clear signature
            </button>
        </div>
    </div>
</template>
