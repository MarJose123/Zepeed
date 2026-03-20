<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

defineProps<{
    user: {
        avatar?: string;
        name: string;
        email: string;
    };
}>();
</script>

<template>
    <Avatar class="h-8 w-8 rounded-lg">
        <AvatarImage :src="user.avatar" :alt="user.name" />
        <AvatarFallback class="rounded-lg"> CN </AvatarFallback>
    </Avatar>
    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold">{{ user.name }}</span>
        <span class="truncate text-xs">{{ user.email }}</span>
    </div>
</template>

<style scoped></style>
