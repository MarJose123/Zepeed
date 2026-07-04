import { CalendarDate, getLocalTimeZone } from "@internationalized/date";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";
import type { DateValue } from "reka-ui";

dayjs.extend(customParseFormat);

/** The only date format ever sent to/received from the backend. */
export const API_DATE_FORMAT = "YYYY-MM-DD";

/**
 * Converts a backend "YYYY-MM-DD" string into the `CalendarDate` value the
 * shadcn-vue Calendar / RangeCalendar components require. All date *logic*
 * (presets, formatting) stays in dayjs — this is the only place the
 * `@internationalized/date` type is constructed.
 */
export function toCalendarDate(
    value: string | null | undefined,
): CalendarDate | undefined {
    if (!value) {
        return undefined;
    }

    const parsed = dayjs(value, API_DATE_FORMAT, true);

    if (!parsed.isValid()) {
        return undefined;
    }

    return new CalendarDate(parsed.year(), parsed.month() + 1, parsed.date());
}

/**
 * Converts a Calendar/RangeCalendar selection back into the "YYYY-MM-DD"
 * string the backend expects.
 */
export function fromCalendarDate(value: DateValue | undefined): string | null {
    if (!value) {
        return null;
    }

    return dayjs(value.toDate(getLocalTimeZone())).format(API_DATE_FORMAT);
}

/** Human-friendly display label, e.g. "Jul 2, 2026". */
export function formatDisplayDate(value: string | null | undefined): string {
    if (!value) {
        return "";
    }

    return dayjs(value, API_DATE_FORMAT, true).format("MMM D, YYYY");
}
