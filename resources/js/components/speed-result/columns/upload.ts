import type { ColumnDef } from "@tanstack/vue-table";
import { h } from "vue";
import { Badge } from "@/components/ui/badge";
import type { TSpeedResult } from "@/types/speed-result";

const ACCENT = "oklch(0.48 0.19 260)";

function metricClass(v: number): "good" | "warn" | "bad" {
    if (v >= 100) return "good";

    if (v >= 10) return "warn";

    return "bad";
}

const colorMap = {
    good: "text-emerald-600 dark:text-emerald-400",
    warn: "text-amber-600 dark:text-amber-400",
    bad: "text-destructive",
};

export const uploadColumns: ColumnDef<TSpeedResult>[] = [
    {
        accessorKey: "measured_at",
        header: "Timestamp",
        meta: {
            sortable: true,
            sortKey: "measured_at",
            sortLabel: "Timestamp",
        },
        cell: ({ row }) =>
            h(
                "span",
                { class: "text-sm text-muted-foreground" },
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
            h(Badge, { variant: "outline", class: "text-xs" }, () =>
                row.getValue("provider_name"),
            ),
    },
    {
        accessorKey: "upload",
        header: () =>
            h(
                "div",
                { class: "text-right", style: `color:${ACCENT}` },
                "↑ Upload",
            ),
        meta: {
            headerClass: "text-right",
            cellClass: "text-right",
            sortable: true,
            sortKey: "upload_mbps",
            sortLabel: "Upload",
        },
        cell: ({ row }) => {
            const v = row.getValue<number>("upload") ?? 0;
            const cls = metricClass(v);

            return h("div", { class: "text-right" }, [
                h(
                    "span",
                    {
                        class: `text-sm font-semibold tabular-nums ${colorMap[cls]}`,
                    },
                    [
                        `${v}`,
                        h(
                            "span",
                            { class: "text-xs font-normal opacity-45 ml-0.5" },
                            "Mbps",
                        ),
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
                          class: "text-sm text-muted-foreground hover:text-foreground transition-colors",
                      },
                      "↗ view",
                  )
                : h("span", { class: "text-sm text-muted-foreground" }, "—");
        },
    },
];
