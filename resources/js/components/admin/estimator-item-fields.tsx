import { Loader2, Sparkles } from 'lucide-react';
import { useState } from 'react';
import AiAssistController from '@/actions/App/Http/Controllers/Admin/AiAssistController';
import CheckboxGroup from '@/components/admin/checkbox-group';
import EstimatorRateInputs from '@/components/admin/estimator-rate-inputs';
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
import { post } from '@/hooks/use-ai-text-assist';
import type {
    EstimatorCurrency,
    EstimatorItem,
    EstimatorPeriod,
    EstimatorRateOption,
} from '@/types';

export const ESTIMATOR_KINDS = [
    { value: 'project_type', label: 'Type de projet' },
    { value: 'feature', label: 'Option' },
] as const;

export const ESTIMATOR_BILLINGS = [
    { value: 'fixed', label: 'Forfait estimé' },
    { value: 'hourly', label: "Facturé à l'heure" },
] as const;

export const ESTIMATOR_PRICINGS = [
    { value: 'hours', label: 'Des heures en plus' },
    { value: 'percent', label: 'Un pourcentage des heures du projet' },
    {
        value: 'recurring',
        label: 'Un service récurrent (heures par mois, payé à chaque période)',
    },
] as const;

export const ESTIMATOR_PERIODS: { value: EstimatorPeriod; label: string }[] = [
    { value: 'monthly', label: 'Mensuel' },
    { value: 'quarterly', label: 'Trimestriel' },
    { value: 'semiannual', label: 'Semestriel' },
    { value: 'yearly', label: 'Annuel' },
];

type Suggestion = {
    name: { fr: string; en: string };
    description: { fr: string | null; en: string | null };
    pricing: EstimatorItem['pricing'];
    percent: number | null;
    periods: EstimatorItem['periods'];
    hours_min: number;
    hours_max: number;
    reasoning: string | null;
};

/**
 * Champs communs aux formulaires de création et de modification d'un élément de
 * l'estimateur, avec « Proposer avec l'IA » : la suggestion remplit les champs, à relire.
 * Une option se chiffre en heures ou en pourcentage, et peut viser certains types de projet.
 */
