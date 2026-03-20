<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from "@/components/ui/empty";

const props = withDefaults(
    defineProps<{
        status?: number;
    }>(),
    {
        status: 404,
    },
);

const statusCode = computed(() => props.status ?? 500);
const statusCopy = computed(() => {
    const copy = {
        503: {
            title: "503: Service Unavailable",
            description:
                "Sorry, we are doing some maintenance. Please check back soon.",
        },
        500: {
            title: "500: Server Error",
            description: "Whoops, something went wrong on our servers.",
        },
        404: {
            title: "404: Page Not Found",
            description:
                "Sorry, the page you are looking for could not be found.",
        },
        403: {
            title: "403: Forbidden",
            description: "Sorry, you are forbidden from accessing this page.",
        },
    } as const;

    return (
        copy[statusCode.value as keyof typeof copy] ?? {
            title: `${statusCode.value}: Unexpected Error`,
            description: "Sorry, something went wrong. Please try again later.",
        }
    );
});

const title = computed(() => statusCopy.value.title);
const description = computed(() => statusCopy.value.description);
const page = usePage();
const retryAfter = computed(() => {
    const value = page.props.retryAfter as number | string | null | undefined;
    if (statusCode.value !== 503) {
        return null;
    }

    return value ?? null;
});
const showRetry = computed(() => statusCode.value >= 500);

const handleRetry = () => {
    window.location.reload();
};
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-[70vh] items-center justify-center px-6 py-12">
        <Empty class="w-full max-w-2xl">
            <EmptyHeader>
                <span
                    class="rounded-full border border-border/60 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                >
                    Status {{ statusCode }}
                </span>
                <EmptyTitle>{{ title }}</EmptyTitle>
                <EmptyDescription>{{ description }}</EmptyDescription>
                <EmptyDescription v-if="retryAfter">
                    Retry after {{ retryAfter }} seconds.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent class="sm:flex-row sm:justify-center">
                <Button v-if="showRetry" variant="outline" @click="handleRetry">
                    Try again
                </Button>
                <Button as-child>
                    <Link :href="route('dashboard')">Back home</Link>
                </Button>
            </EmptyContent>
        </Empty>
    </div>
</template>

<style scoped></style>
