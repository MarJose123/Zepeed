<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import type { PingTarget } from "@/types/ping";

const props = defineProps<{ target: PingTarget | null }>();
const emit = defineEmits<{ close: [] }>();

const confirm = () => {
    if (!props.target) return;

    router.delete(
        route(
            "speedtest.network.ping-targets.destroy",
            { pingTarget: props.target.id },
            false,
        ),
        { preserveScroll: true, onSuccess: () => emit("close") },
    );
    emit("close");
};
</script>

<template>
    <AlertDialog :open="!!target" @update:open="(v) => !v && emit('close')">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete ping target?</AlertDialogTitle>
                <AlertDialogDescription>
                    Delete <strong>{{ target?.label }}</strong> ({{
                        target?.host
                    }})? All associated results and alert rules will also be
                    removed. This cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="emit('close')"
                    >Cancel</AlertDialogCancel
                >
                <Button variant="destructive" size="sm" @click="confirm"
                    >Delete</Button
                >
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
