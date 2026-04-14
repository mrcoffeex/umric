<script setup lang="ts">
import { computed, ref } from 'vue';

interface Option {
    value: number;
    label: string;
}

interface Props {
    modelValue: number[];
    options: Option[];
    placeholder?: string;
    searchPlaceholder?: string;
    checkboxAccentClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'None selected',
    searchPlaceholder: 'Search…',
    checkboxAccentClass: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const query = ref('');

const filteredOptions = computed(() =>
    query.value.trim() === ''
        ? props.options
        : props.options.filter((o) =>
              o.label.toLowerCase().includes(query.value.toLowerCase()),
          ),
);

function toggle(value: number) {
    const current = [...props.modelValue];
    const idx = current.indexOf(value);

    if (idx === -1) {
        current.push(value);
    } else {
        current.splice(idx, 1);
    }

    emit('update:modelValue', current);
}
</script>

<template>
    <div class="rounded-lg border border-input bg-background">
        <div class="border-b border-border px-3 py-2">
            <input
                v-model="query"
                type="text"
                :placeholder="searchPlaceholder"
                class="w-full bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
            />
        </div>
        <div class="max-h-44 overflow-y-auto">
            <label
                v-for="option in filteredOptions"
                :key="option.value"
                class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm transition hover:bg-muted"
            >
                <input
                    type="checkbox"
                    :value="option.value"
                    :checked="modelValue.includes(option.value)"
                    class="h-4 w-4 rounded"
                    :class="checkboxAccentClass"
                    @change="toggle(option.value)"
                />
                <span class="text-foreground">{{ option.label }}</span>
            </label>
            <p
                v-if="filteredOptions.length === 0"
                class="px-3 py-2 text-sm text-muted-foreground"
            >
                {{
                    query.trim() ? 'No results found.' : 'No options available.'
                }}
            </p>
        </div>
    </div>
</template>
