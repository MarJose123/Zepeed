<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Loader2, Plus } from "@lucide/vue";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { TokenAbilityGroup } from "@/types/api-token";

const props = defineProps<{
    abilities: TokenAbilityGroup[];
}>();

const open = ref(false);

const form = useForm({
    name: "",
    expires_at: "",
    abilities: [] as string[],
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

function toggleAbility(
    value: string,
    checked: boolean | "indeterminate",
): void {
    const set = new Set(form.abilities);

    if (checked === true) {
        set.add(value);
    } else {
        set.delete(value);
    }

    form.abilities = Array.from(set);
}

function allSelected(group: TokenAbilityGroup): boolean {
    return group.abilities.every((ability) =>
        form.abilities.includes(ability.value),
    );
}

function someSelected(group: TokenAbilityGroup): boolean {
    const selected = group.abilities.filter((ability) =>
        form.abilities.includes(ability.value),
    ).length;

    return selected > 0 && selected < group.abilities.length;
}

function groupModelValue(group: TokenAbilityGroup): boolean | "indeterminate" {
    if (allSelected(group)) return true;

    if (someSelected(group)) return "indeterminate";

    return false;
}

function toggleGroup(group: TokenAbilityGroup, checked: boolean): void {
    const set = new Set(form.abilities);

    group.abilities.forEach((ability) => {
        if (checked) {
            set.add(ability.value);
        } else {
            set.delete(ability.value);
        }
    });

    form.abilities = Array.from(set);
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => (v ? (open = true) : close())">
        <Button size="sm" @click="open = true">
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Create Token
        </Button>

        <DialogContent class="max-w-lg">
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

                <div class="space-y-1.5">
                    <Label class="text-xs">Token Abilities</Label>
                    <div
                        class="max-h-52 space-y-2 overflow-y-auto rounded-md border p-2"
                    >
                        <div
                            v-for="group in props.abilities"
                            :key="group.module"
                            class="space-y-1"
                        >
                            <label
                                class="flex cursor-pointer items-center gap-2 text-xs font-medium"
                            >
                                <Checkbox
                                    :model-value="groupModelValue(group)"
                                    @update:model-value="
                                        (v) => toggleGroup(group, v === true)
                                    "
                                />
                                {{ group.module }}
                            </label>
                            <div class="grid grid-cols-2 gap-1 pl-6">
                                <label
                                    v-for="ability in group.abilities"
                                    :key="ability.value"
                                    class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <Checkbox
                                        :model-value="
                                            form.abilities.includes(
                                                ability.value,
                                            )
                                        "
                                        @update:model-value="
                                            (v) =>
                                                toggleAbility(ability.value, v)
                                        "
                                    />
                                    {{ ability.label }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="form.errors.abilities"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.abilities }}
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
