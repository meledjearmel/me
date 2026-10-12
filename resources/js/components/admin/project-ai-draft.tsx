import { Languages, Loader2, Sparkles } from "lucide-react";
import { type RefObject, useState } from "react";
import AiAssistController from "@/actions/App/Http/Controllers/Admin/AiAssistController";
import type { GitHubRepositoryOption } from "@/components/admin/project-github-field";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Field, FieldDescription, FieldLabel } from "@/components/ui/field";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { useAiTextAssist } from "@/hooks/use-ai-text-assist";

/** Champs proposés par l'IA, en français seulement (voir ProjectDraftService). */
export type ProjectDraft = {
    title: string;
    slug: string;
    tagline: string | null;
    role: string | null;
    client: string | null;
    platform: string | null;
    context: string;
    challenges: string | null;
    realization: string | null;
    result: string | null;
    decisions: { choice: string; reason: string }[];
    key_figures: { value: string; label: string }[];
    started_on: string | null;
    ended_on: string | null;
    team_size: number | null;
    technologies: number[];
    domains: number[];
    job_profiles: number[];
    github_repository?: string;
    repo_url?: string;
    demo_url?: string | null;
    is_open_source?: boolean;
};

const NONE = "__none__";

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : "";
}

/**
 * « Pré-remplir avec l'IA » : à partir d'un dépôt public et/ou de notes, l'IA propose
 * les champs français du formulaire, à relire avant d'enregistrer.
 */
export function ProjectAiDraftButton({
    repositories,
    onDraft,
}: {
    repositories: GitHubRepositoryOption[];
    onDraft: (draft: ProjectDraft) => void;
}) {
    const [open, setOpen] = useState(false);
    const [repository, setRepository] = useState("");
    const [notes, setNotes] = useState("");
    const [pending, setPending] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function handleSubmit() {
        if (repository === "" && notes.trim() === "") {
            setError("Choisissez un dépôt ou écrivez quelques notes.");

            return;
        }

        setPending(true);
        setError(null);

        try {
            const response = await fetch(
                AiAssistController.draftProject.url(),
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-XSRF-TOKEN": csrfToken(),
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({
                        github_repository: repository || null,
                        notes: notes.trim() || null,
                    }),
                },
            );
            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                setError(
                    body.errors
                        ? (Object.values(
                              body.errors as Record<string, string[]>,
                          )[0]?.[0] ?? null)
                        : (body.message ??
                              "Pré-remplissage indisponible pour le moment, réessayez."),
                );

                return;
            }

            onDraft(body.project as ProjectDraft);
            setOpen(false);
        } catch {
            setError("Pré-remplissage indisponible pour le moment, réessayez.");
        } finally {
            setPending(false);
        }
    }

    return (
        <>
            <Button
                type="button"
                variant="outline"
                onClick={() => setOpen(true)}
            >
                <Sparkles />
                Pré-remplir avec l'IA
            </Button>

            <Dialog
                open={open}
                onOpenChange={(value) => !pending && setOpen(value)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Pré-remplir avec l'IA</DialogTitle>
                        <DialogDescription>
                            L'IA propose les champs en français à partir d'un
                            dépôt et/ou de vos notes. Rien n'est enregistré :
                            vous relisez, traduisez, puis créez le projet.
                        </DialogDescription>
                    </DialogHeader>

                    <Field>
                        <FieldLabel htmlFor="draft_repository">
                            Dépôt GitHub
                        </FieldLabel>
                        <Select
                            value={repository || NONE}
                            onValueChange={(value) =>
                                setRepository(value === NONE ? "" : value)
                            }
                        >
                            <SelectTrigger id="draft_repository">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>Aucun</SelectItem>
                                {repositories.map((option) => (
                                    <SelectItem
                                        key={option.full_name}
                                        value={option.full_name}
                                        disabled={option.private}
                                    >
                                        {option.full_name}
                                        {option.private
                                            ? " (privé : non envoyé)"
                                            : ""}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <FieldDescription>
                            Seuls les dépôts publics sont envoyés à l'IA. Pour
                            un dépôt privé, décrivez-le dans les notes.
                        </FieldDescription>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="draft_notes">Notes</FieldLabel>
                        <Textarea
                            id="draft_notes"
                            rows={6}
                            value={notes}
                            onChange={(event) => setNotes(event.target.value)}
                            placeholder="Le besoin, le client, les dates, ce que vous avez fait, les chiffres…"
                        />
                    </Field>

                    {error && (
                        <p className="text-sm text-destructive">{error}</p>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={handleSubmit}
                            disabled={pending}
                        >
                            {pending ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Sparkles />
                            )}
                            {pending
                                ? "Rédaction… (jusqu’à une minute)"
                                : "Pré-remplir"}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

/**
 * Traduit vers l'anglais chaque champ français rempli dont le champ anglais est vide
 * (noms en « …[fr] » / « …[en] »), un par un.
 */
export function TranslateFormButton({
    formRef,
}: {
    formRef: RefObject<HTMLDivElement | null>;
}) {
    const { translate } = useAiTextAssist();
    const [progress, setProgress] = useState<string | null>(null);
    const [message, setMessage] = useState<string | null>(null);

    async function handleTranslate() {
        const form = formRef.current;

        if (!form) {
            return;
        }

        const pairs = Array.from(
            form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
                '[name$="[fr]"]',
            ),
        )
            .map((source) => ({
                source,
                target: form.querySelector<
                    HTMLInputElement | HTMLTextAreaElement
                >(
                    `[name="${CSS.escape(source.name.replace(/\[fr\]$/, "[en]"))}"]`,
                ),
            }))
            .filter(
                ({ source, target }) =>
                    target &&
                    source.value.trim() !== "" &&
                    target.value.trim() === "",
            );

        setMessage(null);
        let failures = 0;

        for (const [index, { source, target }] of pairs.entries()) {
            setProgress(`${index + 1}/${pairs.length}`);
            const translated = await translate(source.value.trim(), "fr", "en");

            if (translated === null || !target) {
                failures++;

                continue;
            }

            target.value = translated;
        }

        setProgress(null);
        setMessage(
            pairs.length === 0
                ? "Aucun champ anglais vide à traduire."
                : failures > 0
                  ? `${failures} champ(s) non traduit(s) : complétez-les avec le bouton de traduction du champ.`
                  : "Champs anglais remplis : relisez-les avant d’enregistrer.",
        );
    }

    return (
        <div className="flex flex-wrap items-center gap-3">
            <Button
                type="button"
                variant="outline"
                onClick={handleTranslate}
                disabled={progress !== null}
            >
                {progress ? (
                    <Loader2 className="animate-spin" />
                ) : (
                    <Languages />
                )}
                {progress ? `Traduction ${progress}…` : "Traduire en anglais"}
            </Button>
            {message && (
                <span className="text-sm text-muted-foreground">{message}</span>
            )}
        </div>
    );
}
