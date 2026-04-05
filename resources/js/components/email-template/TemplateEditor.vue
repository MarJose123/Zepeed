<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Eye, Loader2 } from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { nextTick } from "vue";
import MergeFieldPicker from "@/components/email-template/MergeFieldPicker.vue";
import TemplatePreviewDialog from "@/components/email-template/TemplatePreviewDialog.vue";
import TiptapEditor from "@/components/email-template/TiptapEditor.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { EmailTemplate, MergeField } from "@/types/email-template";

const props = defineProps<{
    template: EmailTemplate | null;
    mergeFields: MergeField[];
    isNew: boolean;
}>();

const emit = defineEmits<{
    saved: [];
    deleted: [];
}>();

const form = useForm({
    name: props.template?.name ?? "",
    subject: props.template?.subject ?? "",
    body: props.template?.body ?? "",
});

watch(
    () => props.template,
    (tpl) => {
        form.name = tpl?.name ?? "";
        form.subject = tpl?.subject ?? "";
        form.body = tpl?.body ?? "";
    },
    { immediate: false },
);

const showPreview = ref(false);
const showDeleteDialog = ref(false);

// Subject field — still uses native input with # trigger via MergeFieldPicker
const subjectRef = ref<HTMLInputElement | null>(null);
const pickerZone = ref<"subject" | null>(null);
const hashPos = ref(-1);
const pickerTop = ref(0);
const pickerLeft = ref(0);

// Keep the old MergeFieldPicker only for subject since body is now Tiptap

const closePicker = () => {
    pickerZone.value = null;
};

const onSubjectInput = (e: Event) => {
    const inp = e.target as HTMLInputElement;
    const pos = inp.selectionStart ?? 0;

    if (inp.value[pos - 1] === "#") {
        hashPos.value = pos - 1;
        pickerZone.value = "subject";
        pickerTop.value = 44;
        pickerLeft.value = 0;
    } else if (pickerZone.value === "subject") {
        closePicker();
    }
};

const insertField = (tag: string) => {
    if (!subjectRef.value) {
        closePicker();

        return;
    }

    const val = form.subject ?? "";
    const cursor = subjectRef.value.selectionStart ?? val.length;
    const insertAt = hashPos.value >= 0 ? hashPos.value : cursor;
    const before = val.slice(0, insertAt);
    const after = val.slice(cursor);

    form.subject = before + tag + after;

    nextTick(() => {
        const newPos = before.length + tag.length;
        subjectRef.value?.focus();
        subjectRef.value?.setSelectionRange(newPos, newPos);
    });
    hashPos.value = -1;
    closePicker();
};

const save = () => {
    if (props.isNew) {
        form.post(route("speedtest.email-templates.store", {}, false), {
            preserveScroll: true,
            onSuccess: () => {
                emit("saved");
            },
        });
    } else if (props.template) {
        form.patch(
            route(
                "speedtest.email-templates.update",
                { emailTemplate: props.template.id },
                false,
            ),
            {
                preserveScroll: true,
                onSuccess: () => {
                    emit("saved");
                },
            },
        );
    }
};

const confirmDelete = () => {
    showDeleteDialog.value = true;
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
};

const destroyTemplate = () => {
    if (!props.template) return;

    router.delete(
        route(
            "speedtest.email-templates.destroy",
            { emailTemplate: props.template.id },
            false,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteDialog.value = false;
                emit("deleted");
            },
        },
    );
};

const lastUpdatedLabel = computed(() => {
    if (!props.template?.updated_at) return "";

    const diff = Date.now() - new Date(props.template.updated_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) return "edited just now";

    if (mins < 60) return `edited ${mins} min ago`;

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) return `edited ${hrs}h ago`;

    return `edited ${Math.floor(hrs / 24)}d ago`;
});
</script>

