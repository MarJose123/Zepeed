<script setup lang="ts">
import { useHttp } from "@inertiajs/vue3";
import { Loader2, Monitor, Smartphone } from "lucide-vue-next";
import { ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import type { EmailTemplate } from "@/types/email-template";

const props = defineProps<{
    open: boolean;
    template: EmailTemplate | null;
    /** Live subject/body from the editor — used when template is unsaved */
    subject: string;
    body: string;
}>();

const emit = defineEmits<{
    "update:open": [value: boolean];
}>();

type Viewport = "desktop" | "mobile";

const viewport = ref<Viewport>("desktop");
const loading = ref(false);
const error = ref<string | null>(null);
const rendered = ref<{ subject: string; body: string } | null>(null);

// Fetch preview whenever dialog opens
watch(
    () => props.open,
    async (open) => {
        if (!open) return;

        rendered.value = null;
        error.value = null;
        loading.value = true;

        try {
            if (props.template) {
                const http = useHttp();
                // Saved template — use GET preview endpoint
                http.get(
                    route(
                        "speedtest.email-templates.preview",
                        { emailTemplate: props.template.id },
                        false,
                    ),
                    {
                        headers: { Accept: "application/json" },
                    },
                )
                    .then((res) => {
                        rendered.value = res as {
                            subject: string;
                            body: string;
                        };
                    })
                    .catch((e) => {
                        error.value =
                            e instanceof Error
                                ? JSON.stringify(e)
                                : "Network error.";
                    });
            } else {
                const http = useHttp({
                    subject: props.subject,
                    body: props.body,
                });
                // Unsaved / new template — POST raw content
                http.post(
                    route("speedtest.email-templates.preview-raw", {}, false),
                    {
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                        },
                    },
                )
                    .then((res) => {
                        rendered.value = res as {
                            subject: string;
                            body: string;
                        };
                    })
                    .catch((e) => {
                        error.value =
                            e instanceof Error
                                ? JSON.stringify(e)
                                : "Network error.";
                    });
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : "Network error.";
        } finally {
            loading.value = false;
        }
    },
);

function openLinks(e: MouseEvent) {
    const target = e.target as HTMLElement;
    const anchor = target.closest("a");

    if (anchor?.href) {
        e.preventDefault();
        window.open(anchor.href, "_blank", "noopener,noreferrer");
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="flex max-h-[90vh] flex-col gap-0 overflow-hidden p-0"
            :class="viewport === 'desktop' ? 'max-w-3xl' : 'max-w-sm'"
        >
            <DialogHeader class="border-border shrink-0 border-b px-5 py-4">
                <DialogTitle class="text-sm font-medium">
                    Template preview
                    <span
                        class="text-muted-foreground ml-1 text-xs font-normal"
                    >
                        — rendered with sample data
                    </span>
                </DialogTitle>
            </DialogHeader>

            <!-- Loading -->
            <div
                v-if="loading"
                class="flex flex-1 items-center justify-center py-16"
            >
                <Loader2 class="text-muted-foreground h-5 w-5 animate-spin" />
                <span class="text-muted-foreground ml-2 text-sm"
                    >Rendering…</span
                >
            </div>

            <!-- Error -->
            <div
                v-else-if="error"
                class="bg-destructive/5 border-destructive/20 m-5 rounded-lg border p-4"
            >
                <p class="text-destructive text-xs font-medium">
                    Blade render error
                </p>
                <pre
                    class="text-destructive/80 mt-1 whitespace-pre-wrap font-mono text-[11px] leading-relaxed"
                    >{{ error }}</pre
                >
            </div>

            <!-- Preview content -->
            <div
                v-else-if="rendered"
                class="flex flex-1 flex-col overflow-hidden"
            >
                <!-- Subject bar -->
                <div class="border-border bg-muted/50 border-b px-5 py-3">
                    <span
                        class="text-muted-foreground text-[11px] font-medium uppercase tracking-wide"
                        >Subject</span
                    >
                    <p class="mt-0.5 text-sm font-medium">
                        {{ rendered.subject }}
                    </p>
                </div>

                <!-- Email body -->
                <div
                    class="flex-1 overflow-y-auto bg-zinc-100 p-6 dark:bg-zinc-900"
                >
                    <div
                        class="mx-auto overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-800"
                        :class="
                            viewport === 'desktop' ? 'max-w-2xl' : 'max-w-xs'
                        "
                    >
                        <div class="bg-primary h-1.5 w-full" />
                        <div
                            class="font-sans px-8 py-8 text-sm leading-relaxed text-zinc-800 dark:text-zinc-100 [&_a]:text-indigo-500 [&_a]:underline [&_a:hover]:text-indigo-700 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:mb-1 [&_p:empty]:h-4"
                            v-html="rendered.body"
                            @click.capture="openLinks"
                        />
                        <div
                            class="border-t border-zinc-100 px-8 py-4 dark:border-zinc-700"
                        >
                            <p class="text-[11px] text-zinc-400">
                                Sent by Zepeed · sample data preview
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer — viewport toggle + note -->
            <div
                class="border-border bg-muted/30 flex items-center justify-between border-t px-5 py-2.5 gap-6"
            >
                <p class="text-muted-foreground text-[10px] text-balance">
                    Preview uses placeholder values for all merge fields. Actual
                    values are injected by at send time.
                </p>

                <!-- Viewport toggle in footer -->
                <div
                    class="border-border flex items-center gap-0.5 rounded-lg border p-0.5"
                >
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 rounded-md"
                        :class="
                            viewport === 'desktop'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground'
                        "
                        @click="viewport = 'desktop'"
                    >
                        <Monitor class="h-3.5 w-3.5" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 rounded-md"
                        :class="
                            viewport === 'mobile'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground'
                        "
                        @click="viewport = 'mobile'"
                    >
                        <Smartphone class="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
