import { post } from '@/hooks/use-ai-text-assist';
import { Loader2, Sparkles } from 'lucide-react';
import { useState } from 'react';
import AiAssistController from '@/actions/App/Http/Controllers/Admin/AiAssistController';
import FormSelect from '@/components/admin/form-select';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { EstimatorItem } from '@/types';

export const ESTIMATOR_KINDS = [
    { value: 'project_type', label: 'Type de projet' },
    { value: 'feature', label: 'Option' },
] as const;

export const ESTIMATOR_BILLINGS = [
    { value: 'fixed', label: 'Forfait estimé' },
    { value: 'hourly', label: "Facturé à l'heure" },
] as const;

type Suggestion = {
    name: { fr: string; en: string };
    description: { fr: string | null; en: string | null };
    hours_min: number;
    hours_max: number;
    reasoning: string | null;
};

/**
 * Champs communs aux formulaires de création et de modification d'un élément de
 * l'estimateur, avec « Proposer avec l'IA » : la suggestion remplit les champs, à relire.
 */
export default function EstimatorItemFields({
    estimatorItem,
    errors,
}: {
    estimatorItem?: EstimatorItem;
    errors: Record<string, string | undefined>;
}) {
    const [kind, setKind] = useState<EstimatorItem['kind']>(
        estimatorItem?.kind ?? 'project_type',
    );
    const [subject, setSubject] = useState(estimatorItem?.name.fr ?? '');
    const [notes, setNotes] = useState('');
    const [pending, setPending] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);
    const [suggestion, setSuggestion] = useState<Suggestion | null>(null);
    // Une suggestion change les valeurs par défaut : on recrée les champs pour les afficher.
    const [version, setVersion] = useState(0);

    const values = {
        name: suggestion?.name ?? estimatorItem?.name,
        description: suggestion
            ? {
                  fr: suggestion.description.fr ?? '',
                  en: suggestion.description.en ?? '',
              }
            : (estimatorItem?.description ?? undefined),
        hours_min: suggestion?.hours_min ?? estimatorItem?.hours_min ?? 8,
        hours_max: suggestion?.hours_max ?? estimatorItem?.hours_max ?? 16,
    };

    async function suggest() {
        if (subject.trim() === '') {
            setAiError("Indiquez d'abord ce qu'il faut chiffrer.");

            return;
        }

        setPending(true);
        setAiError(null);

        try {
            const response = await post<{ item: Suggestion }>(
                AiAssistController.suggestEstimatorItem.url(),
                { kind, name: subject.trim(), notes: notes.trim() || null },
            );

            setSuggestion(response.item);
            setVersion((current) => current + 1);
        } catch {
            setAiError('Suggestion indisponible pour le moment, réessayez.');
        } finally {
            setPending(false);
        }
    }

    return (
        <>
            <Field data-invalid={!!errors.kind}>
                <FieldLabel htmlFor="kind">Nature *</FieldLabel>
                <FormSelect
                    id="kind"
                    name="kind"
                    required
                    defaultValue={kind}
                    onValueChange={(value) =>
                        setKind(value as EstimatorItem['kind'])
                    }
                >
                    {ESTIMATOR_KINDS.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </FormSelect>
                <FieldDescription>
                    Le visiteur choisit un seul type de projet (la base), puis
                    autant d'options qu'il veut, qui s'y ajoutent.
                </FieldDescription>
                <FieldError>{errors.kind}</FieldError>
            </Field>

            <div className="space-y-3 rounded-lg border border-dashed p-4">
                <p className="flex items-center gap-2 text-sm font-medium">
                    <Sparkles className="size-4" /> Proposer avec l'IA
                </p>
                <Input
                    value={subject}
                    onChange={(event) => setSubject(event.target.value)}
                    placeholder="Ce qu'il faut chiffrer, ex. « Boutique en ligne »"
                    aria-label="Ce qu'il faut chiffrer"
                />
                <Textarea
                    value={notes}
                    onChange={(event) => setNotes(event.target.value)}
                    placeholder="Précisions (facultatif) : ce qui est compris, les contraintes…"
                    aria-label="Précisions pour l'IA"
                    rows={2}
                />
                <div className="flex flex-wrap items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => void suggest()}
                        disabled={pending}
                    >
                        {pending ? (
                            <Loader2 className="animate-spin" />
                        ) : (
                            <Sparkles />
                        )}
                        Proposer le nom, la description et les heures
                    </Button>
                    {aiError && (
                        <span className="text-sm text-destructive">
                            {aiError}
                        </span>
                    )}
                </div>
                {suggestion?.reasoning && (
                    <p className="text-sm text-muted-foreground">
                        <strong>Raisonnement de l'IA :</strong>{' '}
                        {suggestion.reasoning} Relisez avant d'enregistrer.
                    </p>
                )}
            </div>

            <div key={version} className="contents">
                <TranslatableField
                    name="name"
                    label="Nom"
                    required
                    defaultValue={values.name}
                    errors={{ fr: errors['name.fr'], en: errors['name.en'] }}
                />

                <TranslatableField
                    name="description"
                    label="Description"
                    textarea
                    defaultValue={values.description}
                    errors={{
                        fr: errors['description.fr'],
                        en: errors['description.en'],
                    }}
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field data-invalid={!!errors.hours_min}>
                        <FieldLabel htmlFor="hours_min">
                            Heures de travail (min) *
                        </FieldLabel>
                        <Input
                            id="hours_min"
                            name="hours_min"
                            type="number"
                            min={0}
                            required
                            defaultValue={values.hours_min}
                        />
                        <FieldError>{errors.hours_min}</FieldError>
                    </Field>
                    <Field data-invalid={!!errors.hours_max}>
                        <FieldLabel htmlFor="hours_max">
                            Heures de travail (max) *
                        </FieldLabel>
                        <Input
                            id="hours_max"
                            name="hours_max"
                            type="number"
                            min={0}
                            required
                            defaultValue={values.hours_max}
                        />
                        <FieldError>{errors.hours_max}</FieldError>
                    </Field>
                </div>
            </div>
            <FieldDescription>
                Heures réelles, sans marge : le site ajoute la marge pour
                imprévus et applique votre taux horaire (réglages de
                l'estimateur).
            </FieldDescription>

            {kind === 'project_type' && (
                <Field data-invalid={!!errors.billing}>
                    <FieldLabel htmlFor="billing">
                        Présenté au visiteur *
                    </FieldLabel>
                    <FormSelect
                        id="billing"
                        name="billing"
                        required
                        defaultValue={estimatorItem?.billing ?? 'fixed'}
                    >
                        {ESTIMATOR_BILLINGS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldDescription>
                        À l'heure : le visiteur voit les heures et votre taux,
                        avec le total à titre indicatif. À réserver aux projets
                        dont le périmètre est flou ou évolutif.
                    </FieldDescription>
                    <FieldError>{errors.billing}</FieldError>
                </Field>
            )}

            <Field orientation="horizontal">
                <input type="hidden" name="is_active" value="0" />
                <Checkbox
                    id="is_active"
                    name="is_active"
                    value="1"
                    defaultChecked={estimatorItem?.is_active ?? true}
                />
                <FieldLabel htmlFor="is_active">
                    Proposé aux visiteurs
                </FieldLabel>
            </Field>

            <Field data-invalid={!!errors.sort_order}>
                <FieldLabel htmlFor="sort_order">Ordre</FieldLabel>
                <Input
                    id="sort_order"
                    name="sort_order"
                    type="number"
                    min={0}
                    defaultValue={estimatorItem?.sort_order ?? 0}
                />
                <FieldError>{errors.sort_order}</FieldError>
            </Field>
        </>
    );
}