export default function EstimatorItemFields({
    estimatorItem,
    projectTypes,
    rates,
    globalRates,
    defaultKind = 'project_type',
    errors,
}: {
    estimatorItem?: EstimatorItem;
    projectTypes: { id: number; label: string }[];
    rates: EstimatorRateOption[];
    globalRates: Partial<Record<EstimatorCurrency, number>>;
    defaultKind?: EstimatorItem['kind'];
    errors: Record<string, string | undefined>;
}) {
    const [kind, setKind] = useState<EstimatorItem['kind']>(
        estimatorItem?.kind ?? defaultKind,
    );
    const [pricing, setPricing] = useState<EstimatorItem['pricing']>(
        estimatorItem?.pricing ?? 'hours',
    );
    const [subject, setSubject] = useState(estimatorItem?.name.fr ?? '');
    const [notes, setNotes] = useState('');
    const [pending, setPending] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);
    const [suggestion, setSuggestion] = useState<Suggestion | null>(null);
    // Une suggestion change les valeurs par défaut : on recrée les champs pour les afficher.
    const [version, setVersion] = useState(0);

    const [rateId, setRateId] = useState(
        estimatorItem?.estimator_rate_id
            ? String(estimatorItem.estimator_rate_id)
            : '',
    );

    const isFeature = kind === 'feature';
    const isPercent = isFeature && pricing === 'percent';
    const isRecurring = isFeature && pricing === 'recurring';
    const selectedRate = rates.find((rate) => String(rate.id) === rateId);

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
        percent: suggestion?.percent ?? estimatorItem?.percent ?? 15,
        periods: suggestion?.periods ??
            estimatorItem?.periods ?? { monthly: 0, yearly: 10 },
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
            setPricing(response.item.pricing);
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
                    les options proposées pour ce type, qui s'y ajoutent.
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
                        Proposer le nom, la description et le chiffrage
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

                {isFeature && (
                    <Field data-invalid={!!errors.pricing}>
                        <FieldLabel htmlFor="pricing">
                            Ce que l'option ajoute *
                        </FieldLabel>
                        <FormSelect
                            id="pricing"
                            name="pricing"
                            required
                            defaultValue={pricing}
                            onValueChange={(value) =>
                                setPricing(value as EstimatorItem['pricing'])
                            }
                        >
                            {ESTIMATOR_PRICINGS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </FormSelect>
                        <FieldDescription>
                            Le pourcentage convient aux options dont le coût
                            grandit avec le projet (multilingue, accessibilité,
                            design sur mesure…).
                        </FieldDescription>
                        <FieldError>{errors.pricing}</FieldError>
                    </Field>
                )}

                {isPercent ? (
                    <Field data-invalid={!!errors.percent}>
                        <FieldLabel htmlFor="percent">
                            Pourcentage ajouté aux heures du projet *
                        </FieldLabel>
                        <Input
                            id="percent"
                            name="percent"
                            type="number"
                            min={1}
                            max={500}
                            required
                            defaultValue={values.percent}
                        />
                        <FieldDescription>
                            Appliqué aux heures du type de projet et des options
                            en heures, avant la marge pour imprévus, et payé au
                            taux du type de projet.
                        </FieldDescription>
                        <FieldError>{errors.percent}</FieldError>
                    </Field>
                ) : (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field data-invalid={!!errors.hours_min}>
                                <FieldLabel htmlFor="hours_min">
                                    Heures de travail (min)
                                    {isRecurring ? ' par mois' : ''} *
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
                                    Heures de travail (max)
                                    {isRecurring ? ' par mois' : ''} *
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
                        <FieldDescription>
                            {isRecurring
                                ? 'Heures réelles par mois, sans marge (un abonnement est un engagement fixe), à votre taux horaire. Hors du total et du délai du projet.'
                                : 'Heures réelles, sans marge : le site ajoute la marge pour imprévus et applique votre taux horaire.'}
                        </FieldDescription>
                    </>
                )}

                {isRecurring && (
                    <div className="space-y-3 rounded-lg border p-4">
                        <div>
                            <p className="text-sm font-medium">
                                Périodes proposées
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Le visiteur choisit parmi les périodes cochées ;
                                la remise s'applique au prix de la période
                                (heures par mois × nombre de mois).
                            </p>
                        </div>
                        {ESTIMATOR_PERIODS.map((period) => (
                            <PeriodRow
                                key={period.value}
                                period={period.value}
                                label={period.label}
                                defaultDiscount={values.periods?.[period.value]}
                                error={errors[`periods.${period.value}`]}
                            />
                        ))}
                        <FieldError>{errors.periods}</FieldError>
                    </div>
                )}
            </div>

            {isFeature && (
                <div>
                    {projectTypes.length > 0 ? (
                        <CheckboxGroup
                            label="Réservée aux types de projet"
                            name="project_type_ids"
                            options={projectTypes}
                            defaultSelectedIds={
                                estimatorItem?.project_type_ids ?? []
                            }
                        />
                    ) : null}
                    <FieldDescription>
                        Aucun coché : l'option est proposée pour tous les types
                        de projet.
                    </FieldDescription>
                    <FieldError>
                        {errors.project_type_ids ??
                            errors['project_type_ids.0']}
                    </FieldError>
                </div>
            )}

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

            {!isPercent && (
                <div className="space-y-4 rounded-lg border p-4">
                    <div>
                        <p className="text-sm font-medium">Tarif</p>
                        <p className="text-sm text-muted-foreground">
                            Les taux de l'élément, sinon ceux de sa grille,
                            sinon le taux global des réglages.
                        </p>
                    </div>
                    <Field data-invalid={!!errors.estimator_rate_id}>
                        <FieldLabel htmlFor="estimator_rate_id">
                            Grille tarifaire
                        </FieldLabel>
                        <FormSelect
                            id="estimator_rate_id"
                            name="estimator_rate_id"
                            defaultValue={String(rateId)}
                            onValueChange={(value) => setRateId(value)}
                        >
                            <option value="">Aucune (taux global)</option>
                            {rates.map((rate) => (
                                <option key={rate.id} value={String(rate.id)}>
                                    {rate.label}
                                </option>
                            ))}
                        </FormSelect>
                        <FieldError>{errors.estimator_rate_id}</FieldError>
                    </Field>
                    <EstimatorRateInputs
                        name="hourly_rates"
                        defaultValues={estimatorItem?.hourly_rates}
                        fallbacks={{ ...globalRates, ...selectedRate?.rates }}
                        errors={errors}
                    />
                    <FieldDescription>
                        Laissez vide pour suivre la grille ou le taux global
                        (rappelé en gris).
                    </FieldDescription>
                </div>
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

/** Une période d'une option récurrente : cochée, elle est proposée avec sa remise. */
function PeriodRow({
    period,
    label,
    defaultDiscount,
    error,
}: {
    period: EstimatorPeriod;
    label: string;
    defaultDiscount: number | undefined;
    error: string | undefined;
}) {
    const [offered, setOffered] = useState(defaultDiscount !== undefined);

    return (
        <Field orientation="horizontal" data-invalid={!!error}>
            <Checkbox
                id={`period_${period}`}
                checked={offered}
                onCheckedChange={(checked) => setOffered(checked === true)}
            />
            <FieldLabel htmlFor={`period_${period}`} className="min-w-28">
                {label}
            </FieldLabel>
            <Input
                name={`periods[${period}]`}
                type="number"
                min={0}
                max={90}
                disabled={!offered}
                defaultValue={defaultDiscount ?? 0}
                aria-label={`Remise ${label.toLowerCase()} (%)`}
                className="w-24"
            />
            <span className="text-sm text-muted-foreground">% de remise</span>
            <FieldError>{error}</FieldError>
        </Field>
    );
}
