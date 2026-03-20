import { router } from "@inertiajs/vue3";
import axios from "axios";
import { useNotification } from "@/composables/useNotification";

const axiosClient = axios.create({});
const { notify } = useNotification();

axiosClient.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

axiosClient.interceptors.response.use(
    (response) => response,
    (error) => {
        // Ensure the error has a response (network errors may not)
        const status = error?.response?.status;

        if ([401, 419].includes(status)) {
            notify({
                type: "error",
                title: "Session Expired!",
                message: "Your session has expired. Please login again.",
            });
            router.flushAll();
            router.visit(route("login"));
        }

        return Promise.reject(error);
    },
);

export default axiosClient;
