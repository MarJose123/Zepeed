import { router, usePage } from "@inertiajs/vue3";
import { useEcho } from "@laravel/echo-vue";
import { toast } from "vue-sonner";
import { useNotificationSheet } from "@/composables/useNotificationSheet";
import type { Auth } from "@/types";
import type {
    ExportCompletedPayload,
    ExportFailedPayload,
} from "@/types/export";

export function useNotificationChannel(): void {
    const page = usePage<{ auth: Auth }>();
    const userId = page.props.auth.user.id;
    const { open: openSheet } = useNotificationSheet();

    useEcho<ExportCompletedPayload>(
        `exports.${userId}`,
        ".export.completed",
        (payload) => {
            router.reload({ only: ["auth"] });

            toast.success(`${payload.module_label} export ready`, {
                description: `${payload.row_count ?? 0} rows · ${payload.format.toUpperCase()} — available for 7 days`,
                duration: 8000,
                action: {
                    label: "View in notifications",
                    onClick: () => {
                        openSheet.value = true;
                    },
                },
            });
        },
    );

    useEcho<ExportFailedPayload>(
        `exports.${userId}`,
        ".export.failed",
        (payload) => {
            router.reload({ only: ["auth"] });

            toast.error(`${payload.module_label} export failed`, {
                description:
                    payload.failure_message ?? "An unexpected error occurred.",
                duration: 8000,
                action: {
                    label: "View in notifications",
                    onClick: () => {
                        openSheet.value = true;
                    },
                },
            });
        },
    );
}
