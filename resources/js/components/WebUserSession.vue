<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import RevokeAllUserDialog from "@/components/RevokeAllUserDialog.vue";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { WebSession } from "@/types";

defineProps<{
    webSessions: WebSession[];
}>();

const form = useForm({});

const revokeSession = (session: WebSession) => {
    form.transform((data) => ({
        ...data,
        session_id: session.session_id,
    })).delete(route("user-sessions.revoke"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex w-full flex-col gap-4">
        <div class="flex w-full flex-row-reverse">
            <RevokeAllUserDialog />
        </div>
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="font-bold">Device</TableHead>
                    <TableHead class="font-bold">IP Address</TableHead>
                    <TableHead class="font-bold">Location</TableHead>
                    <TableHead class="font-bold">Activity</TableHead>
                    <TableHead class="font-bold">Action</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="(session, i) in webSessions" :key="i">
                    <TableCell>
                        {{
                            session.agent.browser
                                ? session.agent.browser
                                : "Unknown"
                        }}
                        /
                        {{
                            session.agent.platform
                                ? session.agent.platform
                                : "Unknown"
                        }}
                    </TableCell>
                    <TableCell>{{ session.ip_address }}</TableCell>
                    <TableCell>
                        <template v-if="!session.agent.country">
                            Unknown
                        </template>
                        <template v-else>
                            {{ session.agent.city }},
                            {{ session.agent.country }}
                        </template>
                    </TableCell>
                    <TableCell>
                        <template v-if="session.is_current_device">
                            <Badge variant="outline">Current Device</Badge>
                        </template>
                        <template v-else>
                            {{ session.last_active }}
                        </template>
                    </TableCell>
                    <TableCell>
                        <template v-if="session.is_current_device">—</template>
                        <Badge
                            v-else
                            variant="destructive"
                            class="cursor-default"
                            @click.prevent="revokeSession(session)"
                        >
                            Revoke
                        </Badge>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>

<style scoped></style>
