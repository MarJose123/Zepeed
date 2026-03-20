<script setup lang="ts">
import { SunMoon } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import type { Appearance } from "@/composables/useAppearance";
import { useAppearance } from "@/composables/useAppearance";
import axiosClient from "@/lib/axios";

const { appearance, updateAppearance } = useAppearance();

const toggleAppearance = (appearance: Appearance) => {
    updateAppearance(appearance);
    // persist user appearance into the system database
    axiosClient
        .patch(route("appearance.update"), { mode: appearance })
        .then()
        .catch(() => {
            console.error(
                "Something went wrong while updating user appearance.",
            );
        });
};
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    variant="ghost"
                    class="rounded-full"
                    @click="
                        toggleAppearance(
                            appearance === 'dark' ? 'light' : 'dark',
                        )
                    "
                >
                    <component :is="SunMoon" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>Toggle Display Mode (light/dark)</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>

<style scoped></style>
