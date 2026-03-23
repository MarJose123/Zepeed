import type { Auth, UserAppearance } from "@/types/auth";
import type { INotification } from "@/types/notification";
import type { Provider } from "@/types/provider";

// Extend ImportMeta interface for Vite...
declare module "vite/client" {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module "@inertiajs/core" {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            appearance: UserAppearance;
            speedtest: Provider[];
            [key: string]: unknown;
        };
        flashDataType: {
            notification?: INotification;
        };
    }
}

declare module "vue" {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
