/* eslint-disable @typescript-eslint/no-unused-vars, unused-imports/no-unused-vars */

import type { RowData } from "@tanstack/vue-table";
import type { TSpeedResultSortKey } from "@/types/speed-result";

declare module "@tanstack/vue-table" {
    interface ColumnMeta<TData extends RowData, TValue> {
        headerClass?: string;
        cellClass?: string;
        sortable?: boolean;
        sortKey?: TSpeedResultSortKey;
        sortLabel?: string;
    }
}
