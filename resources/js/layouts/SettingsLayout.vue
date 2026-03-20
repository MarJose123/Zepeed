<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { KeyRound, RadioTower, ShieldCheck, User } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { useCurrentUrl } from "@/composables/useCurrentUrl";
import { toUrl } from "@/lib/utils";
import type { INavItem } from "@/types";

const sidebarNavItems: INavItem[] = [
    {
        title: "Profile",
        href: route("profile.edit", {}, false),
        icon: User,
    },
    {
        title: "Password",
        href: route("user-password.edit", {}, false),
        icon: KeyRound,
    },
    {
        title: "Two-Factor Auth",
        href: route("two-factor.show", {}, false),
        icon: ShieldCheck,
    },
    {
        title: "Sessions",
        href: route("user-sessions.edit", {}, false),
        icon: RadioTower,
    },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="space-y-4 p-4 sm:p-6">
        <div class="space-y-2">
            <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">
                Settings
            </h2>
            <p class="text-sm text-muted-foreground">
                Manage your account settings and preferences.
            </p>
        </div>

        <Separator class="my-4 sm:my-6" />

        <div class="flex flex-col w-full gap-6 sm:gap-8 lg:flex-row lg:gap-12">
            <aside class="-mx-4 sm:mx-0 lg:w-1/5 lg:pr-6">
                <nav
                    class="flex flex-wrap gap-2 px-4 pb-2 sm:px-0 sm:pb-0 lg:flex-col lg:gap-1"
                    aria-label="Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'shrink-0 justify-start text-left lg:w-full',
                            { 'bg-muted': isCurrentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link
                            :href="item.href"
                            class="flex items-center gap-2 whitespace-normal lg:w-full"
                        >
                            <component
                                v-if="item.icon"
                                :is="item.icon"
                                class="h-4 w-4"
                            />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <div class="flex w-full flex-1">
                <section class="space-y-6 w-full">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
