<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Loader2 } from "@lucide/vue";
import { watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import type { PingTarget } from "@/types/ping";

const props = defineProps<{
    open: boolean;
    target: PingTarget | null;
}>();

const emit = defineEmits<{ close: [] }>();

const form = useForm({
    label: "",
    host: "",
    packets: 4,
    timeout_seconds: 5,
    is_enabled: true,
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        if (props.target) {
            form.label = props.target.label;
            form.host = props.target.host;
            form.packets = props.target.packets;
            form.timeout_seconds = props.target.timeout_seconds;
            form.is_enabled = props.target.is_enabled;
        } else {
            form.reset();
            form.packets = 4;
            form.timeout_seconds = 5;
            form.is_enabled = true;
        }
    },
);

const submit = () => {
    if (props.target) {
        form.patch(
            route(
                "speedtest.network.ping-targets.update",
                { pingTarget: props.target.id },
                false,
            ),
            { preserveScroll: true, onSuccess: () => emit("close") },
        );
    } else {
        form.post(route("speedtest.network.ping-targets.store", {}, false), {
            preserveScroll: true,
            onSuccess: () => emit("close"),
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(v) => !v && emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    target ? "Edit Ping Target" : "Add Ping Target"
                }}</DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <div class="space-y-1.5">
                    <Label>Label <span class="text-destructive">*</span></Label>
                    <Input
                        v-model="form.label"
                        placeholder="e.g. Primary DNS"
                    />
                    <p
                        v-if="form.errors.label"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.label }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label
                        >Host / IP Address
                        <span class="text-destructive">*</span></Label
                    >
                    <Input
                        v-model="form.host"
                        placeholder="8.8.8.8 or hostname"
                    />
                    <p class="text-[11px] text-muted-foreground">
                        IPv4, IPv6, or resolvable hostname
                    </p>
                    <p v-if="form.errors.host" class="text-xs text-destructive">
                        {{ form.errors.host }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Packets per Test</Label>
                        <Select
                            :model-value="String(form.packets)"
                            @update:model-value="
                                (v) => (form.packets = Number(v))
                            "
                        >
                            <SelectTrigger class="h-9"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="n in [1, 2, 4, 8, 16]"
                                    :key="n"
                                    :value="String(n)"
                                    >{{ n }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Timeout (sec)</Label>
                        <Select
                            :model-value="String(form.timeout_seconds)"
                            @update:model-value="
                                (v) => (form.timeout_seconds = Number(v))
                            "
                        >
                            <SelectTrigger class="h-9"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="n in [1, 2, 3, 5, 10, 15, 30]"
                                    :key="n"
                                    :value="String(n)"
                                    >{{ n }}s</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between rounded-lg border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">Enabled</p>
                        <p class="text-[11px] text-muted-foreground">
                            Include in scheduled ping tests
                        </p>
                    </div>
                    <Switch v-model:model-value="form.is_enabled" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="emit('close')">Cancel</Button>
                <Button :disabled="form.processing" @click="submit">
                    <Loader2
                        v-if="form.processing"
                        class="mr-1.5 h-4 w-4 animate-spin"
                    />
                    {{ target ? "Save Changes" : "Add Target" }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
