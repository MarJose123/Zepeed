import { usePage } from "@inertiajs/vue3";
import { useEcho } from "@laravel/echo-vue";
import { toast } from "vue-sonner";
import type { Auth } from "@/types";
import type {
    ExportCompletedPayload,
    ExportFailedPayload,
} from "@/types/export";

export function useExportChannel(): void {
    const page = usePage<{ auth: Auth }>();
    const userId = page.props.auth.user.id;

    useEcho<ExportCompletedPayload>(
        `exports.${userId}`,
        ".export.completed",
        (payload) => {
            toast.success("Export ready", {
                description: `${payload.module_label} · ${payload.row_count ?? 0} rows · ${payload.format.toUpperCase()}`,
                duration: 0,
                action: {
                    label: "Download",
                    onClick: () => window.open(payload.download_url, "_blank"),
                },
            });
        },
    );

    useEcho<ExportFailedPayload>(
        `exports.${userId}`,
        ".export.failed",
        (payload) => {
            toast.error("Export failed", {
                description:
                    payload.failure_message ??
                    `${payload.module_label} export could not be completed.`,
                duration: 8000,
            });
        },
    );
}
