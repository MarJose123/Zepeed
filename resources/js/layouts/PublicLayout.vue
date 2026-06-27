<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { BarChart2, Moon, Sun, UserLock } from "@lucide/vue";
import { computed, onMounted } from "vue";
import AppLogoIcon from "@/components/AppLogoIcon.vue";
import { useAppearance } from "@/composables/useAppearance";

const { resolvedAppearance, updateAppearance } = useAppearance();

// Default to dark on first visit — runs after the composable's own onMounted
// which reads localStorage, so this only fires when no preference is saved yet.
onMounted(() => {
    if (!localStorage.getItem("appearance")) {
        updateAppearance("dark");
    }
});

const isDark = computed(() => resolvedAppearance.value === "dark");

function toggleDark(): void {
    updateAppearance(isDark.value ? "light" : "dark");
}
</script>

<template>
    <div class="bg-background text-foreground min-h-screen">
        <header
            class="bg-background/90 border-border sticky top-0 z-30 border-b backdrop-blur-sm"
        >
            <div
                class="mx-auto flex h-12 max-w-7xl items-center justify-between px-5"
            >
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="bg-sidebar-primary text-sidebar-primary-foreground flex size-7 items-center justify-center rounded-lg"
                        >
                            <AppLogoIcon class="size-4" />
                        </div>
                        <span class="text-sm font-bold tracking-tight">
                            Zepeed
                        </span>
                        <span
                            class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 text-[10px]"
                        >
                            Read-only · Public view
                        </span>
                    </div>

                    <nav class="flex items-center gap-0.5">
                        <Link
                            :href="route('public.dashboard')"
                            class="text-muted-foreground hover:text-foreground hover:bg-muted rounded-md px-3 py-1.5 text-xs transition-colors"
                            :class="{
                                'text-foreground bg-muted font-medium':
                                    route().current('public.dashboard'),
                            }"
                        >
                            Dashboard
                        </Link>
                        <Link
                            :href="route('public.metrics')"
                            class="text-muted-foreground hover:text-foreground hover:bg-muted flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs transition-colors"
                            :class="{
                                'text-foreground bg-muted font-medium':
                                    route().current('public.metrics'),
                            }"
                        >
                            <BarChart2 class="size-3.5" />
                            Metrics
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground hover:bg-muted flex size-8 items-center justify-center rounded-md transition-colors"
                        :aria-label="
                            isDark
                                ? 'Switch to light mode'
                                : 'Switch to dark mode'
                        "
                        @click="toggleDark"
                    >
                        <Sun v-if="isDark" class="size-4" />
                        <Moon v-else class="size-4" />
                    </button>

                    <Link
                        :href="route('login')"
                        class="border-border text-foreground hover:bg-muted inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs transition-colors"
                    >
                        <UserLock class="size-4" />
                        Sign in
                    </Link>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-5 py-5">
            <slot />
        </main>
    </div>
</template>
