export interface MergeField {
    group: string;
    name: string;
    desc: string;
    tag: string;
    sample: string;
}

export interface EmailTemplate {
    id: string;
    name: string;
    slug: string;
    subject: string;
    body: string;
    is_system: boolean;
    is_used_in_rules: boolean;
    used_in_rule_names: string[];
    updated_at: string;
    created_at: string;
}
