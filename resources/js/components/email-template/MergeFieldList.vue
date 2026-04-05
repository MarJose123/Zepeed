<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { MergeField } from "@/types/email-template";

const props = defineProps<{
    items: MergeField[];
    command: (item: MergeField) => void;
}>();

const selectedIndex = ref(0);

// Reset when list changes (user is typing to filter)
watch(
    () => props.items,
    () => {
        selectedIndex.value = 0;
    },
    { immediate: true },
);

const onKeyDown = (event: KeyboardEvent): boolean => {
    if (props.items.length === 0) return false;

    if (event.key === "ArrowDown") {
        selectedIndex.value = (selectedIndex.value + 1) % props.items.length;
        scrollToSelected();

        return true;
    }

    if (event.key === "ArrowUp") {
        selectedIndex.value =
            (selectedIndex.value - 1 + props.items.length) % props.items.length;
        scrollToSelected();

        return true;
    }

    if (event.key === "Enter") {
        select(selectedIndex.value);

        return true;
    }

    return false;
};

const select = (index: number) => {
    const item = props.items[index];

    if (item) props.command(item);
};

const listEl = ref<HTMLDivElement | null>(null);

const scrollToSelected = () => {
    const el =
        listEl.value?.querySelectorAll("[data-item]")[selectedIndex.value];
    el?.scrollIntoView({ block: "nearest" });
};

// Group items by their group property
const grouped = computed(() => {
    const map = new Map<string, MergeField[]>();

    for (const f of props.items) {
        if (!map.has(f.group)) map.set(f.group, []);

        map.get(f.group)!.push(f);
    }

    return map;
});

defineExpose({ onKeyDown });
</script>

<template>
    <div
        class="border-border bg-background w-72 overflow-hidden rounded-xl border shadow-xl"
    >
        <!-- Header -->
        <div
            class="bg-muted border-border flex items-center gap-2 border-b px-3 py-2"
        >
            <svg
                class="text-muted-foreground h-3.5 w-3.5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <span class="text-muted-foreground text-xs">
                Merge fields
                <span v-if="items.length < 14" class="ml-1 opacity-60">
                    — {{ items.length }} match{{
                        items.length === 1 ? "" : "es"
                    }}
                </span>
            </span>
        </div>

        <!-- Scrollable item list -->
        <div ref="listEl" class="max-h-64 overflow-y-auto">
            <div
                v-if="items.length === 0"
                class="text-muted-foreground px-3 py-4 text-center text-xs"
            >
                No fields match
            </div>

            <template v-else>
                <template v-for="[group, groupFields] in grouped" :key="group">
                    <div
                        class="bg-muted/60 border-border border-b px-3 py-1 text-[9px] font-semibold uppercase tracking-widest text-muted-foreground"
                    >
                        {{ group }}
                    </div>
                    <button
                        v-for="field in groupFields"
                        :key="field.tag"
                        data-item
                        type="button"
                        class="border-border flex w-full items-center justify-between border-b px-3 py-2 text-left transition-colors last:border-0 focus:outline-none"
                        :class="
                            items.indexOf(field) === selectedIndex
                                ? 'bg-primary/5'
                                : 'hover:bg-muted/50'
                        "
                        @mouseenter="selectedIndex = items.indexOf(field)"
                        @mousedown.prevent="select(items.indexOf(field))"
                    >
                        <div class="min-w-0">
                            <div
                                class="text-xs font-medium"
                                :class="
                                    items.indexOf(field) === selectedIndex
                                        ? 'text-primary'
                                        : 'text-foreground'
                                "
                            >
                                {{ field.name }}
                            </div>
                            <div
                                class="text-muted-foreground mt-0.5 truncate text-[10px]"
                            >
                                {{ field.desc }}
                            </div>
                        </div>
                        <code
                            class="ml-2 shrink-0 rounded px-1.5 py-0.5 text-[10px]"
                            :class="
                                items.indexOf(field) === selectedIndex
                                    ? 'bg-primary/10 text-primary'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{ field.tag.replace(/[{}$ ]/g, "").trim() }}
                        </code>
                    </button>
                </template>
            </template>
        </div>

        <!-- Footer hints -->
        <div
            class="bg-muted border-border flex items-center gap-3 border-t px-3 py-1.5"
        >
            <span
                v-for="hint in [
                    ['↑↓', 'navigate'],
                    ['↵', 'insert'],
                    ['Esc', 'close'],
                ]"
                :key="hint[0]"
                class="flex items-center gap-1 text-[10px] text-muted-foreground"
            >
                <kbd
                    class="border-border bg-background rounded border px-1 font-mono text-[9px]"
                >
                    {{ hint[0] }}
                </kbd>
                {{ hint[1] }}
            </span>
        </div>
    </div>
</template>
