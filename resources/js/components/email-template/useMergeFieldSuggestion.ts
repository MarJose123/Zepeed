import { computePosition, flip, offset, shift } from "@floating-ui/vue";
import type {
    SuggestionKeyDownProps,
    SuggestionOptions,
    SuggestionProps,
} from "@tiptap/suggestion";
import { VueRenderer } from "@tiptap/vue-3";
import MergeFieldList from "@/components/email-template/MergeFieldList.vue";
import type { MergeField } from "@/types/email-template";

export function useMergeFieldSuggestion(
    fields: MergeField[],
): Omit<SuggestionOptions, "editor"> {
    return {
        char: "#",
        allowSpaces: false,

        items({ query }: { query: string }): MergeField[] {
            if (!query) return fields;

            const q = query.toLowerCase();

            return fields.filter(
                (f) =>
                    f.name.toLowerCase().includes(q) ||
                    f.tag.toLowerCase().includes(q),
            );
        },

        render() {
            let renderer: VueRenderer | null = null;
            let container: HTMLDivElement | null = null;

            function place(props: SuggestionProps) {
                if (!container || !props.clientRect) return;

                const ref = {
                    getBoundingClientRect: props.clientRect as () => DOMRect,
                };
                computePosition(ref as Element, container, {
                    placement: "bottom-start",
                    middleware: [offset(6), flip(), shift({ padding: 8 })],
                }).then(({ x, y }) => {
                    if (!container) return;

                    container.style.left = `${x}px`;
                    container.style.top = `${y}px`;
                });
            }

            return {
                onStart(props: SuggestionProps) {
                    container = document.createElement("div");
                    container.style.cssText = "position:fixed;z-index:9999;";
                    document.body.appendChild(container);

                    renderer = new VueRenderer(MergeFieldList, {
                        props,
                        editor: props.editor,
                    });

                    // Mount the rendered Vue component into our container
                    if (renderer.element) {
                        container.appendChild(renderer.element);
                    }

                    place(props);
                },

                onUpdate(props: SuggestionProps) {
                    renderer?.updateProps(props);
                    place(props);
                },

                onKeyDown(props: SuggestionKeyDownProps): boolean {
                    // Esc — destroy popup
                    if (props.event.key === "Escape") {
                        renderer?.destroy();
                        container?.remove();
                        renderer = null;
                        container = null;

                        return true;
                    }

                    // Forward arrow + enter to the list component
                    return (
                        (renderer?.ref as any)?.onKeyDown(props.event) ?? false
                    );
                },

                onExit() {
                    renderer?.destroy();
                    container?.remove();
                    renderer = null;
                    container = null;
                },
            };
        },
    };
}
