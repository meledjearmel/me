import type { Editor } from "@tiptap/core";
import type { TableOfContentData } from "@tiptap/extension-table-of-contents";
import { TextSelection } from "@tiptap/pm/state";
import { cn } from "@/lib/utils";

/**
 * Sommaire de l'article (façon Notion) : des traits à droite de la feuille, qui
 * s'ouvrent au survol sur la liste des titres.
 */
export default function OutlineRail({
    editor,
    anchors,
}: {
    editor: Editor;
    anchors: TableOfContentData;
}) {
    if (anchors.length === 0) {
        return null;
    }

    function goTo(pos: number, dom: HTMLElement) {
        const { tr } = editor.state;
        editor.view.dispatch(
            tr.setSelection(TextSelection.create(tr.doc, pos + 1)),
        );
        editor.view.focus();
        dom.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    return (
        <nav
            aria-label="Sommaire de l’article"
            className="group/outline absolute top-6 right-3 z-10 hidden lg:block"
        >
            <ul className="flex flex-col items-end gap-2 py-2 group-hover/outline:hidden">
                {anchors.map((anchor) => (
                    <li
                        key={anchor.id}
                        className={cn(
                            "h-0.5 rounded-full bg-muted-foreground/30",
                            anchor.level === 1 ? "w-5" : "w-3",
                            anchor.isActive && "bg-foreground",
                        )}
                    />
                ))}
            </ul>
            <ul className="hidden w-60 flex-col gap-0.5 rounded-lg border bg-popover p-2 shadow-lg group-hover/outline:flex">
                {anchors.map((anchor) => (
                    <li key={anchor.id}>
                        <button
                            type="button"
                            onClick={() => goTo(anchor.pos, anchor.dom)}
                            style={{
                                paddingLeft: `${(anchor.level - 1) * 12 + 8}px`,
                            }}
                            className={cn(
                                "w-full truncate rounded-md py-1 pr-2 text-left text-sm text-muted-foreground hover:bg-accent hover:text-foreground",
                                anchor.isActive &&
                                    "font-medium text-foreground",
                            )}
                        >
                            {anchor.textContent || "Sans titre"}
                        </button>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
