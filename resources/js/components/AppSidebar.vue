<script setup lang="ts">
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import {
    Cable,
    ChartGantt,
    LayoutDashboard,
    LifeBuoy,
    Settings2,
    Network,
} from "@lucide/vue";
import { h } from "vue";
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
                        href: route("speedtest.results.download", {}, false),
                    },
                    {
                        title: "Upload",
                        href: route("speedtest.results.upload", {}, false),
                    },
                    {
                        title: "Latency",
                        href: route("speedtest.results.latency", {}, false),
                    },
                ],
            },
            {
                title: "Settings",
                icon: Settings2,
                items: [
                    {
                        title: "General",
                        href: route(
                            "speedtest.general-settings.edit",
                            {},
                            false,
                        ),
                    },
                    {
                        title: "Alert Rules",
                        href: route("speedtest.alert-rules.index", {}, false),
                    },
                    {
                        title: "Email Templates",
                        href: route(
                            "speedtest.email-templates.index",
                            {},
                            false,
                        ),
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
            {
                title: "Network",
                icon: Network,
                items: [
                    {
                        title: "Ping Results",
                        href: route(
                            "speedtest.network.ping-results.index",
                            {},
                            false,
                        ),
                    },
                    {
                        title: "Ping Targets",
                        href: route(
                            "speedtest.network.ping-targets.index",
                            {},
                            false,
                        ),
                    },
                    {
                        title: "Ping Alerts",
                        href: route(
                            "speedtest.network.ping-alerts.index",
                            {},
                            false,
                        ),
                    },
                ],
            },
        ],
    },
];

const GithubIcon = () => h(Icon, { icon: "simple-icons:github" });
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
            icon: GithubIcon,
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
