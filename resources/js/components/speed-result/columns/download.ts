import type { ColumnDef } from "@tanstack/vue-table";
import { h } from "vue";
import { Badge } from "@/components/ui/badge";
import type { TSpeedResult } from "@/types/speed-result";

const ACCENT = "oklch(0.52 0.17 155)";

function metricClass(v: number): "good" | "warn" | "bad" {
    if (v >= 100) return "good";

    if (v >= 25) return "warn";

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

export const downloadColumns: ColumnDef<TSpeedResult>[] = [
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
        accessorKey: "download",
        header: () =>
            h(
                "div",
                { class: "text-right", style: `color:${ACCENT}` },
                "↓ Download",
            ),
        meta: { headerClass: "text-right", cellClass: "text-right" },
        cell: ({ row, table }) => {
            const v = row.getValue<number>("download") ?? 0;
            const max = Math.max(
                ...table
                    .getRowModel()
                    .rows.map((r) => r.getValue<number>("download") ?? 0),
            );
            const barW = max > 0 ? Math.round((v / max) * 100) : 0;
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
                            "Mbps",
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
