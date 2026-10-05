import { Extension, type Range } from "@tiptap/core";
import { PluginKey } from "@tiptap/pm/state";
import Suggestion from "@tiptap/suggestion";

export type SlashState = {
    query: string;
    range: Range;
    /** Position du curseur à l'écran, pour placer le menu. */
    rect: DOMRect | null;
};

/**
 * Pont entre le plugin de suggestion (hors React) et le menu « / » rendu par React :
 * le composant renseigne ces rappels, le plugin les appelle.
 */
export type SlashBridge = {
    onChange: (state: SlashState | null) => void;
    onKeyDown: (event: KeyboardEvent) => boolean;
};

/** Ouvre le menu des blocs quand on tape « / » (façon Notion). */
export const SlashCommand = Extension.create<{ bridge: SlashBridge }>({
    name: "slashCommand",

    // Pas d'objet par défaut : configure() fusionnerait en copiant le pont, et le menu
    // React ne modifierait plus celui que le plugin appelle.
    addOptions() {
        return { bridge: null as unknown as SlashBridge };
    },

    addProseMirrorPlugins() {
        const { bridge } = this.options;

        return [
            Suggestion({
                editor: this.editor,
                pluginKey: new PluginKey("slashCommand"),
                char: "/",
                startOfLine: false,
                allowSpaces: false,
                // Pas de menu dans le code : « / » y est un caractère comme un autre.
                allow: ({ editor }) => !editor.isActive("codeBlock"),
                items: () => [],
                render: () => ({
                    onStart: (props) =>
                        bridge.onChange({
                            query: props.query,
                            range: props.range,
                            rect: props.clientRect?.() ?? null,
                        }),
                    onUpdate: (props) =>
                        bridge.onChange({
                            query: props.query,
                            range: props.range,
                            rect: props.clientRect?.() ?? null,
                        }),
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