<template>
    <div class="flex flex-1 flex-col overflow-hidden">
        <!-- Top bar -->
        <div
            class="border-border flex items-center justify-between border-b px-4 py-3"
        >
            <div>
                <div class="text-sm font-medium">
                    {{ isNew ? "New template" : template?.name }}
                </div>
                <div class="text-muted-foreground mt-0.5 text-xs">
                    <template v-if="isNew">Unsaved · custom template</template>
                    <template v-else>
                        {{
                            template?.is_used_in_rules
                                ? `Used in ${template.used_in_rule_names.join(", ")}`
                                : "Not used in any rules"
                        }}
                        · {{ template?.is_system ? "system" : "custom" }} ·
                        {{ lastUpdatedLabel }}
                    </template>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span v-if="form.isDirty" class="text-muted-foreground text-xs"
                    >Unsaved changes</span
                >
                <Button
                    v-if="!isNew && template && !template.is_system"
                    variant="outline"
                    size="sm"
                    class="text-destructive border-destructive/30 text-xs"
                    @click="confirmDelete"
                >
                    Delete
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="text-xs"
                    :disabled="!form.body && !form.subject"
                    @click="showPreview = true"
                >
                    <Eye class="mr-1.5 h-3.5 w-3.5" />
                    Preview
                </Button>
                <Button size="sm" :disabled="form.processing" @click="save">
                    <Loader2
                        v-if="form.processing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    Save template
                </Button>
            </div>
        </div>

        <!-- Editor body -->
        <div class="flex flex-1 flex-col gap-4 overflow-y-auto p-4">
            <!-- Name + Subject -->
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <Label class="text-xs">Template name</Label>
                    <Input
                        v-model="form.name"
                        placeholder="e.g. Default alert"
                        class="text-xs"
                    />
                    <p v-if="form.errors.name" class="text-destructive text-xs">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="relative space-y-1.5">
                    <Label class="text-xs">
                        Subject
                        <span class="text-muted-foreground font-normal">
                            — type
                            <kbd
                                class="border-border bg-muted rounded border px-1 font-mono text-[10px]"
                                >#</kbd
                            >
                            to insert field
                        </span>
                    </Label>
                    <input
                        ref="subjectRef"
                        v-model="form.subject"
                        placeholder="⚡ Speedtest alert — {{ $provider_name }}"
                        class="border-border bg-background focus:border-ring placeholder:text-muted-foreground h-9 w-full rounded-lg border px-3 font-mono text-xs outline-none transition-colors"
                        @input="onSubjectInput"
                    />
                    <p
                        v-if="form.errors.subject"
                        class="text-destructive text-xs"
                    >
                        {{ form.errors.subject }}
                    </p>

                    <!-- Subject merge picker -->
                    <MergeFieldPicker
                        :fields="mergeFields"
                        :visible="pickerZone === 'subject'"
                        :top="pickerTop"
                        :left="pickerLeft"
                        @insert="insertField"
                        @close="closePicker"
                    />
                </div>
            </div>

            <!-- Tiptap body editor -->
            <div class="flex flex-1 flex-col space-y-1.5">
                <Label class="text-xs">Body</Label>
                <TiptapEditor
                    v-model="form.body"
                    :merge-fields="mergeFields"
                    placeholder="Write your email body here… type # to insert a merge field"
                />
                <p v-if="form.errors.body" class="text-destructive text-xs">
                    {{ form.errors.body }}
                </p>
            </div>
        </div>

        <!-- Delete confirmation -->
        <Teleport to="body">
            <div
                v-if="showDeleteDialog"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            >
                <div
                    class="bg-background border-border w-96 overflow-hidden rounded-xl border shadow-2xl"
                >
                    <div class="border-border border-b px-5 py-4">
                        <div class="text-sm font-medium">Delete template?</div>
                    </div>
                    <div class="px-5 py-4">
                        <div
                            v-if="template?.is_used_in_rules"
                            class="mb-3 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950"
                        >
                            <span class="mt-0.5 text-amber-600">⚠</span>
                            <div>
                                <p
                                    class="text-xs font-medium text-amber-700 dark:text-amber-400"
                                >
                                    Template is in use
                                </p>
                                <p
                                    class="mt-0.5 text-xs text-amber-600 dark:text-amber-500"
                                >
                                    <strong>{{ template.name }}</strong> is
                                    referenced by
                                    {{
                                        template.used_in_rule_names.join(", ")
                                    }}.
                                </p>
                            </div>
                        </div>
                        <p class="text-muted-foreground text-xs">
                            Delete <strong>{{ template?.name }}</strong
                            >? This cannot be undone.
                        </p>
                    </div>
                    <div
                        class="border-border flex justify-end gap-2 border-t px-5 py-3"
                    >
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="cancelDelete"
                            >Keep template</Button
                        >
                        <Button
                            size="sm"
                            variant="destructive"
                            @click="destroyTemplate"
                        >
                            {{
                                template?.is_used_in_rules
                                    ? "Delete anyway"
                                    : "Delete template"
                            }}
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>

    <TemplatePreviewDialog
        v-model:open="showPreview"
        :template="isNew ? null : template"
        :subject="form.subject"
        :body="form.body"
    />
</template>
