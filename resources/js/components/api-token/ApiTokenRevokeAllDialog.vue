<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Loader2 } from "@lucide/vue";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { InputPassword } from "@/components/ui/input-password";

defineProps<{
    disabled: boolean;
}>();

const open = ref(false);

const form = useForm({ password: "" });

function close(): void {
    open.value = false;
    form.reset();
    form.clearErrors();
}

function confirm(): void {
    form.delete(route("api-tokens.revoke-all"), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => (v ? (open = true) : close())">
        <Button
            variant="destructive"
            size="sm"
            :disabled="disabled"
            @click="open = true"
        >
            Revoke All
        </Button>

        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="text-sm font-medium">
                    Revoke all tokens?
                </DialogTitle>
                <DialogDescription>
                    All your API tokens will be immediately invalidated. Any
                    active integrations using these tokens will stop working.
                    Enter your password to confirm.
                </DialogDescription>
            </DialogHeader>

            <FieldGroup class="grid gap-2">
                <Field :data-invalid="form.errors.hasOwnProperty('password')">
                    <FieldLabel for="revoke-all-password" class="sr-only">
                        Password
                    </FieldLabel>
                    <InputPassword
                        id="revoke-all-password"
                        name="password"
                        placeholder="Confirm your password"
                        v-model="form.password"
                        @update:model-value="form.clearErrors('password')"
                        @keyup.enter="confirm"
                    />
                    <FieldError v-if="form.errors.password">
                        {{ form.errors.password }}
                    </FieldError>
                </Field>
            </FieldGroup>

            <DialogFooter class="gap-2">
                <Button variant="secondary" size="sm" @click="close">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    :disabled="form.processing"
                    @click="confirm"
                >
                    <Loader2
                        v-if="form.processing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    Revoke All
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
