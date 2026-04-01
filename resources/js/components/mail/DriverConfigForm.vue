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
}>();

const emit = defineEmits<{
    "update:config": [config: Record<string, string>];
}>();

const update = (key: string, value: string) => {
    emit("update:config", { ...props.config, [key]: value });
};
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
                    @update:model-value="update('host', $event)"
                />
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">Port</Label>
                <Input
                    :model-value="config.port ?? ''"
                    placeholder="587"
                    type="number"
                    class="text-xs"
                    @update:model-value="update('port', $event)"
                />
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">Encryption</Label>
                <Select
                    :model-value="config.encryption ?? 'tls'"
                    @update:model-value="update('encryption', $event)"
                >
                    <SelectTrigger class="text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="tls">TLS</SelectItem>
                        <SelectItem value="ssl">SSL</SelectItem>
                        <SelectItem value="none">None</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <Label class="text-xs">Username</Label>
                <Input
                    :model-value="config.username ?? ''"
                    placeholder="user@example.com"
                    class="text-xs"
                    @update:model-value="update('username', $event)"
                />
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">Password</Label>
                <Input
                    :model-value="config.password ?? ''"
                    type="password"
                    placeholder="••••••••"
                    class="text-xs"
                    @update:model-value="update('password', $event)"
                />
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
                @update:model-value="update('api_key', $event)"
            />
            <p class="text-muted-foreground text-[10px]">
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
                @update:model-value="update('api_key', $event)"
            />
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <Label class="text-xs">Domain</Label>
                <Input
                    :model-value="config.domain ?? ''"
                    placeholder="mg.yourdomain.com"
                    class="text-xs"
                    @update:model-value="update('domain', $event)"
                />
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs"
                    >Endpoint
                    <span class="text-muted-foreground">(optional)</span></Label
                >
                <Input
                    :model-value="config.endpoint ?? ''"
                    placeholder="api.mailgun.net"
                    class="text-xs"
                    @update:model-value="update('endpoint', $event)"
                />
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
                @update:model-value="update('token', $event)"
            />
            <p class="text-muted-foreground text-[10px]">
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
                    @update:model-value="update('key', $event)"
                />
            </div>
            <div class="space-y-1.5">
                <Label class="text-xs">Secret key</Label>
                <Input
                    :model-value="config.secret ?? ''"
                    type="password"
                    placeholder="••••••••••••••••••••••••••••••••••••••••"
                    class="text-xs"
                    @update:model-value="update('secret', $event)"
                />
            </div>
        </div>
        <div class="space-y-1.5">
            <Label class="text-xs">Region</Label>
            <Select
                :model-value="config.region ?? 'us-east-1'"
                @update:model-value="update('region', $event)"
            >
                <SelectTrigger class="text-xs">
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
        </div>
    </template>

    <!-- Sendmail -->
    <template v-else-if="driver === 'sendmail'">
        <div class="space-y-1.5">
            <Label class="text-xs"
                >Sendmail path
                <span class="text-muted-foreground">(optional)</span></Label
            >
            <Input
                :model-value="config.path ?? ''"
                placeholder="/usr/sbin/sendmail -bs -i"
                class="font-mono text-xs"
                @update:model-value="update('path', $event)"
            />
            <p class="text-muted-foreground text-[10px]">
                Leave blank to use the default system sendmail path.
            </p>
        </div>
    </template>
</template>
