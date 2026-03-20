import { toast } from "vue-sonner";
import type { INotification } from "@/types/notification";
import "vue-sonner/style.css";

export function useNotification() {
    const notify = (notification: INotification | undefined) => {
        if (notification?.type === "error") {
            toast.error(notification.title ?? "", {
                description: notification.message,
                duration: 5000,
            });
        }
        if (notification?.type === "success") {
            toast.success(notification.title ?? "", {
                description: notification.message,
                duration: 5000,
            });
        }
        if (notification?.type === "warning") {
            toast.warning(notification.title ?? "", {
                description: notification.message,
                duration: 5000,
            });
        }
        if (notification?.type === "info") {
            toast.info(notification.title ?? "", {
                description: notification.message,
                duration: 5000,
            });
        }
    };

    return { notify };
}
