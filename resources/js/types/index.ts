export * from "./auth";
export * from "./navigation";
export * from "./ui";
export * from "./notification";

export interface TPagedLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface TPagedMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

export interface TPagedResource<T> {
    data: T[];
    links: TPagedLinks;
    meta: TPagedMeta;
}
