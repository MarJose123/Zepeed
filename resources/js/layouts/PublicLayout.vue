<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="bg-background text-foreground min-h-screen">
        <header class="bg-background/90 border-border sticky top-0 z-30 border-b backdrop-blur-sm">
            <div class="mx-auto flex h-12 max-w-screen-xl items-center justify-between px-5">
                <div class="flex items-center gap-2.5">
                    <div class="bg-foreground flex h-5.5 w-5.5 items-center justify-center rounded-md">
                        <svg
                            class="text-background h-3 w-3"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.5"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-sm font-bold tracking-tight">Zepeed</span>
                    <span class="bg-muted text-muted-foreground rounded px-1.5 py-0.5 text-[10px]">
                        Read-only · Public view
                    </span>
                </div>
                <Link
                    :href="route('login')"
                    class="border-border text-foreground hover:bg-muted inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs transition-colors"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Sign in
                </Link>
            </div>
        </header>
        <main class="mx-auto max-w-screen-xl px-5 py-5">
            <slot />
        </main>
    </div>
</template>
