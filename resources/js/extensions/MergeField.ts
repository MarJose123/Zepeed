import { mergeAttributes, Node } from "@tiptap/core";
import { PluginKey } from "@tiptap/pm/state";
import Suggestion from "@tiptap/suggestion";

export const MergeField = Node.create({
    name: "mergeField",
    group: "inline",
    inline: true,
    atom: true,
    selectable: false,

    addOptions() {
        return {
            HTMLAttributes: {},
            suggestion: {
                char: "#",
                pluginKey: new PluginKey("mergeField"),
                command({ editor, range, props }: any) {
                    editor
                        .chain()
                        .focus()
                        .deleteRange(range)
                        .insertContent({
                            type: "mergeField",
                            attrs: { tag: props.tag, label: props.name },
                        })
                        .run();
                    // Ensure cursor moves past the node
                    editor.commands.insertContent(" ");
                },
                allow({ state, range }: any) {
                    const $from = state.doc.resolve(range.from);
                    const type = state.schema.nodes["mergeField"];

                    if (!type) return false;

                    return !!$from.parent.type.contentMatch.matchType(type);
                },
            },
        };
    },

    addAttributes() {
        return {
            tag: {
                default: null,
                parseHTML: (el: Element) => el.getAttribute("data-merge-field"),
                renderHTML: (attrs: any) => ({ "data-merge-field": attrs.tag }),
            },
            label: {
                default: null,
                parseHTML: (el: Element) =>
                    el.getAttribute("data-label") ?? el.textContent,
                renderHTML: (attrs: any) => ({ "data-label": attrs.label }),
            },
        };
    },

    parseHTML() {
        return [{ tag: "span[data-merge-field]" }];
    },

    renderHTML({ node, HTMLAttributes }: any) {
        // CRITICAL: return the label as a plain string in index [2], NOT as child node array.
        // ProseMirror crashes if it tries to parse {{ $var }} as HTML children.
        return [
            "span",
            mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, {
                class: "merge-field-pill",
            }),
            // Plain string label — ProseMirror renders it as a text node
            String(node.attrs.tag ?? node.attrs.label ?? ""),
        ];
    },

    renderText({ node }: any) {
        // When getHTML() serialises — emit raw Blade tag
        return String(node.attrs.tag ?? "");
    },

    addProseMirrorPlugins() {
        return [
            Suggestion({
                editor: this.editor,
                ...this.options.suggestion,
            }),
        ];
    },
});
