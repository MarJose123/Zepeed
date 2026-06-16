<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { HelpCircle, PlusIcon } from "@lucide/vue";
import { computed, ref, watch } from "vue";
import TemplateEditor from "@/components/email-template/TemplateEditor.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Kbd } from "@/components/ui/kbd";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    EmailTemplate,
    EmailTemplateType,
    MergeField,
    TemplateTypeOption,
} from "@/types/email-template";

const props = defineProps<{
    templates: EmailTemplate[];
    merge_fields: MergeField[];
    ping_merge_fields: MergeField[];
    template_types: TemplateTypeOption[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "Email templates",
        href: route("speedtest.email-templates.index", {}, false),
    },
];

// ── Filter ────────────────────────────────────────────────────────────────────

type FilterTab = "all" | EmailTemplateType;

const activeFilter = ref<FilterTab>("all");

const filterTabs: { value: FilterTab; label: string }[] = [
    { value: "all", label: "All" },
    { value: "speedtest", label: "Speedtest" },
    { value: "ping", label: "Ping" },
];

const filteredTemplates = computed<EmailTemplate[]>(() =>
    activeFilter.value === "all"
        ? props.templates
        : props.templates.filter((t) => t.template_type === activeFilter.value),
);

const filterCount = (tab: FilterTab): number =>
    tab === "all"
        ? props.templates.length
        : props.templates.filter((t) => t.template_type === tab).length;

// ── Selection ─────────────────────────────────────────────────────────────────

const selectedId = ref<string | null>(props.templates[0]?.id ?? null);
const isNew = ref(false);
const showHelp = ref(false);

const selectedTemplate = computed<EmailTemplate | null>(
    () => props.templates.find((t) => t.id === selectedId.value) ?? null,
);

const select = (id: string) => {
    selectedId.value = id;
    isNew.value = false;
};

const startNew = () => {
    selectedId.value = null;
    isNew.value = true;
};

watch(activeFilter, () => {
    isNew.value = false;
    selectedId.value = filteredTemplates.value[0]?.id ?? null;
});

watch(
    () => props.templates,
    (list) => {
        if (isNew.value) return;

        const visible =
            activeFilter.value === "all"
                ? list
                : list.filter((t) => t.template_type === activeFilter.value);

        if (!visible.find((t) => t.id === selectedId.value)) {
            selectedId.value = visible[0]?.id ?? null;
        }
    },
);

// ── Helpers ───────────────────────────────────────────────────────────────────

const lastUpdatedLabel = (tpl: EmailTemplate): string => {
    const diff = Date.now() - new Date(tpl.updated_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) return "just now";

    if (mins < 60) return `${mins}m ago`;

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) return `${hrs}h ago`;

    return `${Math.floor(hrs / 24)}d ago`;
};

const typeLabel = (tpl: EmailTemplate): string =>
    props.template_types.find((t) => t.value === tpl.template_type)?.label ??
    tpl.template_type;

const bladeSnippet = `@if($failure_reason)\nFailure: {{ $failure_reason }}\n@endif`;
const bladeSnippet2 = `@foreach($results as $r)\n- {{ $r }}\n@endforeach`;
</script>

