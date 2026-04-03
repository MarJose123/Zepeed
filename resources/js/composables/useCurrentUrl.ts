import type { InertiaLinkProps } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import type { ComputedRef, DeepReadonly } from "vue";
import { computed, readonly } from "vue";
import { toUrl } from "@/lib/utils";

export type UseCurrentUrlReturn = {
    currentUrl: DeepReadonly<ComputedRef<string>>;
    isCurrentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps["href"]>,
        currentUrl?: string,
        exact?: boolean,
    ) => boolean;
    whenCurrentUrl: <T, F = null>(
        urlToCheck: NonNullable<InertiaLinkProps["href"]>,
        ifTrue: T,
        ifFalse?: F,
    ) => T | F;
};

const page = usePage();
const currentUrlReactive = computed(
    () => new URL(page.url, window?.location.origin).pathname,
);

export function useCurrentUrl(): UseCurrentUrlReturn {
    function isCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps["href"]>,
        currentUrl?: string,
        exact: boolean = true,
    ) {
        const urlToCompare = currentUrl ?? currentUrlReactive.value;
        const urlString = toUrl(urlToCheck);

        let pathnameToCheck = urlString;

        if (urlString.startsWith("http")) {
            try {
                pathnameToCheck = new URL(urlString).pathname;
            } catch {
                return false;
            }
        }

        if (exact) {
            return pathnameToCheck === urlToCompare;
        }

        return urlToCompare.startsWith(pathnameToCheck);
    }

    function whenCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps["href"]>,
        ifTrue: any,
        ifFalse: any = null,
    ) {
        return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        isCurrentUrl,
        whenCurrentUrl,
    };
}
