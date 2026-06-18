<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Loader2, Plus } from "@lucide/vue";
import { ref } from "vue";
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

const open = ref(false);

const form = useForm({
    name: "",
    expires_at: "",
});

function close(): void {
    open.value = false;
    form.reset();
}

function save(): void {
    form.post(route("api-tokens.store"), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => (v ? (open = true) : close())">
        <Button size="sm" @click="open = true">
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Create Token
        </Button>

        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="text-sm font-medium"
                    >Create API Token</DialogTitle
                >
            </DialogHeader>

            <div class="space-y-3">
                <div class="space-y-1.5">
                    <Label class="text-xs">Token Name</Label>
                    <Input
                        v-model="form.name"
                        placeholder="e.g. Production API"
                        class="text-xs"
                        @keyup.enter="save"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label class="text-xs">
                        Expires At
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        >
                    </Label>
                    <Input
                        v-model="form.expires_at"
                        type="datetime-local"
                        class="text-xs"
                    />
                    <p
                        v-if="form.errors.expires_at"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.expires_at }}
                    </p>
                </div>
            </div>

            <DialogFooter class="gap-2 pt-2">
                <Button variant="secondary" size="sm" @click="close"
                    >Cancel</Button
                >
                <Button size="sm" :disabled="form.processing" @click="save">
                    <Loader2
                        v-if="form.processing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    Create Token
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
