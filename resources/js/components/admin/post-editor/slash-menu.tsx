import type { Editor } from "@tiptap/core";
import { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { cn } from "@/lib/utils";
import {
    type EditorActions,
    filterBlockCommands,
    runBlockCommand,
} from "./block-commands";
import type { SlashBridge, SlashState } from "./slash-command";

/**
 * Menu des blocs ouvert par « / ». Les flèches et Entrée sont reçues du plugin
 * de suggestion via le pont, pour ne pas voler le focus à l'éditeur.
 */
export default function SlashMenu({
    editor,
    bridge,
    actions,
}: {
    editor: Editor;
    bridge: SlashBridge;
    actions: EditorActions;
}) {
    const [state, setState] = useState<SlashState | null>(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const listRef = useRef<HTMLDivElement>(null);
    const commands = useMemo(
        () => filterBlockCommands(state?.query ?? ""),
        [state?.query],
    );

    // Les rappels lisent l'état courant via des refs : le plugin garde la même fonction.
    const latest = useRef({ state, commands, activeIndex });
    latest.current = { state, commands, activeIndex };

    useEffect(() => {
        bridge.onChange = (next) => {
            setState(next);
            setActiveIndex(0);
        };
        bridge.onKeyDown = (event) => {
            const current = latest.current;

            if (!current.state || current.commands.length === 0) {
                return false;
            }

            if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                const step = event.key === "ArrowDown" ? 1 : -1;
                setActiveIndex(
                    (current.activeIndex + step + current.commands.length) %
                        current.commands.length,
                );

                return true;
            }

            if (event.key === "Enter" || event.key === "Tab") {
                runBlockCommand(
                    editor,
                    current.state.range,
                    current.commands[current.activeIndex],
                    actions,
                );
                setState(null);

                return true;
            }

            return false;
        };
    }, [bridge, editor, actions]);

    useEffect(() => {
        listRef.current
            ?.querySelector(`[data-index="${activeIndex}"]`)
            ?.scrollIntoView({ block: "nearest" });
    }, [activeIndex]);

    if (!state?.rect || commands.length === 0) {
        return null;
    }

    return createPortal(
        <div
            ref={listRef}
            role="listbox"
            aria-label="Blocs"
            className="fixed z-50 max-h-80 w-72 overflow-y-auto rounded-lg border bg-popover p-1 text-popover-foreground shadow-lg"
            style={{
                top: state.rect.bottom + 6,
                left: state.rect.left,
            }}
        >
            <p className="px-2 pt-1 pb-1.5 text-xs text-muted-foreground">
                Blocs
            </p>
            {commands.map((command, index) => (
                <button
                    key={command.title}
                    type="button"
                    role="option"
                    aria-selected={index === activeIndex}
                    data-index={index}
                    onMouseEnter={() => setActiveIndex(index)}
                    onMouseDown={(event) => {
                        event.preventDefault();
                        runBlockCommand(editor, state.range, command, actions);
                        setState(null);
                    }}
                    className={cn(
                        "flex w-full items-center gap-3 rounded-md px-2 py-1.5 text-left",
                        index === activeIndex && "bg-accent",
                    )}
                >
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-md border bg-background">
                        <command.icon className="size-4" />
                    </span>
                    <span className="min-w-0">
                        <span className="block text-sm font-medium">
                            {command.title}
                        </span>
                        <span className="block truncate text-xs text-muted-foreground">
                            {command.description}
                        </span>
                    </span>
                </button>
            ))}
        </div>,
        document.body,
    );
}
