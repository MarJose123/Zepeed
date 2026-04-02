<script setup lang="ts">
import { useHttp } from "@inertiajs/vue3";
import { Moon, Sun } from "lucide-vue-next";
import { computed } from "vue";
import { Switch } from "@/components/ui/switch";
import type { Appearance } from "@/composables/useAppearance";
import { useAppearance } from "@/composables/useAppearance";

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === "dark");

const toggleAppearance = (val: boolean) => {
    const http = useHttp({
        mode: "",
    });
    const next: Appearance = val ? "dark" : "light";
    updateAppearance(next);
    http.mode = next;

    http.patch(route("appearance.update"))
        .then()
        .catch(() => {
            console.error(
                "Something went wrong while updating user appearance.",
            );
        });
};
</script>

<template>
    <Switch :model-value="isDark" @update:model-value="toggleAppearance">
        <template #thumb>
            <div class="flex items-center justify-center w-full h-full">
                <Moon v-if="isDark" class="size-2.5 text-blue-300" />
                <Sun v-else class="size-2.5 text-amber-500" />
            </div>
        </template>
    </Switch>
</template>
