import { Languages, Loader2, Sparkles, WandSparkles } from "lucide-react";
import { type RefObject, useRef, useState } from "react";
import MentionTextarea from "@/components/admin/mention-textarea";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Field, FieldError, FieldLabel } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import {
    type Locale,
    type TextTone,
    useAiTextAssist,
} from "@/hooks/use-ai-text-assist";

const TONES: { value: TextTone; label: string }[] = [
    { value: "formal", label: "Plus formel" },
    { value: "friendly", label: "Plus chaleureux" },
    { value: "concise", label: "Plus concis" },
    { value: "enthusiastic", label: "Plus enthousiaste" },
];

const LOCALES = ["fr", "en"] as const;
const OTHER_LOCALE: Record<Locale, Locale> = { fr: "en", en: "fr" };

type TranslatableFieldProps = {
    name: string;
    label: string;
    defaultValue?: { fr?: string; en?: string };
    errors?: { fr?: string; en?: string };
    textarea?: boolean;
    required?: boolean;
    maxLength?: number;
    /**
     * Remplace « Améliorer » par « Générer » : renvoie la technologie à décrire
     * (lue dans le formulaire), et l'IA remplit les deux langues.
     */
    technologySource?: () => { name: string; category: string | null };
    /** Zone de texte où « @ » insère une mention d'un élément du site. */
    mentions?: boolean;
};

