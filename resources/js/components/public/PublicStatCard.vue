<script setup lang="ts">
import {
    Database,
    ArrowDown,
    ArrowUp,
    Server,
    Activity,
} from "@lucide/vue";
import { Card, CardContent } from "@/components/ui/card";

const iconMap = {
    database: Database,
    "arrow-down": ArrowDown,
    "arrow-up": ArrowUp,
    activity: Activity,
    server: Server,
} as const;

const props = defineProps<{
    label: string;
    value: string | number;
    unit?: string;
    icon?: keyof typeof iconMap;
}>();

const IconComponent = props.icon ? iconMap[props.icon] : null;
</script>

<template>
    <Card class="rounded-lg">
        <CardContent class="p-3">
            <p
                class="text-muted-foreground mb-1 flex items-center gap-1.5 text-sm font-bold"
            >
                <component
                    :is="IconComponent"
                    v-if="IconComponent"
                    class="h-3 w-3"
                    aria-hidden="true"
                />
                {{ label }}
            </p>
            <p class="text-2xl font-bold tracking-tight pl-2">
                {{ value }}
                <span
                    v-if="unit"
                    class="text-muted-foreground ml-1 text-xs font-normal"
                >
                    {{ unit }}
                </span>
            </p>
        </CardContent>
    </Card>
</template>
