import { mergeAttributes, Node } from "@tiptap/core";
import { PluginKey } from "@tiptap/pm/state";
import Suggestion from "@tiptap/suggestion";
import type { SlashBridge } from "./slash-command";

export type MentionKind = "project" | "post" | "technology";

export type Mentionable = {
    kind: MentionKind;
    id: number;
    label: string;
    hint: string | null;
};

/**
 * Mention d'un élément du site (« @App Station »), ouverte en tapant « @ ». Seuls le type et
 * l'identifiant comptent : le serveur en fait un lien avec une carte au survol, au nom actuel.
 * Le menu réutilise le pont du menu « / ».
 */
export const Mention = Node.create<{ bridge: SlashBridge }>({
    name: "mention",
    group: "inline",
    inline: true,
    atom: true,
    selectable: false,

    // Pas d'objet par défaut : configure() fusionnerait en copiant le pont, et le menu
    // React ne modifierait plus celui que le plugin appelle.
    addOptions() {
        return { bridge: null as unknown as SlashBridge };
    },

    addAttributes() {
        return {
            kind: {
                parseHTML: (element) => element.getAttribute("data-kind"),
                renderHTML: (attributes) => ({ "data-kind": attributes.kind }),
            },
            id: {
                parseHTML: (element) => Number(element.getAttribute("data-id")),
                renderHTML: (attributes) => ({ "data-id": attributes.id }),
            },
            label: {
                parseHTML: (element) =>
                    element.getAttribute("data-label") ?? element.textContent,
                renderHTML: (attributes) => ({
                    "data-label": attributes.label,
                }),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'span[data-type="mention"]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return [
            "span",
            mergeAttributes(
                { "data-type": "mention", class: "post-mention" },
                HTMLAttributes,
            ),
            `@${node.attrs.label}`,
        ];
    },

    renderText({ node }) {
        return node.attrs.label;
    },

    addKeyboardShortcuts() {
        return {
            // Effacer une mention d'un coup rend le « @ » pour en choisir une autre.
            Backspace: () =>
                this.editor.commands.command(({ tr, state }) => {
                    const { selection } = state;
                    const before = selection.$anchor.nodeBefore;

                    if (!selection.empty || before?.type.name !== this.name) {
                        return false;
                    }

                    tr.insertText(
                        "@",
                        selection.anchor - before.nodeSize,
                        selection.anchor,
                    );

                    return true;
                }),
        };
    },

    addProseMirrorPlugins() {
        const { bridge } = this.options;
        const state = (props: {
            query: string;
            range: { from: number; to: number };
            clientRect?: (() => DOMRect | null) | null;
        }) =>
            bridge.onChange({
                query: props.query,
                range: props.range,
                rect: props.clientRect?.() ?? null,
            });

        return [
            Suggestion({
                editor: this.editor,
                pluginKey: new PluginKey("mention"),
                char: "@",
                allowSpaces: true,
                allow: ({ editor }) => !editor.isActive("codeBlock"),
                items: () => [],
                render: () => ({
                    onStart: state,
                    onUpdate: state,
                    onKeyDown: ({ event }) => {
                        if (event.key === "Escape") {
                            bridge.onChange(null);

                            return true;
                        }

                        return bridge.onKeyDown(event);
                    },
                    onExit: () => bridge.onChange(null),
                }),
            }),
        ];
    },
});
