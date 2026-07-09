<!-- resources/js/components/NavUser.vue -->
<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import {
    Bell,
    ChevronsUpDown,
    LogOut,
    Moon,
    Settings,
} from "@lucide/vue";
import { computed } from "vue";
import DisplayModeToggle from "@/components/DisplayModeToggle.vue";
import NotificationSheet from "@/components/notifications/NotificationSheet.vue";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from "@/components/ui/sidebar";
import UserInfo from "@/components/UserInfo.vue";
import { useAppearance } from "@/composables/useAppearance";
import { useNotificationSheet } from "@/composables/useNotificationSheet";
import type { Auth, TUserNotification } from "@/types";

const { isMobile, state } = useSidebar();
const { updateAppearance } = useAppearance();
const { open: openSheet } = useNotificationSheet();

const page = usePage<{ auth: Auth; notifications?: TUserNotification[] }>();
const user = computed(() => page.props.auth.user);
const unreadCount = computed(() => user.value.unread_count ?? 0);
const notifications = computed(() => page.props.notifications ?? []);

function handleLogout(): void {
    updateAppearance("light");
    router.flushAll();
}

function openNotifications(): void {
    openSheet.value = true;
}
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <div class="relative">
                            <UserInfo :user="user" />
                            <!-- Unread badge -->
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-primary-foreground"
                            >
                                {{ unreadCount > 9 ? "9+" : unreadCount }}
                            </span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'left'
                              : 'bottom'
                    "
                    align="end"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="p-0 font-normal">
                        <div
                            class="flex items-center gap-2 px-1 py-1.5 text-left text-sm"
                        >
                            <UserInfo :user="user" />
                        </div>
                    </DropdownMenuLabel>

                    <DropdownMenuSeparator />

                    <DropdownMenuGroup>
                        <DropdownMenuItem
                            class="flex items-center justify-between"
                            @select.prevent
                        >
                            <div class="flex items-center gap-2">
                                <Moon class="size-4" />
                                <span>Appearance</span>
                            </div>
                            <DisplayModeToggle />
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <DropdownMenuGroup>
                        <DropdownMenuItem
                            class="cursor-pointer"
                            @select="openNotifications"
                        >
                            <Bell class="size-4" />
                            <span>Notifications</span>
                            <span
                                v-if="unreadCount > 0"
                                class="ml-auto flex size-5 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-primary-foreground"
                            >
                                {{ unreadCount > 9 ? "9+" : unreadCount }}
                            </span>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link
                                class="block w-full cursor-pointer flex-row"
                                :href="route('profile.edit')"
                                as="button"
                                method="get"
                            >
                                <Settings />
                                Settings
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem :as-child="true">
                        <Link
                            class="block w-full cursor-pointer"
                            :href="route('logout')"
                            as="button"
                            method="post"
                            @click="handleLogout"
                        >
                            <LogOut />
                            Log out
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>

    <!-- Sheet lives here, outside the dropdown, controlled by useNotificationSheet -->
    <NotificationSheet
        :notifications="notifications"
        :unread-count="unreadCount"
    />
</template>
