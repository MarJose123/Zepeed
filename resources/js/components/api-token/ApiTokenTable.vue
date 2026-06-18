<script setup lang="ts">
import { ref } from "vue";
import ApiTokenRevokeDialog from "@/components/api-token/ApiTokenRevokeDialog.vue";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { ApiToken } from "@/types/api-token";

defineProps<{
    tokens: ApiToken[];
}>();

const revokeTarget = ref<ApiToken | null>(null);
</script>

<template>
    <div class="rounded-md border">
        <Table v-if="tokens.length > 0">
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead>Last Used</TableHead>
                    <TableHead>Last IP</TableHead>
                    <TableHead>Last Agent</TableHead>
                    <TableHead>Expires</TableHead>
                    <TableHead />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="token in tokens" :key="token.id">
                    <TableCell class="text-sm font-medium">
                        {{ token.name }}
                    </TableCell>
                    <TableCell class="text-sm text-muted-foreground">
                        {{ token.created_at }}
                    </TableCell>
                    <TableCell class="text-sm text-muted-foreground">
                        {{ token.last_used_at ?? "Never" }}
                    </TableCell>
                    <TableCell class="text-sm text-muted-foreground">
                        {{ token.last_used_ip ?? "—" }}
                    </TableCell>
                    <TableCell
                        class="max-w-[180px] truncate text-sm text-muted-foreground"
                    >
                        {{ token.last_used_agent ?? "—" }}
                    </TableCell>
                    <TableCell>
                        <Badge
                            v-if="token.is_expired"
                            variant="destructive"
                            class="text-xs"
                        >
                            Expired
                        </Badge>
                        <span
                            v-else-if="token.expires_at"
                            class="text-sm text-muted-foreground"
                        >
                            {{ token.expires_at }}
                        </span>
                        <span v-else class="text-sm text-muted-foreground"
                            >Never</span
                        >
                    </TableCell>
                    <TableCell class="text-right">
                        <Badge
                            variant="destructive"
                            class="cursor-pointer text-xs"
                            @click="revokeTarget = token"
                        >
                            Revoke
                        </Badge>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <div
            v-else
            class="flex flex-col items-center justify-center gap-2 py-12"
        >
            <p class="text-sm font-medium">No API tokens yet</p>
            <p class="text-[11px] text-muted-foreground">
                Create your first token to enable programmatic access.
            </p>
        </div>
    </div>

    <ApiTokenRevokeDialog :token="revokeTarget" @close="revokeTarget = null" />
</template>

<style scoped></style>