export default function TranslatableField({
    name,
    label,
    defaultValue,
    errors,
    textarea = false,
    required = false,
    maxLength,
    technologySource,
    mentions = false,
}: TranslatableFieldProps) {
    const TextareaComponent = mentions ? MentionTextarea : Textarea;
    const refs = {
        fr: useRef<HTMLInputElement | HTMLTextAreaElement>(null),
        en: useRef<HTMLInputElement | HTMLTextAreaElement>(null),
    };
    const { translate, improve, describeTechnology, pending } =
        useAiTextAssist();
    const [assistError, setAssistError] = useState<string | null>(null);
    const [improveTarget, setImproveTarget] = useState<Locale | null>(null);
    const [tone, setTone] = useState<TextTone | null>(null);
    const [instructions, setInstructions] = useState("");

    async function handleTranslate(locale: Locale) {
        setAssistError(null);
        const source = OTHER_LOCALE[locale];
        const sourceText = refs[source].current?.value.trim() ?? "";

        if (sourceText === "") {
            setAssistError(
                `Remplissez d'abord le champ ${source.toUpperCase()} pour pouvoir le traduire.`,
            );

            return;
        }

        const translated = await translate(sourceText, source, locale);

        if (translated === null) {
            setAssistError(
                "Traduction indisponible pour le moment, réessayez.",
            );

            return;
        }

        const field = refs[locale].current;

        if (field) {
            field.value = translated;
        }
    }

    async function handleGenerate() {
        if (!technologySource) {
            return;
        }

        setAssistError(null);
        const { name, category } = technologySource();

        if (name.trim() === "") {
            setAssistError(
                "Renseignez d'abord le nom de la technologie pour générer sa description.",
            );

            return;
        }

        const description = await describeTechnology(name.trim(), category);

        if (description === null) {
            setAssistError(
                "Génération indisponible pour le moment, réessayez.",
            );

            return;
        }

        LOCALES.forEach((locale) => {
            const field = refs[locale].current;

            if (field) {
                field.value = description[locale];
            }
        });
    }

    function openImproveDialog(locale: Locale) {
        setAssistError(null);
        const currentText = refs[locale].current?.value.trim() ?? "";

        if (currentText === "") {
            setAssistError("Rien à améliorer : le champ est vide.");

            return;
        }

        setTone(null);
        setInstructions("");
        setImproveTarget(locale);
    }

    async function handleImprove() {
        if (improveTarget === null) {
            return;
        }

        const currentText = refs[improveTarget].current?.value.trim() ?? "";
        const improved = await improve(
            currentText,
            improveTarget,
            tone,
            instructions,
        );

        if (improved === null) {
            setAssistError(
                "Amélioration indisponible pour le moment, réessayez.",
            );
            setImproveTarget(null);

            return;
        }

        const field = refs[improveTarget].current;

        if (field) {
            field.value = improved;
        }

        setImproveTarget(null);
    }

    return (
        <div className="space-y-2">
            <div className="grid gap-4 sm:grid-cols-2">
                {LOCALES.map((locale) => (
                    <Field key={locale} data-invalid={!!errors?.[locale]}>
                        <div className="flex items-center justify-between gap-2">
                            <FieldLabel htmlFor={`${name}-${locale}`}>
                                {label} ({locale.toUpperCase()})
                                {required && " *"}
                            </FieldLabel>
                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    disabled={pending !== null}
                                    onClick={() => handleTranslate(locale)}
                                    title={`Traduire depuis le ${OTHER_LOCALE[locale].toUpperCase()}`}
                                >
                                    {pending === "translate" ? (
                                        <Loader2 className="animate-spin" />
                                    ) : (
                                        <Languages />
                                    )}
                                    Traduire
                                </Button>
                                {technologySource ? (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        disabled={pending !== null}
                                        onClick={handleGenerate}
                                        title="Rédiger la description (FR et EN) à partir du nom de la technologie"
                                    >
                                        {pending === "describe" ? (
                                            <Loader2 className="animate-spin" />
                                        ) : (
                                            <WandSparkles />
                                        )}
                                        Générer
                                    </Button>
                                ) : (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        disabled={pending !== null}
                                        onClick={() =>
                                            openImproveDialog(locale)
                                        }
                                        title="Améliorer ce texte"
                                    >
                                        <Sparkles />
                                        Améliorer
                                    </Button>
                                )}
                            </div>
                        </div>
                        {textarea ? (
                            <TextareaComponent
                                id={`${name}-${locale}`}
                                name={`${name}[${locale}]`}
                                ref={
                                    refs[
                                        locale
                                    ] as RefObject<HTMLTextAreaElement | null>
                                }
                                defaultValue={defaultValue?.[locale]}
                                required={required}
                                maxLength={maxLength}
                                aria-invalid={!!errors?.[locale]}
                            />
                        ) : (
                            <Input
                                id={`${name}-${locale}`}
                                name={`${name}[${locale}]`}
                                ref={
                                    refs[
                                        locale
                                    ] as RefObject<HTMLInputElement | null>
                                }
                                defaultValue={defaultValue?.[locale]}
                                required={required}
                                maxLength={maxLength}
                                aria-invalid={!!errors?.[locale]}
                            />
                        )}
                        <FieldError>{errors?.[locale]}</FieldError>
                    </Field>
                ))}
            </div>

            {assistError && (
                <p className="text-sm text-destructive">{assistError}</p>
            )}

            <Dialog
                open={improveTarget !== null}
                onOpenChange={(open) => !open && setImproveTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Améliorer le texte</DialogTitle>
                        <DialogDescription>
                            Choisissez un ton et, si besoin, précisez une
                            consigne. Le texte{" "}
                            {improveTarget &&
                                `(${improveTarget.toUpperCase()})`}{" "}
                            sera réécrit dans la même langue.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <Field>
                            <FieldLabel htmlFor="ai-tone">Ton</FieldLabel>
                            <Select
                                value={tone ?? undefined}
                                onValueChange={(value) =>
                                    setTone(value as TextTone)
                                }
                            >
                                <SelectTrigger id="ai-tone">
                                    <SelectValue placeholder="Sans consigne de ton" />
                                </SelectTrigger>
                                <SelectContent>
                                    {TONES.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="ai-instructions">
                                Consigne supplémentaire (facultatif)
                            </FieldLabel>
                            <Textarea
                                id="ai-instructions"
                                value={instructions}
                                onChange={(event) =>
                                    setInstructions(event.target.value)
                                }
                                placeholder="Ex. : mettre en avant la disponibilité du projet"
                                maxLength={500}
                            />
                        </Field>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setImproveTarget(null)}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="button"
                            onClick={handleImprove}
                            disabled={pending !== null}
                        >
                            {pending === "improve" && (
                                <Loader2 className="animate-spin" />
                            )}
                            Générer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
