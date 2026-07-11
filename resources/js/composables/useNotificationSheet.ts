import { ref } from "vue";

const open = ref(false);

export function useNotificationSheet() {
    return { open };
}
