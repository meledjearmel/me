import type { Editor } from "@tiptap/core";
import { useEditorState } from "@tiptap/react";
import { BubbleMenu } from "@tiptap/react/menus";
import {
    Bold,
    Code,
    Highlighter,
    Italic,
    Sparkles,
    Strikethrough,
    Underline,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import type { EditorActions } from "./block-commands";
import LinkPopover from "./link-popover";
import ToolbarButton from "./toolbar-button";

/** Menu flottant au-dessus de la sélection : mise en forme et IA. */
export default function SelectionBubble({
    editor,
    actions,
}: {
    editor: Editor;
    actions: EditorActions;
}) {
    const state = useEditorState({
        editor,
        selector: ({ editor: current }) => ({
            bold: current.isActive("bold"),
            italic: current.isActive("italic"),
            underline: current.isActive("underline"),
            strike: current.isActive("strike"),
            code: current.isActive("code"),
            highlight: current.isActive("highlight"),
            link: current.isActive("link"),
        }),
    });

    const chain = () => editor.chain().focus();

    return (
        <BubbleMenu
            editor={editor}
            shouldShow={({ editor: current, from, to }) =>
                from !== to &&
                !current.isActive("image") &&
                !current.isActive("codeBlock")
            }
            className="flex items-center gap-0.5 rounded-lg border bg-popover p-1 shadow-lg"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                className="h-8 gap-1.5 px-2 text-violet-600 dark:text-violet-400"
                onMouseDown={(event) => event.preventDefault()}
                onClick={actions.focusAi}
            >
                <Sparkles className="size-4" /> Demander à l’IA
            </Button>
            <Separator orientation="vertical" className="mx-1 h-5!" />
            <ToolbarButton
                icon={Bold}
                label="Gras"
                active={state.bold}
                onClick={() => chain().toggleBold().run()}
            />
            <ToolbarButton
                icon={Italic}
                label="Italique"
                active={state.italic}
                onClick={() => chain().toggleItalic().run()}
            />
            <ToolbarButton
                icon={Underline}
                label="Souligné"
                active={state.underline}
                onClick={() => chain().toggleUnderline().run()}
            />
            <ToolbarButton
                icon={Strikethrough}
                label="Barré"
                active={state.strike}
                onClick={() => chain().toggleStrike().run()}
            />
            <ToolbarButton
                icon={Code}
                label="Code"
                active={state.code}
                onClick={() => chain().toggleCode().run()}
            />
            <ToolbarButton
                icon={Highlighter}
                label="Surligner"
                active={state.highlight}
                onClick={() => chain().toggleHighlight().run()}
            />
            <LinkPopover editor={editor} active={state.link} />
        </BubbleMenu>
    );
}
