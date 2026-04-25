<script setup lang="ts">
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Italic,
    Link2,
    List,
    ListOrdered,
    Redo2,
    Undo2,
} from 'lucide-vue-next';
import { onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        disabled?: boolean;
        inputId?: string;
    }>(),
    { disabled: false },
);

const emit = defineEmits<{
    (e: 'update:modelValue', v: string): void;
}>();

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Link.configure({
            openOnClick: false,
            autolink: true,
            linkOnPaste: true,
        }),
        Placeholder.configure({
            placeholder: 'Describe the criterion…',
        }),
    ],
    content: props.modelValue || '<p></p>',
    editable: !props.disabled,
    editorProps: {
        attributes: {
            ...(props.inputId ? { id: props.inputId } : {}),
            class: cn(
                'min-h-28 w-full max-w-none px-3 py-2 text-sm text-foreground outline-none',
                'focus-visible:ring-0',
                '[&_a]:text-primary [&_a]:underline',
                '[&_h2]:text-base [&_h2]:font-semibold',
                '[&_h2]:text-foreground',
                '[&_h3]:text-sm [&_h3]:font-semibold [&_h3]:text-foreground',
                '[&_ol]:list-decimal [&_ol]:pl-5',
                '[&_p]:my-1.5 first:[&_p]:mt-0 last:[&_p]:mb-0',
                '[&_ul]:list-disc [&_ul]:pl-5',
            ),
        },
    },
    onUpdate: ({ editor: ed }) => {
        emit('update:modelValue', ed.getHTML());
    },
});

watch(
    () => props.modelValue,
    (v) => {
        const ed = editor.value;

        if (!ed) {
            return;
        }

        const next = v || '<p></p>';

        if (next === ed.getHTML()) {
            return;
        }

        ed.commands.setContent(next, { emitUpdate: false });
    },
);

watch(
    () => props.disabled,
    (d) => {
        editor.value?.setEditable(!d);
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function setLink() {
    const ed = editor.value;

    if (!ed) {
        return;
    }

    const prev = ed.getAttributes('link').href;
    const url = window.prompt('Link URL', prev ?? 'https://');

    if (url === null) {
        return;
    }

    if (url === '') {
        ed.chain().focus().extendMarkRange('link').unsetLink().run();

        return;
    }

    ed.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-input bg-background text-sm shadow-xs"
    >
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-0.5 border-b border-border bg-muted/20 p-1.5"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0"
                :disabled="disabled || !editor.can().toggleBold()"
                :aria-pressed="editor.isActive('bold')"
                aria-label="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0"
                :disabled="disabled || !editor.can().toggleItalic()"
                :aria-pressed="editor.isActive('italic')"
                aria-label="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-4 w-4" />
            </Button>
            <div
                class="mx-0.5 h-5 w-px shrink-0 bg-border"
                aria-hidden="true"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0"
                :disabled="disabled"
                :aria-pressed="editor.isActive('bulletList')"
                aria-label="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0"
                :disabled="disabled"
                :aria-pressed="editor.isActive('orderedList')"
                aria-label="Numbered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>
            <div
                class="mx-0.5 h-5 w-px shrink-0 bg-border"
                aria-hidden="true"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0"
                :disabled="disabled"
                aria-label="Link"
                :aria-pressed="editor.isActive('link')"
                @click="setLink"
            >
                <Link2 class="h-4 w-4" />
            </Button>
            <div
                class="mx-0.5 h-5 w-px shrink-0 bg-border"
                aria-hidden="true"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0"
                :disabled="disabled || !editor.can().undo()"
                aria-label="Undo"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo2 class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0"
                :disabled="disabled || !editor.can().redo()"
                aria-label="Redo"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo2 class="h-4 w-4" />
            </Button>
        </div>
        <div :class="disabled ? 'cursor-not-allowed opacity-60' : ''">
            <EditorContent v-if="editor" :editor="editor" />
        </div>
    </div>
</template>
