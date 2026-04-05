<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import { Kbd } from "@/components/ui/kbd";
import type { MergeField } from "@/types/email-template";

const props = defineProps<{
    fields: MergeField[];
    visible: boolean;
    /** Position hint so parent can pass computed top/left */
    top?: number;
    left?: number;
}>();

const emit = defineEmits<{
    insert: [tag: string];
    close: [];
}>();

const query = ref("");
const activeIndex = ref(0);
const searchInput = ref<HTMLInputElement | null>(null);

// Focus search when opened
watch(
    () => props.visible,
    async (v) => {
        if (v) {
            query.value = "";
            activeIndex.value = 0;
            await nextTick();
            searchInput.value?.focus();
        }
    },
);

const filtered = computed(() => {
    if (!query.value) {
        return props.fields;
    }

    const q = query.value.toLowerCase();

    return props.fields.filter(
        (f) =>
            f.name.toLowerCase().includes(q) || f.tag.toLowerCase().includes(q),
    );
});

// Flat list used for keyboard navigation — skips group headers
const flatItems = computed(() => filtered.value);

// Groups for display
const grouped = computed(() => {
    const map = new Map<string, MergeField[]>();

    for (const f of filtered.value) {
        if (!map.has(f.group)) {
            map.set(f.group, []);
        }

        map.get(f.group)!.push(f);
    }

    return map;
});

// Flat index of a field in filtered list
const globalIndex = (field: MergeField) =>
    flatItems.value.findIndex((f) => f.tag === field.tag);

const onKey = (e: KeyboardEvent) => {
    if (e.key === "Escape") {
        e.preventDefault();
        emit("close");

        return;
    }

    if (e.key === "Enter") {
        e.preventDefault();
        const f = flatItems.value[activeIndex.value];

        if (f) {
            emit("insert", f.tag);
        }

        return;
    }

    if (e.key === "ArrowDown") {
        e.preventDefault();
        activeIndex.value = Math.min(
            activeIndex.value + 1,
            flatItems.value.length - 1,
        );

        return;
    }

    if (e.key === "ArrowUp") {
        e.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
    }
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="visible"
            class="border-border bg-background absolute z-50 w-72 overflow-hidden rounded-xl border shadow-xl"
            :style="{ top: `${top ?? 40}px`, left: `${left ?? 0}px` }"
        >
            <!-- Search header -->
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
                <input
                    ref="searchInput"
                    v-model="query"
                    placeholder="Filter fields…"
                    class="flex-1 bg-transparent text-xs outline-none placeholder:text-muted-foreground"
                    @keydown="onKey"
                />
            </div>

            <!-- Field groups -->
            <div class="max-h-64 overflow-y-auto">
                <template v-if="filtered.length === 0">
                    <div
                        class="text-muted-foreground px-3 py-4 text-center text-xs"
                    >
                        No fields match "{{ query }}"
                    </div>
                </template>

                <template v-for="[group, items] in grouped" :key="group">
                    <div
                        class="bg-muted/60 border-border border-b border-t px-3 py-1 text-[9px] font-semibold uppercase tracking-widest text-muted-foreground first:border-t-0"
                    >
                        {{ group }}
                    </div>
                    <div
                        v-for="field in items"
                        :key="field.tag"
                        class="border-border flex cursor-pointer items-center justify-between border-b px-3 py-2 last:border-0"
                        :class="
                            globalIndex(field) === activeIndex
                                ? 'bg-primary/5 text-primary'
                                : 'hover:bg-muted/50'
                        "
                        @mouseenter="activeIndex = globalIndex(field)"
                        @click="emit('insert', field.tag)"
                    >
                        <div>
                            <div
                                class="text-xs font-medium"
                                :class="
                                    globalIndex(field) === activeIndex
                                        ? 'text-primary'
                                        : 'text-foreground'
                                "
                            >
                                {{ field.name }}
                            </div>
                            <div
                                class="text-muted-foreground mt-0.5 text-[10px]"
                            >
                                {{ field.desc }}
                            </div>
                        </div>
                        <code
                            class="ml-2 shrink-0 rounded px-1.5 py-0.5 text-[10px]"
                            :class="
                                globalIndex(field) === activeIndex
                                    ? 'bg-primary/10 text-primary'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{ field.tag.replace(/[{}$ ]/g, "").trim() }}
                        </code>
                    </div>
                </template>
            </div>

            <!-- Footer hint -->
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
                    <Kbd
                        class="border-border bg-background rounded border px-1 font-mono text-[9px]"
                        >{{ hint[0] }}</Kbd
                    >
                    {{ hint[1] }}
                </span>
            </div>
        </div>
    </Transition>
</template>