<template>
    <Head title="Email templates" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 flex-1">
            <!-- ── Left: template list ── -->
            <div class="border-border flex w-md shrink-0 flex-col border-r">
                <!-- Header row -->
                <div
                    class="border-border flex items-center justify-between border-b px-3 py-3"
                >
                    <span class="text-sm font-medium">Templates</span>
                    <div class="flex items-center gap-1.5">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-muted-foreground h-7 w-7"
                            @click="showHelp = true"
                        >
                            <HelpCircle class="h-4 w-4" />
                        </Button>
                        <Button size="sm" class="h-7 text-xs" @click="startNew">
                            <PlusIcon />
                            New
                        </Button>
                    </div>
                </div>

                <!-- Filter tab strip -->
                <div class="border-border flex border-b px-3">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.value"
                        class="flex items-center gap-1.5 border-b-2 py-2 pr-4 text-xs transition-colors"
                        :class="
                            activeFilter === tab.value
                                ? 'border-primary text-primary font-medium'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        "
                        @click="activeFilter = tab.value"
                    >
                        {{ tab.label }}
                        <span
                            class="flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px]"
                            :class="
                                activeFilter === tab.value
                                    ? 'bg-primary/15 text-primary'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{ filterCount(tab.value) }}
                        </span>
                    </button>
                </div>

                <!-- Template list -->
                <div class="flex-1 overflow-y-auto">
                    <div
                        v-for="template in filteredTemplates"
                        :key="template.id"
                        class="border-border cursor-pointer border-b px-3 py-3 transition-colors"
                        :class="{
                            'border-l-2 border-l-primary bg-primary/5':
                                !isNew && selectedId === template.id,
                            'hover:bg-muted/50':
                                isNew || selectedId !== template.id,
                        }"
                        @click="select(template.id)"
                    >
                        <div
                            class="text-xs font-medium leading-snug"
                            :class="
                                !isNew && selectedId === template.id
                                    ? 'text-primary'
                                    : 'text-foreground'
                            "
                        >
                            {{ template.name }}
                        </div>
                        <div
                            class="mt-0.5 truncate text-xs"
                            :class="
                                !isNew && selectedId === template.id
                                    ? 'text-primary/60'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ template.subject }}
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <!-- system / custom badge -->
                            <Badge
                                variant="outline"
                                class="text-[9px]"
                                :class="
                                    template.is_system
                                        ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300'
                                        : 'border-green-300 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300'
                                "
                            >
                                {{ template.is_system ? "system" : "custom" }}
                            </Badge>

                            <!-- type badge — only shown in "All" tab -->
                            <Badge
                                v-if="activeFilter === 'all'"
                                variant="outline"
                                class="text-[9px]"
                                :class="
                                    template.template_type === 'ping'
                                        ? 'border-purple-300 bg-purple-50 text-purple-700 dark:border-purple-800 dark:bg-purple-950 dark:text-purple-300'
                                        : 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                "
                            >
                                {{ typeLabel(template) }}
                            </Badge>

                            <span class="text-muted-foreground text-[10px]">
                                {{ lastUpdatedLabel(template) }}
                            </span>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="filteredTemplates.length === 0"
                        class="text-muted-foreground flex flex-col items-center gap-2 px-3 py-10 text-center"
                    >
                        <p class="text-xs">
                            No
                            {{ activeFilter === "all" ? "" : activeFilter }}
                            templates yet.
                        </p>
                        <button
                            class="text-primary text-xs underline underline-offset-2"
                            @click="startNew"
                        >
                            Create one
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Right: editor ── -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <TemplateEditor
                    v-if="isNew || selectedTemplate"
                    :key="isNew ? 'new' : selectedId!"
                    :template="isNew ? null : selectedTemplate"
                    :merge-fields="merge_fields"
                    :ping-merge-fields="ping_merge_fields"
                    :template-types="template_types"
                    :is-new="isNew"
                    @saved="isNew = false"
                    @deleted="selectedId = filteredTemplates[0]?.id ?? null"
                />

                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-3"
                >
                    <p class="text-muted-foreground text-sm">
                        Select a template or create a new one.
                    </p>
                    <Button size="sm" variant="outline" @click="startNew">
                        + New template
                    </Button>
                </div>
            </div>
        </div>

        <!-- ── Help dialog ── -->
        <Dialog v-model:open="showHelp">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-sm font-medium">
                        How email templates work
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <p class="text-muted-foreground text-xs leading-relaxed">
                        Templates are stored as strings and rendered at send
                        time — no extra packages required.
                    </p>
                    <div class="space-y-1.5">
                        <p class="text-xs font-medium">
                            Inserting merge fields
                        </p>
                        <p
                            class="text-muted-foreground text-xs leading-relaxed"
                        >
                            Type
                            <Kbd
                                class="border-border bg-muted rounded border px-1.5 py-0.5 text-[10px]"
                                >#</Kbd
                            >
                            anywhere in the subject or body to open the merge
                            field picker. The fields shown match the template
                            type (Speedtest or Ping result).
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <p class="text-xs font-medium">Blade directives</p>
                        <pre
                            class="bg-muted rounded-lg p-3 text-[11px] leading-relaxed"
                            v-text="bladeSnippet"
                        />
                        <pre
                            class="bg-muted rounded-lg p-3 text-[11px] leading-relaxed"
                            v-text="bladeSnippet2"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <p class="text-xs font-medium">Template types</p>
                        <p
                            class="text-muted-foreground text-xs leading-relaxed"
                        >
                            Choose <strong>Speedtest result</strong> for speed
                            alert rules and <strong>Ping result</strong> for
                            ping alert rules. The merge field picker
                            automatically shows only the fields relevant to the
                            selected type.
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
