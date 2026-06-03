/* eslint-disable @typescript-eslint/no-unused-vars, unused-imports/no-unused-vars */

import type { RowData } from "@tanstack/vue-table";

declare module "@tanstack/vue-table" {
    interface ColumnMeta<TData extends RowData, TValue> {
        headerClass?: string;
        cellClass?: string;
    }
}
