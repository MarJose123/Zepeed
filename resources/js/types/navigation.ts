import type { InertiaLinkProps } from "@inertiajs/vue3";
import type { LucideIcon } from "lucide-vue-next";

export type TBreadcrumbItem = {
    title: string;
    href?: string;
};

// Base nav item (leaf node - no children)
export interface INavItem {
    title: string;
    href: NonNullable<InertiaLinkProps["href"]>;
    icon?: LucideIcon;
}

// Nav item that can have sub-items (for collapsible menus)
export interface INavItemWithChildren {
    title: string;
    href?: NonNullable<InertiaLinkProps["href"]>;
    icon?: LucideIcon;
    items?: INavItem[];
}

// A navigation group with a label and menu items
export interface INavGroup {
    title?: string;
    items: INavItemWithChildren[];
}

export type TSidebarNavigationItems = INavGroup[];
export type TFooterNavigationItems = INavGroup;
