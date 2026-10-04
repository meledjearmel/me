import { getHTMLFromFragment, type Editor, type Range } from "@tiptap/core";
import { ArrowUp, Loader2, Sparkles, X } from "lucide-react";
import { forwardRef, useImperativeHandle, useRef, useState } from "react";
import AiAssistController from "@/actions/App/Http/Controllers/Admin/AiAssistController";
import { Button } from "@/components/ui/button";
import { post, type Locale } from "@/hooks/use-ai-text-assist";
import { cn } from "@/lib/utils";

/** Longueur du texte précédant le curseur envoyée comme contexte. */
const CONTEXT_LENGTH = 1500;

const SELECTION_PRESETS = [
    "Améliore la formulation",
    "Corrige les fautes",
    "Rends-le plus concis",
    "Développe davantage",
    "Transforme en liste à puces",
];

const CURSOR_PRESETS = [
    "Continue l’écriture",
    "Écris une introduction",
    "Écris une conclusion",
    "Ajoute un tableau comparatif",
];

export type AiBarHandle = {
    /** Mémorise la sélection courante et place le curseur dans la barre. */
    focus: () => void;
};

type ArticleMeta = () => { title: string; excerpt: string };

/**
 * Barre IA en bas de l'éditeur (façon « Agent editor ») : réécrit la sélection
 * ou rédige au curseur, via l'assistance IA de l'admin.
 */
const AiBar = forwardRef<
    AiBarHandle,
    { editor: Editor; locale: Locale; articleMeta: ArticleMeta }
>(function AiBar({ editor, locale, articleMeta }, ref) {
    const inputRef = useRef<HTMLTextAreaElement>(null);
    const [instruction, setInstruction] = useState("");
    const [target, setTarget] = useState<Range | null>(null);
    const [pending, setPending] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function captureSelection() {
        const { from, to } = editor.state.selection;
        setTarget(from === to ? { from, to: from } : { from, to });
    }

    useImperativeHandle(ref, () => ({
        focus: () => {
            captureSelection();
            inputRef.current?.focus();
        },
    }));

    const hasSelection = target !== null && target.from !== target.to;
    const presets = hasSelection ? SELECTION_PRESETS : CURSOR_PRESETS;

    async function submit(text: string) {
        const consigne = text.trim();

        if (consigne === "" || pending) {
            return;
        }

        const range = target ?? {
            from: editor.state.selection.from,
            to: editor.state.selection.to,
        };
        const { doc, schema } = editor.state;
        const selection =
            range.from !== range.to
                ? getHTMLFromFragment(
                      doc.slice(range.from, range.to).content,
                      schema,
                  )
                : null;
        const context = selection
            ? null
            : doc.textBetween(
                  Math.max(0, range.from - CONTEXT_LENGTH),
                  range.from,
                  "\n\n",
              ) || null;

        setPending(true);
        setError(null);

        try {
            const { text: html } = await post(
                AiAssistController.writePost.url(),
                {
                    instruction: consigne,
                    locale,
                    selection,
                    context,
                    ...articleMeta(),
                },
            );

            editor
                .chain()
                .focus()
                .insertContentAt(range, html, {
                    parseOptions: { preserveWhitespace: false },
                })
                .run();
            setInstruction("");
            setTarget(null);
        } catch {
            setError("L’assistant est indisponible pour le moment, réessayez.");
        } finally {
            setPending(false);
        }
    }

    return (
        <div className="sticky bottom-4 z-20 mx-auto mt-6 w-full max-w-2xl px-4">
            <div
                className={cn(
                    "rounded-xl border bg-background/95 p-2 shadow-lg backdrop-blur",
                    "ring-violet-500/30 focus-within:ring-2",
                )}
            >
                {(hasSelection || error) && (
                    <div className="mb-1.5 flex items-center gap-2 px-1 text-xs">
                        {hasSelection && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-violet-500/10 px-2 py-0.5 text-violet-700 dark:text-violet-300">
                                Sur la sélection
                                <button
                                    type="button"
                                    aria-label="Ne plus cibler la sélection"
                                    onClick={() => setTarget(null)}
                                >
                                    <X className="size-3" />
                                </button>
                            </span>
                        )}
                        {error && (
                            <span className="text-destructive">{error}</span>
                        )}
                    </div>
                )}

                <div className="flex items-end gap-2">
                    <Sparkles className="mb-2 ml-1 size-4 shrink-0 text-violet-500" />
                    <textarea
                        ref={inputRef}
                        rows={1}
                        value={instruction}
                        disabled={pending}
                        onFocus={() => target === null && captureSelection()}
                        onChange={(event) => setInstruction(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === "Enter" && !event.shiftKey) {
                                event.preventDefault();
                                void submit(instruction);
                            }

                            if (event.key === "Escape") {
                                setTarget(null);
                                editor.commands.focus();
                            }
                        }}
                        placeholder={
                            hasSelection
                                ? "Que faire de la sélection ?"
                                : "Demandez à l’IA d’écrire ou de modifier l’article…"
                        }
                        aria-label="Consigne pour l’IA"
                        className="max-h-32 min-h-9 flex-1 resize-none bg-transparent py-2 text-sm outline-none placeholder:text-muted-foreground field-sizing-content"
                    />
                    <Button
                        type="button"
                        size="icon"
                        className="size-8 shrink-0 rounded-full"
                        disabled={pending || instruction.trim() === ""}
                        onClick={() => void submit(instruction)}
                        aria-label="Envoyer"
                    >
                        {pending ? (
                            <Loader2 className="animate-spin" />
                        ) : (
                            <ArrowUp />
                        )}
                    </Button>
                </div>

                <div className="mt-1.5 flex flex-wrap gap-1 px-1">
                    {presets.map((preset) => (
                        <button
                            key={preset}
                            type="button"
                            disabled={pending}
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => void submit(preset)}
                            className="rounded-full border px-2.5 py-0.5 text-xs text-muted-foreground hover:bg-accent hover:text-foreground disabled:opacity-50"
                        >
                            {preset}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
});

export default AiBar;
