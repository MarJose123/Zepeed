import { Icon } from "@iconify/vue";
import { Gauge, Rocket } from "@lucide/vue";
import type { Component } from "vue";
import { h } from "vue";

/**
 * Returns a render function (functional component) for a given speedtest
 * provider slug (@see \App\Enums\SpeedtestServer).
 * Uses simple-icons where a reliable brand icon exists, falls back to a
 * distinct lucide icon for providers without one (Ookla, LibreSpeed).
 */
export function providerIcon(slug: string | null): Component {
    const simpleIconsMap: Record<string, string> = {
        netflix: "simple-icons:netflix",
        cloudflare: "simple-icons:cloudflare",
        ookla: "simple-icons:speedtest",
    };

    const lucideMap: Record<string, Component> = {
        librespeed: Rocket,
    };

    if (!slug) {
        return Gauge;
    }

    const iconifySlug = simpleIconsMap[slug] ?? null;

    if (iconifySlug) {
        return () => h(Icon, { icon: iconifySlug, class: "h-3.5 w-3.5" });
    }

    return lucideMap[slug] ?? Gauge;
}
