<script setup lang="ts">
import { Bell, RefreshCcw } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui/empty";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/components/ui/sheet";
</script>

<template>
    <Sheet>
        <SheetTrigger as-child>
            <Button variant="ghost" class="rounded-full">
                <component :is="Bell" />
            </Button>
        </SheetTrigger>
        <SheetContent>
            <SheetHeader>
                <SheetTitle>Notifications</SheetTitle>
                <SheetDescription>
                    New notifications will appear here.
                </SheetDescription>
            </SheetHeader>

            <Empty
                class="from-muted/50 to-background h-full bg-linear-to-b from-30%"
            >
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Bell />
                    </EmptyMedia>
                    <EmptyTitle>No Notifications</EmptyTitle>
                    <EmptyDescription>
                        You're all caught up. New notifications will appear
                        here.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button variant="outline" size="sm">
                        <RefreshCcw />
                        Refresh
                    </Button>
                </EmptyContent>
            </Empty>
        </SheetContent>
    </Sheet>
</template>
