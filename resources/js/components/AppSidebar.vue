<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import {
    Cable,
    ChartGantt,
    Github,
    LayoutDashboard,
    LifeBuoy,
    Settings2,
} from "lucide-vue-next";
import AppLogo from "@/components/AppLogo.vue";
import NavMain from "@/components/NavMain.vue";
import NavSidebarFooter from "@/components/NavSidebarFooter.vue";
import NavUser from "@/components/NavUser.vue";
import type { SidebarProps } from "@/components/ui/sidebar";

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import type { TFooterNavigationItems, TSidebarNavigationItems } from "@/types";

const props = withDefaults(defineProps<SidebarProps>(), {
    variant: "inset",
});

const navigation: TSidebarNavigationItems = [
    {
        title: "Platform",
        items: [
            {
                title: "Dashboard",
                href: route("dashboard", {}, false),
                icon: LayoutDashboard,
            },
            {
                title: "Speedtest Results",
                icon: ChartGantt,
                items: [
                    {
                        title: "Download",
                        href: "#",
                    },
                    {
                        title: "Upload",
                        href: "#",
                    },
                    {
                        title: "Pings",
                        href: "#",
                    },
                ],
            },
            {
                title: "Settings",
                icon: Settings2,
                items: [
                    {
                        title: "General",
                        href: "#",
                    },
                    {
                        title: "Alerts",
                        href: "#",
                    },
                    {
                        title: "Schedules",
                        href: route("speedtest.schedules.index", {}, false),
                    },
                    {
                        title: "Speedtest Providers",
                        href: route(
                            "speedtest.server.providers.index",
                            {},
                            false,
                        ),
                    },
                ],
            },
            {
                title: "Integrations",
                icon: Cable,
                items: [
                    {
                        title: "Webhooks",
                        href: route("speedtest.integration.webhooks.index"),
                    },
                    {
                        title: "Mailer",
                        href: route("speedtest.integration.smtp.index"),
                    },
                ],
            },
        ],
    },
];

const footerNavigation: TFooterNavigationItems = {
    title: "Links",
    items: [
        {
            title: "Support",
            href: "https://github.com/MarJose123/Zepeed/issues?ref=zepeed-app",
            icon: LifeBuoy,
        },
        {
            title: "GitHub",
            href: "https://github.com/MarJose123/Zepeed?ref=zepeed-app",
            icon: Github,
        },
    ],
};
</script>

<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard', {}, false)">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="pb-3">
            <NavMain :items="navigation" />
            <NavSidebarFooter :items="footerNavigation" class="mt-auto" />
        </SidebarContent>
        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
