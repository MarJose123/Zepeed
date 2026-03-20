<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import { useTemplateRef } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogHeader,
    DialogTrigger,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogClose,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";

const passwordInput = useTemplateRef("passwordInput");
</script>

<template>
    <div class="space-y-6">
        <Dialog>
            <DialogTrigger as-child>
                <Button variant="destructive" size="sm">Revoke All</Button>
            </DialogTrigger>
            <DialogContent>
                <Form
                    method="delete"
                    :action="route('user-sessions.destroy')"
                    reset-on-success
                    @error="() => passwordInput?.$el?.focus()"
                    :options="{
                        preserveScroll: true,
                    }"
                    class="space-y-6"
                    v-slot="{ errors, processing, reset, clearErrors }"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle
                            >Are you sure you want to revoke all of your account
                            session?</DialogTitle
                        >
                        <DialogDescription>
                            Once your account session is revoked, all of it will
                            be logged out on other browser. Please enter your
                            password to confirm you would like to revoke all
                            your account session.
                        </DialogDescription>
                    </DialogHeader>

                    <FieldGroup class="grid gap-2">
                        <Field
                            :data-invalid="errors.hasOwnProperty('password')"
                        >
                            <FieldLabel for="password" class="sr-only"
                                >Password</FieldLabel
                            >
                            <Input
                                ref="passwordInput"
                                name="password"
                                id="password"
                                type="password"
                                @update:modelValue="
                                    () => clearErrors('password')
                                "
                            />
                            <FieldError v-if="errors.password">{{
                                errors.password
                            }}</FieldError>
                        </Field>
                    </FieldGroup>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button
                                variant="secondary"
                                @click="
                                    () => {
                                        clearErrors();
                                        reset();
                                    }
                                "
                            >
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                            data-test="confirm-delete-user-button"
                        >
                            Revoke All
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped></style>
