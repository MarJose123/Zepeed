<script setup lang="ts">
import { Search, ServerCrash, Loader2, MapPin, Building2 } from "@lucide/vue";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { OoklaServer } from "@/types/provider";

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: "select", server: OoklaServer): void;
    (e: "close"): void;
}>();

const query = ref("");
const results = ref<OoklaServer[]>([]);
const isLoading = ref(false);
const hasSearched = ref(false);
const errorMsg = ref<string | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const onQueryInput = () => {
    if (debounceTimer) clearTimeout(debounceTimer);

    errorMsg.value = null;

    if (query.value.trim().length < 2) {
        results.value = [];
        hasSearched.value = false;

        return;
    }

    debounceTimer = setTimeout(() => fetchServers(), 400);
};

const fetchServers = async () => {
    isLoading.value = true;
    hasSearched.value = true;
    errorMsg.value = null;

    try {
        const url = route("speedtest.server.ookla.servers.search", {}, false);
        const res = await fetch(
            `${url}?q=${encodeURIComponent(query.value.trim())}`,
            {
                headers: { Accept: "application/json" },
            },
        );

        if (!res.ok) {
            errorMsg.value =
                "Unable to reach the Ookla server list. Try again.";
            results.value = [];

            return;
        }

        const data = (await res.json()) as {
            results: OoklaServer[];
            count: number;
        };
        results.value = data.results;
    } catch {
        errorMsg.value = "Network error. Check your connection and try again.";
        results.value = [];
    } finally {
        isLoading.value = false;
    }
};

const onSelect = (server: OoklaServer) => {
    emit("select", server);
    emit("close");
};

const onClose = () => {
    query.value = "";
    results.value = [];
    hasSearched.value = false;
    errorMsg.value = null;
    emit("close");
};
</script>

<template>
    <Dialog :open="open" @update:open="(v) => !v && onClose()">
        <DialogContent class="max-w-xl p-0 gap-0 overflow-hidden">
            <DialogHeader class="px-5 pt-5 pb-4 border-b border-border">
                <DialogTitle class="text-base font-bold">
                    Search Ookla Servers
                </DialogTitle>
                <DialogDescription
                    class="text-[11px] text-muted-foreground mt-0.5"
                >
                    Results are cached for 24 hours. Click a row to select that
                    server.
                </DialogDescription>
            </DialogHeader>

            <!-- Search input -->
            <div class="px-5 py-3 border-b border-border">
                <div class="relative">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none"
                    />
                    <Input
                        v-model="query"
                        type="text"
                        placeholder="Search by city, sponsor, or country…"
                        class="pl-9 text-sm"
                        autofocus
                        @input="onQueryInput"
                    />
                </div>
            </div>

            <!-- Results -->
            <ScrollArea class="h-80">
                <!-- Loading -->
                <div
                    v-if="isLoading"
                    class="flex flex-col items-center justify-center gap-2 py-16"
                >
                    <Loader2
                        class="h-5 w-5 animate-spin text-muted-foreground"
                    />
                    <p class="text-xs text-muted-foreground">
                        Searching servers…
                    </p>
                </div>

                <!-- Error -->
                <div
                    v-else-if="errorMsg"
                    class="flex flex-col items-center justify-center gap-2 py-16 px-6 text-center"
                >
                    <ServerCrash class="h-5 w-5 text-destructive" />
                    <p class="text-xs text-destructive">{{ errorMsg }}</p>
                </div>

                <!-- Empty — no query yet -->
                <div
                    v-else-if="!hasSearched"
                    class="flex flex-col items-center justify-center gap-2 py-16"
                >
                    <Search class="h-5 w-5 text-muted-foreground" />
                    <p class="text-xs text-muted-foreground">
                        Type at least 2 characters to search
                    </p>
                </div>

                <!-- Empty — searched but nothing found -->
                <div
                    v-else-if="hasSearched && results.length === 0"
                    class="flex flex-col items-center justify-center gap-2 py-16"
                >
                    <Search class="h-5 w-5 text-muted-foreground" />
                    <p class="text-xs text-muted-foreground">
                        No servers matched
                        <span class="font-medium text-foreground"
                            >"{{ query }}"</span
                        >
                    </p>
                </div>

                <!-- Results table -->
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-20">ID</TableHead>
                            <TableHead>Sponsor</TableHead>
                            <TableHead>Location</TableHead>
                            <TableHead class="text-right w-24"
                                >Distance</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="server in results"
                            :key="server.id"
                            class="cursor-pointer"
                            @click="onSelect(server)"
                        >
                            <TableCell class="text-sm text-muted-foreground">
                                {{ server.id }}
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-1.5">
                                    <Building2
                                        class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                                    />
                                    <span class="text-sm">{{
                                        server.sponsor
                                    }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-1.5">
                                    <MapPin
                                        class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                                    />
                                    <span class="text-sm"
                                        >{{ server.name }},
                                        {{ server.country }}</span
                                    >
                                </div>
                            </TableCell>
                            <TableCell
                                class="text-right text-sm text-muted-foreground"
                            >
                                {{ server.distance.toFixed(1) }} km
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </ScrollArea>

            <!-- Footer -->
            <div
                class="flex items-center justify-between px-5 py-3 border-t border-border bg-muted/30"
            >
                <p class="text-[11px] text-muted-foreground">
                    Showing up to 20 nearest results
                </p>
                <Button variant="outline" size="sm" @click="onClose">
                    Cancel
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
