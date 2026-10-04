import type { Editor } from "@tiptap/core";
import { Highlight } from "@tiptap/extension-highlight";
import { Image } from "@tiptap/extension-image";
import { Subscript } from "@tiptap/extension-subscript";
import { Superscript } from "@tiptap/extension-superscript";
import { TableKit } from "@tiptap/extension-table";
import {
    TableOfContents,
    type TableOfContentData,
} from "@tiptap/extension-table-of-contents";
import { TextAlign } from "@tiptap/extension-text-align";
import { CharacterCount, Placeholder } from "@tiptap/extensions";
import { EditorContent, useEditor, useEditorState } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import { useMemo, useRef, useState } from "react";
import PostController from "@/actions/App/Http/Controllers/Admin/PostController";
import type { Locale } from "@/hooks/use-ai-text-assist";
import AiBar, { type AiBarHandle } from "./ai-bar";
import type { EditorActions } from "./block-commands";
import OutlineRail from "./outline-rail";
import SelectionBubble from "./selection-bubble";
import { type SlashBridge, SlashCommand } from "./slash-command";
import SlashMenu from "./slash-menu";
import Toolbar from "./toolbar";

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : "";
}

async function uploadImage(file: File): Promise<string | null> {
    const body = new FormData();
    body.append("image", file);

    const response = await fetch(PostController.storeImage.url(), {
        method: "POST",
        headers: {
            Accept: "application/json",
            "X-XSRF-TOKEN": csrfToken(),
            "X-Requested-With": "XMLHttpRequest",
        },
        body,
    });

    return response.ok
        ? ((await response.json()) as { url: string }).url
        : null;
}

function imageFiles(list: FileList | null | undefined): File[] {
    return Array.from(list ?? []).filter((file) =>
        file.type.startsWith("image/"),
    );
}

/**
 * Éditeur des articles du blog. Il réunit la feuille et la barre IA de l'« Agent
 * editor », la barre d'outils du « Simple editor », ainsi que le menu « / », le
 * menu de sélection et le sommaire du modèle « Notion ». Le HTML part avec le
 * formulaire via un champ caché ; le serveur le nettoie à l'enregistrement.
 */
export default function PostEditor({
    name,
    defaultValue,
    locale,
    articleMeta,
    onReady,
}: {
    name: string;
    defaultValue?: string;
    locale: Locale;
    articleMeta: () => { title: string; excerpt: string };
    onReady?: (editor: Editor) => void;
}) {
    const [html, setHtml] = useState(defaultValue ?? "");
    const [anchors, setAnchors] = useState<TableOfContentData>([]);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const aiBarRef = useRef<AiBarHandle>(null);
    const bridge = useMemo<SlashBridge>(
        () => ({ onChange: () => {}, onKeyDown: () => false }),
        [],
    );

    async function insertImages(editor: Editor, files: File[], pos?: number) {
        setUploadError(null);

        for (const file of files) {
            const src = await uploadImage(file).catch(() => null);

            if (src === null) {
                setUploadError(
                    `« ${file.name} » n’a pas pu être importée (image de 5 Mo maximum).`,
                );

                continue;
            }

            const content = { type: "image", attrs: { src, alt: "" } };

            if (pos === undefined) {
                editor.chain().focus().insertContent(content).run();
            } else {
                editor.chain().focus().insertContentAt(pos, content).run();
            }
        }
    }

    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, autolink: true },
            }),
            Highlight,
            Subscript,
            Superscript,
            TextAlign.configure({ types: ["heading", "paragraph"] }),
            Image,
            TableKit.configure({ table: { resizable: false } }),
            Placeholder.configure({
                placeholder: ({ node }) =>
                    node.type.name === "heading"
                        ? "Titre"
                        : "Écrivez, ou tapez « / » pour insérer un bloc…",
            }),
            CharacterCount,
            TableOfContents.configure({
                onUpdate: (data) => setAnchors(data),
            }),
            SlashCommand.configure({ bridge }),
        ],
        content: defaultValue ?? "",
        editorProps: {
            attributes: {
                class: "post-content",
                "aria-label": "Contenu de l’article",
            },
            handlePaste: (view, event) => {
                const files = imageFiles(event.clipboardData?.files);

                if (files.length === 0 || !editorRef.current) {
                    return false;
                }

                void insertImages(editorRef.current, files);

                return true;
            },
            handleDrop: (view, event, _slice, moved) => {
                const files = imageFiles(event.dataTransfer?.files);

                if (moved || files.length === 0 || !editorRef.current) {
                    return false;
                }

                const pos = view.posAtCoords({
                    left: event.clientX,
                    top: event.clientY,
                })?.pos;
                void insertImages(editorRef.current, files, pos);

                return true;
            },
        },
        onCreate: ({ editor: created }) => onReady?.(created),
        onUpdate: ({ editor: updated }) =>
            setHtml(updated.isEmpty ? "" : updated.getHTML()),
    });
    const editorRef = useRef<Editor | null>(null);
    editorRef.current = editor;

    const counts = useEditorState({
        editor,
        selector: ({ editor: current }) => ({
            words: current?.storage.characterCount.words() ?? 0,
        }),
    });

    const actions = useMemo<EditorActions>(
        () => ({
            pickImage: () => fileInputRef.current?.click(),
            focusAi: () => aiBarRef.current?.focus(),
        }),
        [],
    );

    return (
        <div className="relative overflow-clip rounded-xl border bg-muted/40">
            <input type="hidden" name={name} value={html} />
            <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                multiple
                hidden
                onChange={(event) => {
                    const files = imageFiles(event.target.files);
                    event.target.value = "";

                    if (editor && files.length > 0) {
                        void insertImages(editor, files);
                    }
                }}
            />

            {editor ? (
                <>
                    <Toolbar editor={editor} actions={actions} />

                    <div className="relative px-3 py-6 sm:px-6">
                        <OutlineRail editor={editor} anchors={anchors} />

                        <div className="mx-auto max-w-3xl rounded-lg bg-background px-5 py-10 shadow-sm ring-1 ring-border sm:px-14 sm:py-14">
                            <EditorContent editor={editor} />
                        </div>

                        <div className="mx-auto mt-2 flex max-w-3xl justify-between px-1 text-xs text-muted-foreground">
                            <span className="text-destructive">
                                {uploadError}
                            </span>
                            <span>
                                {counts?.words ?? 0} mots · ~
                                {Math.max(
                                    1,
                                    Math.ceil((counts?.words ?? 0) / 220),
                                )}{" "}
                                min de lecture
                            </span>
                        </div>

                        <AiBar
                            ref={aiBarRef}
                            editor={editor}
                            locale={locale}
                            articleMeta={articleMeta}
                        />
                    </div>

                    <SelectionBubble editor={editor} actions={actions} />
                    <SlashMenu
                        editor={editor}
                        bridge={bridge}
                        actions={actions}
                    />
                </>
            ) : (
                <div className="h-[32rem] animate-pulse" />
            )}
        </div>
    );
}
