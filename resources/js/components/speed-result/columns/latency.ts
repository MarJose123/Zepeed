import type { ColumnDef } from "@tanstack/vue-table";
import { h } from "vue";
import { Badge } from "@/components/ui/badge";
import type { TSpeedResult } from "@/types/speed-result";

const ACCENT = "oklch(0.48 0.22 305)";

function metricClass(v: number): "good" | "warn" | "bad" {
    if (v <= 20) return "good";

    if (v <= 60) return "warn";

    return "bad";
}

const colorMap = {
    good: "text-emerald-600 dark:text-emerald-400",
    warn: "text-amber-600 dark:text-amber-400",
    bad: "text-destructive",
};

const barMap = {
    good: "bg-emerald-500",
    warn: "bg-amber-500",
    bad: "bg-destructive",
};

export const latencyColumns: ColumnDef<TSpeedResult>[] = [
    {
        accessorKey: "measured_at",
        header: "Timestamp",
        cell: ({ row }) =>
            h(
                "span",
                { class: "font-mono text-[11.5px] text-muted-foreground" },
                new Date(row.getValue("measured_at")).toLocaleString(
                    "default",
                    {
                        year: "numeric",
                        month: "short",
                        day: "2-digit",
                        hour: "2-digit",
                        minute: "2-digit",
                    },
                ),
            ),
    },
    {
        accessorKey: "provider_name",
        header: "Provider",
        cell: ({ row }) =>
            h(
                Badge,
                { variant: "outline", class: "font-mono text-[10px]" },
                () => row.getValue("provider_name"),
            ),
    },
    {
        accessorKey: "ping",
        header: () =>
            h(
                "div",
                { class: "text-right", style: `color:${ACCENT}` },
                "◎ Ping",
            ),
        meta: { headerClass: "text-right", cellClass: "text-right" },
        cell: ({ row }) => {
            const v = row.getValue<number>("ping") ?? 0;
            // For ping: bar width proportional to 200ms max — higher = worse
            const barW = Math.min(100, Math.round((v / 200) * 100));
            const cls = metricClass(v);

            return h("div", { class: "flex flex-col items-end gap-0.5" }, [
                h(
                    "span",
                    {
                        class: `font-mono text-sm font-semibold leading-none ${colorMap[cls]}`,
                    },
                    [
                        `${v}`,
                        h(
                            "span",
                            {
                                class: "text-[9.5px] font-normal opacity-45 ml-0.5",
                            },
                            "ms",
                        ),
                    ],
                ),
                h(
                    "div",
                    {
                        class: "w-[52px] h-[2px] rounded-full bg-border overflow-hidden",
                    },
                    [
                        h("div", {
                            class: `h-full rounded-full ${barMap[cls]}`,
                            style: `width:${barW}%`,
                        }),
                    ],
                ),
            ]);
        },
    },
    {
        accessorKey: "jitter",
        header: () => h("div", { class: "text-right" }, "Jitter"),
        meta: { headerClass: "text-right", cellClass: "text-right" },
        cell: ({ row }) => {
            const v = row.getValue<number | null>("jitter");

            return h(
                "span",
                { class: "font-mono text-xs text-muted-foreground" },
                [
                    v !== null ? `${v}` : "—",
                    v !== null
                        ? h(
                              "span",
                              { class: "opacity-45 ml-0.5 text-[9.5px]" },
                              "ms",
                          )
                        : "",
                ],
            );
        },
    },
    {
        accessorKey: "share_url",
        header: () => h("div", { class: "text-right" }, "Share"),
        meta: { headerClass: "text-right", cellClass: "text-right" },
        cell: ({ row }) => {
            const url = row.getValue<string | null>("share_url");

            return url
                ? h(
                      "a",
                      {
                          href: url,
                          target: "_blank",
                          rel: "noopener noreferrer",
                          class: "font-mono text-[10px] text-muted-foreground hover:text-foreground transition-colors",
                      },
                      "↗ view",
                  )
                : h(
                      "span",
                      { class: "font-mono text-[10px] text-muted-foreground" },
                      "—",
                  );
        },
    },
];
