import { useRef, useState } from "react";
import type { ComponentProps, KeyboardEvent, Ref } from "react";
import type { Mentionable } from "@/components/admin/post-editor/mention";
import { MentionList } from "@/components/admin/post-editor/mention-menu";
import { Textarea } from "@/components/ui/textarea";
import { useMentionSearch } from "@/hooks/use-mention-search";

/** « @ » en début de mot, suivi de ce qui est tapé jusqu'au curseur. */
const TRIGGER = /(?:^|\s)@([^\s@[\]()]{0,40})$/u;

/** Jeton d'une mention dans un texte brut, lu par le serveur (voir PostMentions::TOKEN). */
export function mentionToken(item: Mentionable): string {
    const label = item.label.replace(/[[\]\n]/g, " ").trim();

    return `@[${label}](${item.kind}:${item.id})`;
}

/**
 * Zone de texte où « @ » propose les projets, articles, expériences et technologies du site.
 * La mention choisie est écrite en jeton `@[App Station](project:12)` : le site en fait un
 * lien avec une carte au survol.
 */
export default function MentionTextarea({
    ref,
    ...props
}: ComponentProps<typeof Textarea> & {
    ref?: Ref<HTMLTextAreaElement | null>;
}) {
    const field = useRef<HTMLTextAreaElement | null>(null);
    const [query, setQuery] = useState<string | null>(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const items = useMentionSearch(query);
    const open = query !== null && items.length > 0;

    function setRefs(element: HTMLTextAreaElement | null) {
        field.current = element;

        if (typeof ref === "function") {
            ref(element);
        } else if (ref) {
            ref.current = element;
        }
    }

    function detect() {
        const element = field.current;

        if (!element || element.selectionStart !== element.selectionEnd) {
            setQuery(null);

            return;
        }

        const match = TRIGGER.exec(
            element.value.slice(0, element.selectionStart),
        );
        setQuery(match ? match[1] : null);
        setActiveIndex(0);
    }

    function pick(item: Mentionable) {
        const element = field.current;

        if (!element || query === null) {
            return;
        }

        const caret = element.selectionStart;
        const start = caret - query.length - 1;
        const token = `${mentionToken(item)} `;

        element.setRangeText(token, start, caret, "end");
        element.dispatchEvent(new Event("input", { bubbles: true }));
        element.focus();
        setQuery(null);
    }

    function handleKeyDown(event: KeyboardEvent<HTMLTextAreaElement>) {
        if (open) {
            if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                event.preventDefault();
                const step = event.key === "ArrowDown" ? 1 : -1;
                setActiveIndex(
                    (activeIndex + step + items.length) % items.length,
                );

                return;
            }

            if (event.key === "Enter" || event.key === "Tab") {
                event.preventDefault();
                pick(items[Math.min(activeIndex, items.length - 1)]);

                return;
            }

            if (event.key === "Escape") {
                event.preventDefault();
                setQuery(null);

                return;
            }
        }

        props.onKeyDown?.(event);
    }

    return (
        <div className="relative">
            <Textarea
                {...props}
                ref={setRefs}
                onKeyDown={handleKeyDown}
                onKeyUp={(event) => {
                    if (
                        !["ArrowDown", "ArrowUp", "Enter", "Tab", "Escape"].includes(
                            event.key,
                        )
                    ) {
                        detect();
                    }

                    props.onKeyUp?.(event);
                }}
                onClick={(event) => {
                    detect();
                    props.onClick?.(event);
                }}
                onBlur={(event) => {
                    setQuery(null);
                    props.onBlur?.(event);
                }}
            />
            {open && (
                <MentionList
                    items={items}
                    activeIndex={activeIndex}
                    onHover={setActiveIndex}
                    onPick={pick}
                    className="absolute top-full left-0 mt-1"
                />
            )}
        </div>
    );
}
