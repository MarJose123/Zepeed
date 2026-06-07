<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { UserLock } from "@lucide/vue";
import AppLogoIcon from "@/components/AppLogoIcon.vue";
</script>

<template>
    <div class="bg-background text-foreground min-h-screen">
        <header
            class="bg-background/90 border-border sticky top-0 z-30 border-b backdrop-blur-sm"
        >
            <div
                class="mx-auto flex h-12 max-w-7xl items-center justify-between px-5"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="bg-sidebar-primary text-sidebar-primary-foreground flex size-7 items-center justify-center rounded-lg"
                    >
                        <AppLogoIcon class="size-4" />
                    </div>
                    <span class="text-sm font-bold tracking-tight">Zepeed</span>
                    <span
                        class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 text-[10px]"
                    >
                        Read-only · Public view
                    </span>
                </div>
                <Link
                    :href="route('login')"
                    class="border-border text-foreground hover:bg-muted inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs transition-colors"
                >
                    <UserLock class="size-4" />
                    Sign in
                </Link>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-5 py-5">
            <slot />
        </main>
    </div>
</template>
