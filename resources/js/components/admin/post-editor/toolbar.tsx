import type { Editor } from "@tiptap/core";
import { useEditorState } from "@tiptap/react";
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    ChevronDown,
    Code,
    Code2,
    Highlighter,
    ImagePlus,
    Italic,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Strikethrough,
    Subscript,
    Superscript,
    Table,
    Trash2,
    Underline,
    Undo2,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Separator } from "@/components/ui/separator";
import type { EditorActions } from "./block-commands";
import LinkPopover from "./link-popover";
import ToolbarButton from "./toolbar-button";

const HEADING_LEVELS = [2, 3, 4] as const;

function Divider() {
    return <Separator orientation="vertical" className="mx-1 h-5!" />;
}

/** Barre d'outils fixe, façon « Simple editor ». */
export default function Toolbar({
    editor,
    actions,
}: {
    editor: Editor;
    actions: EditorActions;
}) {
    const state = useEditorState({
        editor,
        selector: ({ editor: current }) => ({
            canUndo: current.can().undo(),
            canRedo: current.can().redo(),
            heading:
                HEADING_LEVELS.find((level) =>
                    current.isActive("heading", { level }),
                ) ?? null,
            bold: current.isActive("bold"),
            italic: current.isActive("italic"),
            underline: current.isActive("underline"),
            strike: current.isActive("strike"),
            code: current.isActive("code"),
            highlight: current.isActive("highlight"),
            link: current.isActive("link"),
            superscript: current.isActive("superscript"),
            subscript: current.isActive("subscript"),
            bulletList: current.isActive("bulletList"),
            orderedList: current.isActive("orderedList"),
            blockquote: current.isActive("blockquote"),
            codeBlock: current.isActive("codeBlock"),
            table: current.isActive("table"),
            align: (["left", "center", "right", "justify"] as const).find(
                (align) => current.isActive({ textAlign: align }),
            ),
        }),
    });

    const chain = () => editor.chain().focus();

    return (
        <div className="sticky top-0 z-20 flex flex-wrap items-center justify-center gap-0.5 border-b bg-background/95 px-2 py-1.5 backdrop-blur">
            <ToolbarButton
                icon={Undo2}
                label="Annuler"
                shortcut="Ctrl+Z"
                disabled={!state.canUndo}
                onClick={() => chain().undo().run()}
            />
            <ToolbarButton
                icon={Redo2}
                label="Rétablir"
                shortcut="Ctrl+Maj+Z"
                disabled={!state.canRedo}
                onClick={() => chain().redo().run()}
            />
            <Divider />

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-8 gap-1 px-2 text-muted-foreground"
                        aria-label="Style du paragraphe"
                    >
                        {state.heading ? `Titre ${state.heading}` : "Texte"}
                        <ChevronDown className="size-3" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start">
                    <DropdownMenuItem
                        onSelect={() => chain().setParagraph().run()}
                    >
                        Texte
                    </DropdownMenuItem>
                    {HEADING_LEVELS.map((level) => (
                        <DropdownMenuItem
                            key={level}
                            onSelect={() =>
                                chain().toggleHeading({ level }).run()
                            }
                        >
                            Titre {level}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
            <ToolbarButton
                icon={List}
                label="Liste à puces"
                active={state.bulletList}
                onClick={() => chain().toggleBulletList().run()}
            />
            <ToolbarButton
                icon={ListOrdered}
                label="Liste numérotée"
                active={state.orderedList}
                onClick={() => chain().toggleOrderedList().run()}
            />
            <ToolbarButton
                icon={Quote}
                label="Citation"
                active={state.blockquote}
                onClick={() => chain().toggleBlockquote().run()}
            />
            <ToolbarButton
                icon={Code2}
                label="Bloc de code"
                active={state.codeBlock}
                onClick={() => chain().toggleCodeBlock().run()}
            />
            <Divider />

            <ToolbarButton
                icon={Bold}
                label="Gras"
                shortcut="Ctrl+B"
                active={state.bold}
                onClick={() => chain().toggleBold().run()}
            />
            <ToolbarButton
                icon={Italic}
                label="Italique"
                shortcut="Ctrl+I"
                active={state.italic}
                onClick={() => chain().toggleItalic().run()}
            />
            <ToolbarButton
                icon={Underline}
                label="Souligné"
                shortcut="Ctrl+U"
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
                shortcut="Ctrl+E"
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
            <Divider />

            <ToolbarButton
                icon={Superscript}
                label="Exposant"
                active={state.superscript}
                onClick={() => chain().toggleSuperscript().run()}
            />
            <ToolbarButton
                icon={Subscript}
                label="Indice"
                active={state.subscript}
                onClick={() => chain().toggleSubscript().run()}
            />
            <Divider />

            <ToolbarButton
                icon={AlignLeft}
                label="Aligner à gauche"
                active={state.align === "left"}
                onClick={() => chain().setTextAlign("left").run()}
            />
            <ToolbarButton
                icon={AlignCenter}
                label="Centrer"
                active={state.align === "center"}
                onClick={() => chain().setTextAlign("center").run()}
            />
            <ToolbarButton
                icon={AlignRight}
                label="Aligner à droite"
                active={state.align === "right"}
                onClick={() => chain().setTextAlign("right").run()}
            />
            <ToolbarButton
                icon={AlignJustify}
                label="Justifier"
                active={state.align === "justify"}
                onClick={() => chain().setTextAlign("justify").run()}
            />
            <Divider />

            <ToolbarButton
                icon={ImagePlus}
                label="Image"
                onClick={actions.pickImage}
            />
            {state.table ? (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="h-8 gap-1 bg-accent px-2"
                        >
                            <Table className="size-4" />
                            <ChevronDown className="size-3" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            onSelect={() => chain().addRowAfter().run()}
                        >
                            Ajouter une ligne
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => chain().addColumnAfter().run()}
                        >
                            Ajouter une colonne
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => chain().deleteRow().run()}
                        >
                            Supprimer la ligne
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => chain().deleteColumn().run()}
                        >
                            Supprimer la colonne
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={() => chain().deleteTable().run()}
                        >
                            <Trash2 /> Supprimer le tableau
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ) : (
                <ToolbarButton
                    icon={Table}
                    label="Tableau"
                    onClick={() =>
                        chain()
                            .insertTable({
                                rows: 3,
                                cols: 3,
                                withHeaderRow: true,
                            })
                            .run()
                    }
                />
            )}
        </div>
    );
}
