<script setup lang="ts">
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { MailDriver } from "@/types/mail";

const props = defineProps<{
    driver: MailDriver;
    config: Record<string, string>;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    "update:config": [config: Record<string, string>];
}>();

const update = (key: string, value: string | number) => {
    emit("update:config", { ...props.config, [key]: String(value) });
};

const updateSelect = (key: string, value: unknown) => {
    if (typeof value === "string" || typeof value === "number") {
        emit("update:config", { ...props.config, [key]: String(value) });
    }
};

/** Resolve a dotted key e.g. "config.host" → errors["config.host"] */
const e = (field: string): string | undefined =>
    props.errors?.[`config.${field}`];
</script>

<template>
    <!-- SMTP -->
    <template v-if="driver === 'smtp'">
        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-1 space-y-1.5">
                <Label class="text-xs">Host</Label>
                <Input
                    :model-value="config.host ?? ''"
                    placeholder="smtp.example.com"
                    class="text-xs"
                    :class="{ 'border-destructive': e('host') }"
                    @update:model-value="update('host', $event)"
                />
                <p v-if="e('host')" class="text-destructive text-[10px]">
                    {{ e("host") }}
                </p>
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">Port</Label>
                <Input
                    :model-value="config.port ?? ''"
                    placeholder="587"
                    type="number"
                    class="text-xs"
                    :class="{ 'border-destructive': e('port') }"
                    @update:model-value="update('port', $event)"
                />
                <p v-if="e('port')" class="text-destructive text-[10px]">
                    {{ e("port") }}
                </p>
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">Encryption</Label>
                <Select
                    :model-value="config.encryption ?? 'tls'"
                    @update:model-value="
                        (val) => updateSelect('encryption', val)
                    "
                >
                    <SelectTrigger
                        class="text-xs"
                        :class="{ 'border-destructive': e('encryption') }"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="tls">TLS</SelectItem>
                        <SelectItem value="ssl">SSL</SelectItem>
                        <SelectItem value="none">None</SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="e('encryption')" class="text-destructive text-[10px]">
                    {{ e("encryption") }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <Label class="text-xs">
                    Username
                    <span class="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    :model-value="config.username ?? ''"
                    placeholder="user@example.com"
                    class="text-xs"
                    :class="{ 'border-destructive': e('username') }"
                    @update:model-value="update('username', $event)"
                />
                <p v-if="e('username')" class="text-destructive text-[10px]">
                    {{ e("username") }}
                </p>
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">
                    Password
                    <span class="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    :model-value="config.password ?? ''"
                    type="password"
                    placeholder="••••••••"
                    class="text-xs"
                    :class="{ 'border-destructive': e('password') }"
                    @update:model-value="update('password', $event)"
                />
                <p v-if="e('password')" class="text-destructive text-[10px]">
                    {{ e("password") }}
                </p>
            </div>
        </div>
    </template>

    <!-- Resend -->
    <template v-else-if="driver === 'resend'">
        <div class="space-y-1.5">
            <Label class="text-xs">API key</Label>
            <Input
                :model-value="config.api_key ?? ''"
                placeholder="re_••••••••••••••••"
                class="font-mono text-xs"
                :class="{ 'border-destructive': e('api_key') }"
                @update:model-value="update('api_key', $event)"
            />
            <p v-if="e('api_key')" class="text-destructive text-[10px]">
                {{ e("api_key") }}
            </p>
            <p v-else class="text-muted-foreground text-[10px]">
                Get your API key from

                <a
                    href="https://resend.com/api-keys"
                    target="_blank"
                    class="underline"
                    >resend.com</a
                >
            </p>
        </div>
    </template>

    <!-- Mailgun -->
    <template v-else-if="driver === 'mailgun'">
        <div class="space-y-1.5">
            <Label class="text-xs">API key</Label>
            <Input
                :model-value="config.api_key ?? ''"
                placeholder="key-••••••••••••••••••••••••••••••"
                class="font-mono text-xs"
                :class="{ 'border-destructive': e('api_key') }"
                @update:model-value="update('api_key', $event)"
            />
            <p v-if="e('api_key')" class="text-destructive text-[10px]">
                {{ e("api_key") }}
            </p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <Label class="text-xs">Domain</Label>
                <Input
                    :model-value="config.domain ?? ''"
                    placeholder="mg.yourdomain.com"
                    class="text-xs"
                    :class="{ 'border-destructive': e('domain') }"
                    @update:model-value="update('domain', $event)"
                />
                <p v-if="e('domain')" class="text-destructive text-[10px]">
                    {{ e("domain") }}
                </p>
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">
                    Endpoint
                    <span class="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    :model-value="config.endpoint ?? ''"
                    placeholder="api.mailgun.net"
                    class="text-xs"
                    :class="{ 'border-destructive': e('endpoint') }"
                    @update:model-value="update('endpoint', $event)"
                />
                <p v-if="e('endpoint')" class="text-destructive text-[10px]">
                    {{ e("endpoint") }}
                </p>
            </div>
        </div>
    </template>

    <!-- Postmark -->
    <template v-else-if="driver === 'postmark'">
        <div class="space-y-1.5">
            <Label class="text-xs">Server token</Label>
            <Input
                :model-value="config.token ?? ''"
                placeholder="••••••••-••••-••••-••••-••••••••••••"
                class="font-mono text-xs"
                :class="{ 'border-destructive': e('token') }"
                @update:model-value="update('token', $event)"
            />
            <p v-if="e('token')" class="text-destructive text-[10px]">
                {{ e("token") }}
            </p>
            <p v-else class="text-muted-foreground text-[10px]">
                Find your token in

                <a
                    href="https://account.postmarkapp.com/servers"
                    target="_blank"
                    class="underline"
                    >Postmark servers</a
                >
            </p>
        </div>
    </template>

    <!-- Amazon SES -->
    <template v-else-if="driver === 'ses'">
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <Label class="text-xs">Access key</Label>
                <Input
                    :model-value="config.key ?? ''"
                    placeholder="AKIA••••••••••••••••"
                    class="font-mono text-xs"
                    :class="{ 'border-destructive': e('key') }"
                    @update:model-value="update('key', $event)"
                />
                <p v-if="e('key')" class="text-destructive text-[10px]">
                    {{ e("key") }}
                </p>
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">Secret key</Label>
                <Input
                    :model-value="config.secret ?? ''"
                    type="password"
                    placeholder="••••••••••••••••••••••••••••••••••••••••"
                    class="text-xs"
                    :class="{ 'border-destructive': e('secret') }"
                    @update:model-value="update('secret', $event)"
                />
                <p v-if="e('secret')" class="text-destructive text-[10px]">
                    {{ e("secret") }}
                </p>
            </div>
        </div>
        <div class="space-y-1.5">
            <Label class="text-xs">Region</Label>
            <Select
                :model-value="config.region ?? 'us-east-1'"
                @update:model-value="(val) => updateSelect('region', val)"
            >
                <SelectTrigger
                    class="text-xs"
                    :class="{ 'border-destructive': e('region') }"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="us-east-1"
                        >us-east-1 (N. Virginia)</SelectItem
                    >
                    <SelectItem value="us-east-2">us-east-2 (Ohio)</SelectItem>
                    <SelectItem value="us-west-1"
                        >us-west-1 (N. California)</SelectItem
                    >
                    <SelectItem value="us-west-2"
                        >us-west-2 (Oregon)</SelectItem
                    >
                    <SelectItem value="eu-west-1"
                        >eu-west-1 (Ireland)</SelectItem
                    >
                    <SelectItem value="eu-central-1"
                        >eu-central-1 (Frankfurt)</SelectItem
                    >
                    <SelectItem value="ap-southeast-1"
                        >ap-southeast-1 (Singapore)</SelectItem
                    >
                    <SelectItem value="ap-northeast-1"
                        >ap-northeast-1 (Tokyo)</SelectItem
                    >
                </SelectContent>
            </Select>
            <p v-if="e('region')" class="text-destructive text-[10px]">
                {{ e("region") }}
            </p>
        </div>
    </template>

    <!-- Sendmail -->
    <template v-else-if="driver === 'sendmail'">
        <div class="space-y-1.5">
            <Label class="text-xs">
                Sendmail path
                <span class="text-muted-foreground">(optional)</span>
            </Label>
            <Input
                :model-value="config.path ?? ''"
                placeholder="/usr/sbin/sendmail -bs -i"
                class="font-mono text-xs"
                :class="{ 'border-destructive': e('path') }"
                @update:model-value="update('path', $event)"
            />
            <p v-if="e('path')" class="text-destructive text-[10px]">
                {{ e("path") }}
            </p>
            <p v-else class="text-muted-foreground text-[10px]">
                Leave blank to use the default system sendmail path.
            </p>
        </div>
    </template>
</template>
