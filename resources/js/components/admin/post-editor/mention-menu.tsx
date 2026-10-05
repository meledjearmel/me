import type { Editor } from "@tiptap/core";
import {
    Briefcase,
    FileText,
    FolderKanban,
    Wrench,
    type LucideIcon,
} from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import PostController from "@/actions/App/Http/Controllers/Admin/PostController";
import { cn } from "@/lib/utils";
import type { MentionKind, Mentionable } from "./mention";
import type { SlashBridge, SlashState } from "./slash-command";

const KINDS: Record<MentionKind, { label: string; icon: LucideIcon }> = {
    project: { label: "Projet", icon: FolderKanban },
    post: { label: "Article", icon: FileText },
    technology: { label: "Technologie", icon: Wrench },
    experience: { label: "Expérience", icon: Briefcase },
};

/** Délai avant de chercher, pour ne pas lancer une requête par lettre tapée. */
const SEARCH_DELAY_MS = 150;

function insertMention(editor: Editor, state: SlashState, item: Mentionable) {
    editor
        .chain()
        .focus()
        .insertContentAt(state.range, [
            {
                type: "mention",
                attrs: { kind: item.kind, id: item.id, label: item.label },
            },
            { type: "text", text: " " },
        ])
        .run();
}

/**
 * Menu des éléments du site mentionnables, ouvert par « @ ». Comme le menu « / », il reçoit
 * les flèches et Entrée du plugin de suggestion via le pont.
 */
export default function MentionMenu({
    editor,
    bridge,
}: {
    editor: Editor;
    bridge: SlashBridge;
}) {
    const [state, setState] = useState<SlashState | null>(null);
    const [items, setItems] = useState<Mentionable[]>([]);
    const [activeIndex, setActiveIndex] = useState(0);
    const query = state?.query ?? null;

    const latest = useRef({ state, items, activeIndex });
    latest.current = { state, items, activeIndex };

    useEffect(() => {
        if (query === null) {
            setItems([]);

            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            fetch(PostController.mentions.url({ query: { q: query } }), {
                headers: { Accept: "application/json" },
                signal: controller.signal,
            })
                .then((response) => response.json())
                .then((body: { data: Mentionable[] }) => {
                    setItems(body.data);
                    setActiveIndex(0);
                })
                .catch(() => {});
        }, SEARCH_DELAY_MS);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [query]);

    useEffect(() => {
        bridge.onChange = (next) => setState(next);
        bridge.onKeyDown = (event) => {
            const current = latest.current;

            if (!current.state || current.items.length === 0) {
                return false;
            }

            if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                const step = event.key === "ArrowDown" ? 1 : -1;
                setActiveIndex(
                    (current.activeIndex + step + current.items.length) %
                        current.items.length,
                );

                return true;
            }

            if (event.key === "Enter" || event.key === "Tab") {
                insertMention(
                    editor,
                    current.state,
                    current.items[current.activeIndex],
                );
                setState(null);

                return true;
            }

            return false;
        };
    }, [bridge, editor]);

    if (!state?.rect || items.length === 0) {
        return null;
    }

    return createPortal(
        <div
            role="listbox"
            aria-label="Mentions"
            className="fixed z-50 max-h-80 w-80 overflow-y-auto rounded-lg border bg-popover p-1 text-popover-foreground shadow-lg"
            style={{ top: state.rect.bottom + 6, left: state.rect.left }}
        >
            <p className="px-2 pt-1 pb-1.5 text-xs text-muted-foreground">
                Mentionner
            </p>
            {items.map((item, index) => {
                const kind = KINDS[item.kind];

                return (
                    <button
                        key={`${item.kind}:${item.id}`}
                        type="button"
                        role="option"
                        aria-selected={index === activeIndex}
                        onMouseEnter={() => setActiveIndex(index)}
                        onMouseDown={(event) => {
                            event.preventDefault();
                            insertMention(editor, state, item);
                            setState(null);
                        }}
                        className={cn(
                            "flex w-full items-center gap-3 rounded-md px-2 py-1.5 text-left",
                            index === activeIndex && "bg-accent",
                        )}
                    >
                        <span className="flex size-8 shrink-0 items-center justify-center rounded-md border bg-background">
                            <kind.icon className="size-4" />
                        </span>
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-medium">
                                {item.label}
                            </span>
                            <span className="block truncate text-xs text-muted-foreground">
                                {kind.label}
                                {item.hint ? ` · ${item.hint}` : ""}
                            </span>
                        </span>
                    </button>
                );
            })}
        </div>,
        document.body,
    );
}
