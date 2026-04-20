<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
    maxTags?: number;
    wrapperFocusClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Add keyword…',
    maxTags: 10,
    wrapperFocusClass: '',
});
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const inputEl = ref<HTMLInputElement | null>(null);
const inputValue = ref('');
const tags = ref<string[]>([]);

watch(
    () => props.modelValue,
    (val) => {
        const parsed = (val ?? '')
            .split(',')
            .map((t) => t.trim())
            .filter(Boolean);

        // only update if different to avoid loop
        if (parsed.join(',') !== tags.value.join(',')) {
            tags.value = parsed;
        }
    },
    { immediate: true },
);

watch(
    tags,
    () => {
        emit('update:modelValue', tags.value.join(', '));
    },
    { deep: true },
);

function addTag() {
    const val = inputValue.value.trim();

    if (!val) {
        return;
    }

    if (val.length > 40) {
        return;
    }

    if (tags.value.length >= props.maxTags) {
        return;
    }

    if (tags.value.map((t) => t.toLowerCase()).includes(val.toLowerCase())) {
        inputValue.value = '';

        return;
    }

    tags.value.push(val);
    inputValue.value = '';
}

function onComma(e: KeyboardEvent) {
    e.preventDefault();
    addTag();
}

function onBackspace() {
    if (inputValue.value === '' && tags.value.length > 0) {
        tags.value.pop();
    }
}

function onPaste(e: ClipboardEvent) {
    const text = e.clipboardData?.getData('text') ?? '';
    const parts = text
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);

    for (const part of parts) {
        if (tags.value.length >= props.maxTags) {
            break;
        }

        if (part.length > 40) {
            continue;
        }

        if (
            !tags.value.map((t) => t.toLowerCase()).includes(part.toLowerCase())
        ) {
            tags.value.push(part);
        }
    }
}

function removeTag(index: number) {
    tags.value.splice(index, 1);
}
</script>

<template>
    <div
        class="flex min-h-10 w-full cursor-text flex-wrap items-center gap-1.5 rounded-lg border border-input bg-background px-3 py-2 transition"
        :class="wrapperFocusClass"
        @click="inputEl?.focus()"
    >
        <!-- Rendered chips -->
        <span
            v-for="(tag, i) in tags"
            :key="i"
            class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-foreground"
        >
            {{ tag }}
            <button
                type="button"
                class="ml-0.5 text-muted-foreground hover:text-foreground"
                @click.stop="removeTag(i)"
            >
                ×
            </button>
        </span>
        <!-- Inner input (hidden when maxTags reached) -->
        <input
            v-if="tags.length < props.maxTags"
            ref="inputEl"
            v-model="inputValue"
            type="text"
            :placeholder="tags.length === 0 ? props.placeholder : ''"
            maxlength="40"
            class="min-w-24 flex-1 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
            @keydown.enter.prevent="addTag"
            @keydown.,="onComma"
            @keydown.backspace="onBackspace"
            @paste.prevent="onPaste"
        />
        <span v-else class="text-xs text-muted-foreground"
            >Max {{ props.maxTags }} tags</span
        >
    </div>
</template>
