<script setup lang="ts">
import Placeholder from "@tiptap/extension-placeholder";
import StarterKit from "@tiptap/starter-kit";
import { EditorContent, useEditor } from "@tiptap/vue-3";
import {
    Bold,
    Italic,
    List,
    ListOrdered,
    Minus,
    Undo,
    Redo,
    Heading1,
    Heading2,
} from "lucide-vue-next";
import { watch } from "vue";
import { useMergeFieldSuggestion } from "@/components/email-template/useMergeFieldSuggestion";
import { Button } from "@/components/ui/button";
import { Kbd } from "@/components/ui/kbd";
import { MergeField } from "@/extensions/MergeField";
import type { MergeField as MergeFieldType } from "@/types/email-template";

const props = defineProps<{
    modelValue: string;
    mergeFields: MergeFieldType[];
    placeholder?: string;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: string];
}>();

const editor = useEditor({
    content: props.modelValue || "<p></p>",
    extensions: [
        StarterKit.configure({}),
        Placeholder.configure({
            placeholder:
                props.placeholder ??
                "Write your email body here… type # to insert a merge field",
        }),
        MergeField.configure({
            HTMLAttributes: { class: "merge-field-pill" },
            suggestion: useMergeFieldSuggestion(props.mergeFields),
        }),
    ],
    onUpdate({ editor }) {
        emit("update:modelValue", editor.getHTML());
    },
});

// Sync content when switching templates
watch(
    () => props.modelValue,
    (val) => {
        if (!editor.value) return;

        const current = editor.value.getHTML();

        if (val !== current) {
            editor.value.commands.setContent(val ?? "<p></p>", undefined);
        }
    },
);
</script>

<template>
    <div class="border-border flex flex-col overflow-hidden rounded-lg border">
        <!-- Toolbar -->
        <div
            v-if="editor"
            class="border-border bg-muted/40 flex flex-wrap items-center gap-0.5 border-b px-2 py-1.5"
        >
            <!-- Bold -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="{ 'bg-accent': editor.isActive('bold') }"
                @mousedown.prevent="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="h-3.5 w-3.5" />
            </Button>

            <!-- Italic -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="{ 'bg-accent': editor.isActive('italic') }"
                @mousedown.prevent="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-3.5 w-3.5" />
            </Button>

            <div class="bg-border mx-1 h-4 w-px" />

            <!-- H1 -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="{
                    'bg-accent': editor.isActive('heading', { level: 1 }),
                }"
                @mousedown.prevent="
                    editor.chain().focus().toggleHeading({ level: 1 }).run()
                "
            >
                <Heading1 class="h-3.5 w-3.5" />
            </Button>

            <!-- H2 -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="{
                    'bg-accent': editor.isActive('heading', { level: 2 }),
                }"
                @mousedown.prevent="
                    editor.chain().focus().toggleHeading({ level: 2 }).run()
                "
            >
                <Heading2 class="h-3.5 w-3.5" />
            </Button>

            <div class="bg-border mx-1 h-4 w-px" />

            <!-- Bullet list -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="{ 'bg-accent': editor.isActive('bulletList') }"
                @mousedown.prevent="
                    editor.chain().focus().toggleBulletList().run()
                "
            >
                <List class="h-3.5 w-3.5" />
            </Button>

            <!-- Ordered list -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :class="{ 'bg-accent': editor.isActive('orderedList') }"
                @mousedown.prevent="
                    editor.chain().focus().toggleOrderedList().run()
                "
            >
                <ListOrdered class="h-3.5 w-3.5" />
            </Button>

            <!-- Horizontal rule -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                @mousedown.prevent="
                    editor.chain().focus().setHorizontalRule().run()
                "
            >
                <Minus class="h-3.5 w-3.5" />
            </Button>

            <div class="bg-border mx-1 h-4 w-px" />

            <!-- Undo -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :disabled="!editor.can().undo()"
                @mousedown.prevent="editor.chain().focus().undo().run()"
            >
                <Undo class="h-3.5 w-3.5" />
            </Button>

            <!-- Redo -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                :disabled="!editor.can().redo()"
                @mousedown.prevent="editor.chain().focus().redo().run()"
            >
                <Redo class="h-3.5 w-3.5" />
            </Button>

            <!-- # hint -->
            <div class="ml-auto flex items-center gap-1">
                <Kbd
                    class="border-border bg-background text-muted-foreground rounded border px-1.5 py-0.5 font-mono text-[10px]"
                >
                    #
                </Kbd>
                <span class="text-muted-foreground text-[10px]"
                    >to insert merge field</span
                >
            </div>
        </div>

        <!-- Editor content -->
        <EditorContent :editor="editor" class="flex-1" />
    </div>
</template>

<style>
.tiptap {
    min-height: 260px;
    padding: 12px 14px;
    outline: none;
    font-size: 13px;
    line-height: 1.7;
}

/* Placeholder */
.tiptap p.is-editor-empty:first-child::before {
    color: var(--muted-foreground, #888);
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

/* Lists */
.tiptap ul {
    list-style-type: disc;
    padding-left: 1.25rem;
    margin: 0.5rem 0;
}
.tiptap ol {
    list-style-type: decimal;
    padding-left: 1.25rem;
    margin: 0.5rem 0;
}
.tiptap li {
    margin: 0.2rem 0;
}
.tiptap li > p {
    margin: 0;
}

/* Headings */
.tiptap h1 {
    font-size: 1.4rem;
    font-weight: 600;
    margin: 0.75rem 0 0.4rem;
}
.tiptap h2 {
    font-size: 1.15rem;
    font-weight: 600;
    margin: 0.6rem 0 0.3rem;
}
.tiptap h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0.5rem 0 0.25rem;
}

/* HR */
.tiptap hr {
    border: none;
    border-top: 1px solid var(--border, #e5e7eb);
    margin: 1rem 0;
}

/* Bold / Italic */
.tiptap strong {
    font-weight: 600;
}
.tiptap em {
    font-style: italic;
}

/* Merge field pill */
.merge-field-pill {
    display: inline-flex;
    align-items: center;
    background: oklch(0.646 0.222 41.116 / 0.1);
    color: oklch(0.646 0.222 41.116);
    border: 1px solid oklch(0.646 0.222 41.116 / 0.3);
    border-radius: 4px;
    padding: 1px 6px;
    font-family: var(--font-mono, monospace), serif;
    font-size: 11px;
    line-height: 1.4;
    white-space: nowrap;
    cursor: default;
    user-select: none;
    font-weight: 500;
}
</style>
