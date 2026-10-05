import type { Editor } from "@tiptap/core";
import { useEditorState } from "@tiptap/react";
import { BubbleMenu } from "@tiptap/react/menus";
import { ImageIcon } from "lucide-react";
import { Input } from "@/components/ui/input";

/**
 * Menu flottant d'une image sélectionnée : son texte alternatif, lu par les lecteurs d'écran
 * et les moteurs de recherche. Vide, l'image est traitée comme décorative.
 */
export default function ImageBubble({ editor }: { editor: Editor }) {
    const alt = useEditorState({
        editor,
        selector: ({ editor: current }) =>
            current.isActive("image")
                ? ((current.getAttributes("image").alt as string | null) ?? "")
                : "",
    });

    return (
        <BubbleMenu
            editor={editor}
            pluginKey="imageBubble"
            shouldShow={({ editor: current }) => current.isActive("image")}
            className="flex w-80 items-center gap-2 rounded-lg border bg-popover p-1.5 shadow-lg"
        >
            <ImageIcon className="ml-1 size-4 shrink-0 text-muted-foreground" />
            <Input
                value={alt}
                onChange={(event) =>
                    editor
                        .chain()
                        .updateAttributes("image", { alt: event.target.value })
                        .run()
                }
                onKeyDown={(event) => {
                    if (event.key === "Enter") {
                        event.preventDefault();
                        editor.commands.focus();
                    }
                }}
                placeholder="Texte alternatif : décrire l’image"
                aria-label="Texte alternatif de l’image"
                className="h-8"
            />
        </BubbleMenu>
    );
}
