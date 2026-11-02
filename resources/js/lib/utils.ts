import type { InertiaLinkProps } from "@inertiajs/vue3";
export { cn } from "cn";

export function toUrl(href: NonNullable<InertiaLinkProps["href"]>) {
    return typeof href === "string" ? href : href?.url;
}
