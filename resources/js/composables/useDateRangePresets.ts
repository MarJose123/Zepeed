import dayjs from "dayjs";
import { computed } from "vue";
import type { Ref } from "vue";
import { API_DATE_FORMAT } from "@/lib/date";

export interface TDatePreset {
    key: string;
    label: string;
    from: string;
    to: string;
}

function formatMonthLabel(month: string): string {
    return dayjs(`${month}-01`, `${API_DATE_FORMAT}`, true).format("MMMM YYYY");
}

/**
 * Builds the quick-pick date range presets (Today, Last 7 days, ...) plus one
 * preset per distinct month present in the data, sourced from the `months`
 * list the controller already computes from real rows.
 */
export function useDateRangePresets(months: Ref<string[]>) {
    const presets = computed<TDatePreset[]>(() => {
        const today = dayjs();

        const fixed: TDatePreset[] = [
            {
                key: "today",
                label: "Today",
                from: today.format(API_DATE_FORMAT),
                to: today.format(API_DATE_FORMAT),
            },
            {
                key: "last_7_days",
                label: "Last 7 days",
                from: today.subtract(6, "day").format(API_DATE_FORMAT),
                to: today.format(API_DATE_FORMAT),
            },
            {
                key: "last_30_days",
                label: "Last 30 days",
                from: today.subtract(29, "day").format(API_DATE_FORMAT),
                to: today.format(API_DATE_FORMAT),
            },
            {
                key: "this_month",
                label: "This month",
                from: today.startOf("month").format(API_DATE_FORMAT),
                to: today.endOf("month").format(API_DATE_FORMAT),
            },
        ];

        const named = months.value.map<TDatePreset>((month) => ({
            key: `month_${month}`,
            label: formatMonthLabel(month),
            from: dayjs(`${month}-01`, "YYYY-MM-DD", true)
                .startOf("month")
                .format(API_DATE_FORMAT),
            to: dayjs(`${month}-01`, "YYYY-MM-DD", true)
                .endOf("month")
                .format(API_DATE_FORMAT),
        }));

        return [...fixed, ...named];
    });

    function match(from: string | null, to: string | null): string | null {
        if (!from || !to) {
            return null;
        }

        return (
            presets.value.find(
                (preset) => preset.from === from && preset.to === to,
            )?.key ?? null
        );
    }

    return { presets, match };
}
