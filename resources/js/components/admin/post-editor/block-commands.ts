import type { Editor, Range } from "@tiptap/core";
import {
    Code2,
    Heading2,
    Heading3,
    Heading4,
    ImagePlus,
    List,
    ListOrdered,
    Minus,
    Pilcrow,
    Quote,
    Sparkles,
    Table,
    type LucideIcon,
} from "lucide-react";

/** Actions de l'éditeur fournies par le composant (upload, barre IA). */
export type EditorActions = {
    pickImage: () => void;
    focusAi: () => void;
};

export type BlockCommand = {
    title: string;
    description: string;
    keywords: string[];
    icon: LucideIcon;
    run: (editor: Editor, actions: EditorActions) => void;
};

/** Blocs proposés par le menu « / » (façon Notion). */
export const BLOCK_COMMANDS: BlockCommand[] = [
    {
        title: "Texte",
        description: "Un paragraphe simple",
        keywords: ["paragraphe", "texte", "p"],
        icon: Pilcrow,
        run: (editor) => editor.chain().focus().setParagraph().run(),
    },
    {
        title: "Titre 2",
        description: "Une section de l’article",
        keywords: ["h2", "titre", "section"],
        icon: Heading2,
        run: (editor) =>
            editor.chain().focus().toggleHeading({ level: 2 }).run(),
    },
    {
        title: "Titre 3",
        description: "Une sous-section",
        keywords: ["h3", "sous-titre"],
        icon: Heading3,
        run: (editor) =>
            editor.chain().focus().toggleHeading({ level: 3 }).run(),
    },
    {
        title: "Titre 4",
        description: "Un petit intertitre",
        keywords: ["h4"],
        icon: Heading4,
        run: (editor) =>
            editor.chain().focus().toggleHeading({ level: 4 }).run(),
    },
    {
        title: "Liste à puces",
        description: "Une liste simple",
        keywords: ["liste", "puces", "ul"],
        icon: List,
        run: (editor) => editor.chain().focus().toggleBulletList().run(),
    },
    {
        title: "Liste numérotée",
        description: "Des étapes dans l’ordre",
        keywords: ["liste", "numéro", "ol", "étapes"],
        icon: ListOrdered,
        run: (editor) => editor.chain().focus().toggleOrderedList().run(),
    },
    {
        title: "Citation",
        description: "Mettre une phrase en valeur",
        keywords: ["citation", "quote", "blockquote"],
        icon: Quote,
        run: (editor) => editor.chain().focus().toggleBlockquote().run(),
    },
    {
        title: "Bloc de code",
        description: "Du code, police à chasse fixe",
        keywords: ["code", "pre"],
        icon: Code2,
        run: (editor) => editor.chain().focus().toggleCodeBlock().run(),
    },
    {
        title: "Tableau",
        description: "Un tableau de 3 × 3",
        keywords: ["tableau", "table", "comparatif"],
        icon: Table,
        run: (editor) =>
            editor
                .chain()
                .focus()
                .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
                .run(),
    },
    {
        title: "Image",
        description: "Importer une image",
        keywords: ["image", "photo", "illustration"],
        icon: ImagePlus,
        run: (_editor, actions) => actions.pickImage(),
    },
    {
        title: "Séparateur",
        description: "Une ligne horizontale",
        keywords: ["séparateur", "ligne", "hr"],
        icon: Minus,
        run: (editor) => editor.chain().focus().setHorizontalRule().run(),
    },
    {
        title: "Demander à l’IA",
        description: "Rédiger la suite avec l’assistant",
        keywords: ["ia", "ai", "rédiger", "écrire"],
        icon: Sparkles,
        run: (_editor, actions) => actions.focusAi(),
    },
];

export function filterBlockCommands(query: string): BlockCommand[] {
    const normalized = query.trim().toLowerCase();

    if (normalized === "") {
        return BLOCK_COMMANDS;
    }

    return BLOCK_COMMANDS.filter(
        (command) =>
            command.title.toLowerCase().includes(normalized) ||
            command.keywords.some((keyword) => keyword.startsWith(normalized)),
    );
}

/** Retire le « /requête » tapé avant d'appliquer la commande. */
export function runBlockCommand(
    editor: Editor,
    range: Range,
    command: BlockCommand,
    actions: EditorActions,
): void {
    editor.chain().focus().deleteRange(range).run();
    command.run(editor, actions);
}
