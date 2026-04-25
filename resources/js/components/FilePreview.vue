<script setup lang="ts">
import { Upload, FileText, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Props {
    file: File | null;
    accept?: string;
    hoverBorderClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    accept: '.pdf,application/pdf',
    hoverBorderClass: '',
});

const emit = defineEmits<{
    (e: 'update:file', value: File | null): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);

const formattedSize = computed(() => {
    if (!props.file) {
        return '';
    }

    const size = props.file.size;

    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(0)} KB`;
    }

    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
});

function handleChange(event: Event) {
    const f = (event.target as HTMLInputElement).files?.[0];

    if (f) {
        emit('update:file', f);
    }
}

function handleDrop(event: DragEvent) {
    const f = event.dataTransfer?.files?.[0];

    if (f) {
        emit('update:file', f);
    }
}

function clearFile() {
    emit('update:file', null);

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}
</script>

<template>
    <div>
        <!-- Hidden input (always in DOM) -->
        <input
            ref="fileInput"
            type="file"
            class="hidden"
            :accept="accept"
            @change="handleChange"
        />

        <!-- State 1 — No file selected -->
        <div
            v-if="!file"
            class="cursor-pointer rounded-xl border-2 border-dashed border-border p-8 text-center transition-colors"
            :class="hoverBorderClass"
            @click="fileInput?.click()"
            @dragover.prevent
            @drop.prevent="handleDrop"
        >
            <Upload class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">
                Drag and drop your file here, or click to browse
            </p>
            <p class="mt-1 text-xs text-muted-foreground">PDF only</p>
        </div>

        <!-- State 2 — File selected -->
        <div
            v-else
            class="flex items-center gap-3 rounded-xl border border-border bg-muted/50 p-4"
        >
            <FileText class="h-10 w-10 shrink-0 text-red-500" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-foreground">
                    {{ file.name }}
                </p>
                <p class="text-xs text-muted-foreground">{{ formattedSize }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="text-xs font-medium text-muted-foreground transition hover:text-foreground"
                    @click="fileInput?.click()"
                >
                    Change
                </button>
                <button
                    type="button"
                    class="text-muted-foreground transition hover:text-red-500"
                    @click="clearFile"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
