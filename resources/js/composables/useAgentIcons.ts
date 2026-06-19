import { Icon } from "@iconify/vue";
import { Globe, HelpCircle, Monitor, Smartphone, Tablet } from "@lucide/vue";
import type { Component } from "vue";
import { h } from "vue";

/**
 * Returns a render function (functional component) for a given browser name.
 * Uses simple-icons where available, falls back to a lucide Globe.
 */
export function browserIcon(browser: string | null): Component {
    const map: Record<string, string> = {
        Chrome: "simple-icons:googlechrome",
        Firefox: "simple-icons:firefox",
        Edge: "simple-icons:microsoftedge",
        Safari: "simple-icons:safari",
        Opera: "simple-icons:opera",
        Brave: "simple-icons:brave",
        Vivaldi: "simple-icons:vivaldi",
        "Samsung Browser": "simple-icons:samsung",
        "Yandex Browser": "simple-icons:yandex",
        "UC Browser": "simple-icons:ucbrowser",
        DuckDuckGo: "simple-icons:duckduckgo",
        "Internet Explorer": "simple-icons:internetexplorer",
    };

    const slug = browser ? (map[browser] ?? null) : null;

    if (slug) {
        return () => h(Icon, { icon: slug, class: "h-4 w-4" });
    }

    return browser ? Globe : HelpCircle;
}

/**
 * Returns a render function (functional component) for a given platform name.
 * Uses simple-icons for named OSes, lucide icons for generic mobile/desktop.
 */
export function platformIcon(platform: string | null): Component {
    const simpleIconsMap: Record<string, string> = {
        Windows: "simple-icons:windows",
        Mac: "simple-icons:apple",
        Linux: "simple-icons:linux",
        Ubuntu: "simple-icons:ubuntu",
        Fedora: "simple-icons:fedora",
        Arch: "simple-icons:archlinux",
        CentOS: "simple-icons:centos",
        Debian: "simple-icons:debian",
        Android: "simple-icons:android",
        iOS: "simple-icons:apple",
        Fuchsia: "simple-icons:google",
        Tizen: "simple-icons:samsung",
        Roku: "simple-icons:roku",
    };

    const mobileSet = new Set([
        "Android",
        "iOS",
        "Windows Phone",
        "Maemo",
        "WebOS",
        "KaiOS",
    ]);
    const tabletSet = new Set(["iPad"]);

    if (!platform) return HelpCircle;

    const slug = simpleIconsMap[platform] ?? null;

    if (slug) {
        return () => h(Icon, { icon: slug, class: "h-4 w-4" });
    }

    if (tabletSet.has(platform)) return Tablet;

    if (mobileSet.has(platform)) return Smartphone;

    return Monitor;
}
