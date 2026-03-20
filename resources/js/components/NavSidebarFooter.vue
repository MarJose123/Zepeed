<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { toUrl } from "@/lib/utils";
import type { INavGroup } from "@/types";

defineProps<{
    items: INavGroup;
}>();
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>{{ items.title }}</SidebarGroupLabel>
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        size="sm"
                        v-for="subItem in items.items"
                        :key="subItem.title"
                    >
                        <a
                            :href="toUrl(subItem.href!)"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component :is="subItem.icon" />
                            <span>{{ subItem.title }}</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>

<style scoped></style>
