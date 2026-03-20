<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ChevronRight } from "lucide-vue-next";
import { useId } from "vue";
import {
    Collapsible,
    CollapsibleTrigger,
    CollapsibleContent,
} from "@/components/ui/collapsible";
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from "@/components/ui/sidebar";
import type { INavGroup } from "@/types";

const props = defineProps<{
    items: INavGroup[];
}>();
</script>

<template>
    <SidebarGroup
        v-for="mainItem in props.items"
        :key="mainItem.title ?? useId()"
    >
        <SidebarGroupLabel>{{ mainItem.title }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in mainItem.items" :key="item?.title">
                <!-- Item with sub-items: collapsible -->
                <Collapsible
                    v-if="item.items && item.items.length > 0"
                    as-child
                    class="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton :tooltip="item.title">
                                <component v-if="item.icon" :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="subItem in item.items"
                                    :key="subItem.title"
                                >
                                    <SidebarMenuSubButton as-child>
                                        <Link :href="subItem.href">
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Item without sub-items: simple link -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton as-child :tooltip="item.title">
                        <Link :href="item.href">
                            <component v-if="item.icon" :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

<style scoped></style>
