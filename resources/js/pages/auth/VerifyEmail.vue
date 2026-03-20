<script setup lang="ts">
import { Form, Head, Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Field, FieldDescription, FieldGroup } from "@/components/ui/field";
import { Spinner } from "@/components/ui/spinner";
import GuestLayout from "@/layouts/GuestLayout.vue";

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Email verification" />
    <GuestLayout
        class="bg-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="'flex flex-col gap-6'">
                <Card>
                    <CardContent>
                        <Form
                            class="p-6 md:p-8"
                            method="post"
                            :action="route('verification.send')"
                            v-slot="{ processing }"
                        >
                            <FieldGroup>
                                <div
                                    class="flex flex-col items-center gap-2 text-center"
                                >
                                    <h1 class="text-2xl font-bold">
                                        Verify email
                                    </h1>
                                    <p
                                        class="text-muted-foreground text-sm text-balance"
                                    >
                                        Please verify your email address by
                                        clicking on the link we just emailed to
                                        you.
                                    </p>
                                </div>
                                <Field>
                                    <Button
                                        type="submit"
                                        :disabled="processing"
                                    >
                                        <Spinner v-if="processing" />
                                        Resend verification email
                                    </Button>
                                </Field>
                                <FieldDescription class="text-center">
                                    <Link
                                        method="post"
                                        :href="route('logout', {}, false)"
                                        >Logout</Link
                                    >
                                </FieldDescription>
                            </FieldGroup>
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped></style>
