<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { HelpCircle, PlusIcon } from "lucide-vue-next";
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
import type { EmailTemplate, MergeField } from "@/types/email-template";

const props = defineProps<{
    templates: EmailTemplate[];
    merge_fields: MergeField[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "Email templates",
        href: route("speedtest.email-templates.index", {}, false),
    },
];

const selectedId = ref<string | null>(props.templates[0]?.id ?? null);
const isNew = ref(false);
const showHelp = ref(false);

const selectedTemplate = computed(
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

watch(
    () => props.templates,
    (list) => {
        if (isNew.value) return;

        if (!list.find((t) => t.id === selectedId.value)) {
            selectedId.value = list[0]?.id ?? null;
        }
    },
);

const lastUpdatedLabel = (tpl: EmailTemplate): string => {
    const diff = Date.now() - new Date(tpl.updated_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) return "just now";

    if (mins < 60) return `${mins}m ago`;

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) return `${hrs}h ago`;

    return `${Math.floor(hrs / 24)}d ago`;
};

const bladeSnippet = `@if($failure_reason)\nFailure: {{ $failure_reason }}\n@endif`;
const bladeSnippet2 = `@foreach($results as $r)\n- {{ $r }}\n@endforeach`;
</script>

<template>
    <Head title="Email templates" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 flex-1">
            <!-- ── Left: template list ── -->
            <div class="border-border flex w-md shrink-0 flex-col border-r">
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

                <div class="flex-1 overflow-y-auto">
                    <div
                        v-for="template in templates"
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
                        <!-- Use safeSubject() to strip Blade tags — avoids Vue compiler conflict -->
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
                        <div class="mt-1.5 flex items-center gap-1.5">
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
                            <span class="text-muted-foreground text-[10px]">
                                {{ lastUpdatedLabel(template) }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="templates.length === 0"
                        class="text-muted-foreground px-3 py-8 text-center text-xs"
                    >
                        No templates yet.
                        <button
                            class="text-primary underline"
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
                    :is-new="isNew"
                    @saved="isNew = false"
                    @deleted="selectedId = templates[0]?.id ?? null"
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
                                class="border-border bg-muted rounded border px-1.5 py-0.5 font-mono text-[10px]"
                                >#</Kbd
                            >
                            anywhere in the subject or body to open the merge
                            field picker. Use ↑↓ to navigate, Enter to insert,
                            Esc to dismiss. You can also click any chip at the
                            bottom of the editor.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <p class="text-xs font-medium">Blade directives</p>
                        <p
                            class="text-muted-foreground text-xs leading-relaxed"
                        >
                            Templates run through the full Blade compiler so all
                            directives work:
                        </p>
                        <!-- Use v-text / pre to safely render Blade snippets without Vue parsing them -->
                        <pre
                            class="bg-muted rounded-lg p-3 font-mono text-[11px] leading-relaxed"
                            v-text="bladeSnippet"
                        />
                        <pre
                            class="bg-muted rounded-lg p-3 font-mono text-[11px] leading-relaxed"
                            v-text="bladeSnippet2"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <p class="text-xs font-medium">
                            System vs custom templates
                        </p>
                        <p
                            class="text-muted-foreground text-xs leading-relaxed"
                        >
                            System templates ship with Zepeed and cannot be
                            deleted — only edited. Custom templates can be
                            freely removed. Alert rules reference templates by
                            ID so deleting a used template will leave those
                            rules without one.
                        </p>
                    </div>

                    <div class="border-border rounded-lg border p-3">
                        <p class="text-muted-foreground text-xs">
                            For the full Blade reference visit

                            <a
                                href="https://laravel.com/docs/blade"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary underline underline-offset-2"
                            >
                                laravel.com/docs/blade
                            </a>
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
