import { Loader2, Sparkles } from 'lucide-react';
import { useState } from 'react';
import AiAssistController from '@/actions/App/Http/Controllers/Admin/AiAssistController';
import CheckboxGroup from '@/components/admin/checkbox-group';
import FormSelect from '@/components/admin/form-select';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { post } from '@/hooks/use-ai-text-assist';
import { PUBLICATION_STATUSES } from '@/lib/admin-options';
import type { EstimatorCurrency, Service } from '@/types';

const PRICE_MODES = [
    { value: 'estimator', label: "Calculé par l'estimateur" },
    { value: 'manual', label: 'Saisi à la main' },
    { value: 'quote', label: 'Sur devis (pas de prix affiché)' },
] as const;

const CURRENCIES: { value: EstimatorCurrency; label: string }[] = [
    { value: 'CHF', label: 'CHF' },
    { value: 'EUR', label: 'EUR' },
    { value: 'USD', label: 'USD' },
    { value: 'XOF', label: 'FCFA' },
];

type Draft = {
    title: string;
    slug: string;
    tagline: string | null;
    description: string | null;
    includes: string | null;
};

type Option = { id: number; label: string };

/**
 * Champs d'une offre de la page « Services », partagés par la création et la modification,
 * avec « Rédiger avec l'IA » : le français proposé remplit les champs, à relire puis traduire.
 */
export default function ServiceFields({
    service,
    projectTypes,
    projects,
    errors,
}: {
    service?: Service;
    projectTypes: Option[];
    projects: Option[];
    errors: Record<string, string | undefined>;
}) {
    const [priceMode, setPriceMode] = useState<Service['price_mode']>(
        service?.price_mode ??
            (projectTypes.length > 0 ? 'estimator' : 'quote'),
    );
    const [notes, setNotes] = useState('');
    const [pending, setPending] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);
    const [draft, setDraft] = useState<Draft | null>(null);
    // Un brouillon de l'IA change les valeurs par défaut : on recrée les champs pour les afficher.
    const [version, setVersion] = useState(0);

    const french = (
        value: string | null | undefined,
        fallback?: Partial<Record<'fr' | 'en', string>> | null,
    ) =>
        draft
            ? { fr: value ?? '', en: fallback?.en ?? '' }
            : (fallback ?? undefined);

    async function write() {
        setPending(true);
        setAiError(null);

        try {
            const response = await post<{ service: Draft }>(
                AiAssistController.draftService.url(),
                { notes },
            );

            setDraft(response.service);
            setVersion((current) => current + 1);
        } catch {
            setAiError('Rédaction indisponible pour le moment, réessayez.');
        } finally {
            setPending(false);
        }
    }

    return (
        <>
            <div className="space-y-3 rounded-lg border border-dashed p-4">
                <p className="flex items-center gap-2 text-sm font-medium">
                    <Sparkles className="size-4" /> Rédiger avec l'IA
                </p>
                <Textarea
                    value={notes}
                    onChange={(event) => setNotes(event.target.value)}
                    rows={3}
                    placeholder="Vos notes : pour qui, ce que vous faites, ce qui est compris, votre façon de travailler…"
                    aria-label="Notes pour l'IA"
                />
                <div className="flex flex-wrap items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => void write()}
                        disabled={pending || notes.trim().length < 10}
                    >
                        {pending ? (
                            <Loader2 className="animate-spin" />
                        ) : (
                            <Sparkles />
                        )}
                        Rédiger le texte en français
                    </Button>
                    {aiError && (
                        <span className="text-sm text-destructive">
                            {aiError}
                        </span>
                    )}
                </div>
                {draft && (
                    <p className="text-sm text-muted-foreground">
                        Texte proposé : relisez-le, puis traduisez chaque champ
                        avec le bouton de traduction.
                    </p>
                )}
            </div>

            <div key={version} className="contents">
                <TranslatableField
                    name="title"
                    label="Nom de l'offre"
                    required
                    defaultValue={french(draft?.title, service?.title)}
                    errors={{ fr: errors['title.fr'], en: errors['title.en'] }}
                />

                <Field data-invalid={!!errors.slug}>
                    <FieldLabel htmlFor="slug">Ancre *</FieldLabel>
                    <Input
                        id="slug"
                        name="slug"
                        required
                        defaultValue={draft?.slug ?? service?.slug ?? ''}
                        placeholder="application-mobile"
                    />
                    <FieldDescription>
                        L'adresse de l'offre sur la page : /services#ancre.
                    </FieldDescription>
                    <FieldError>{errors.slug}</FieldError>
                </Field>

                <TranslatableField
                    name="tagline"
                    label="Accroche"
                    defaultValue={french(draft?.tagline, service?.tagline)}
                    errors={{
                        fr: errors['tagline.fr'],
                        en: errors['tagline.en'],
                    }}
                />

                <TranslatableField
                    name="description"
                    label="Description"
                    textarea
                    defaultValue={french(
                        draft?.description,
                        service?.description,
                    )}
                    errors={{
                        fr: errors['description.fr'],
                        en: errors['description.en'],
                    }}
                />

                <TranslatableField
                    name="includes"
                    label="Ce qui est compris (une ligne par élément)"
                    textarea
                    defaultValue={french(draft?.includes, service?.includes)}
                    errors={{
                        fr: errors['includes.fr'],
                        en: errors['includes.en'],
                    }}
                />
            </div>

            <Field data-invalid={!!errors.price_mode}>
                <FieldLabel htmlFor="price_mode">
                    Prix « à partir de » *
                </FieldLabel>
                <FormSelect
                    id="price_mode"
                    name="price_mode"
                    required
                    defaultValue={priceMode}
                    onValueChange={(value) =>
                        setPriceMode(value as Service['price_mode'])
                    }
                >
                    {PRICE_MODES.map((mode) => (
                        <option key={mode.value} value={mode.value}>
                            {mode.label}
                        </option>
                    ))}
                </FormSelect>
                <FieldDescription>
                    Calculé : le bas de la fourchette du type de projet lié,
                    sans option, dans la devise du visiteur (son taux horaire
                    s'il est facturé à l'heure).
                </FieldDescription>
                <FieldError>{errors.price_mode}</FieldError>
            </Field>

            <Field data-invalid={!!errors.estimator_item_id}>
                <FieldLabel htmlFor="estimator_item_id">
                    Type de projet de l'estimateur
                    {priceMode === 'estimator' ? ' *' : ''}
                </FieldLabel>
                <FormSelect
                    id="estimator_item_id"
                    name="estimator_item_id"
                    required={priceMode === 'estimator'}
                    defaultValue={service?.estimator_item_id ?? ''}
                >
                    <option value="">Aucun</option>
                    {projectTypes.map((type) => (
                        <option key={type.id} value={type.id}>
                            {type.label}
                        </option>
                    ))}
                </FormSelect>
                <FieldDescription>
                    Sert aussi au bouton « Estimer mon projet », qui ouvre
                    l'estimateur sur ce type.
                </FieldDescription>
                <FieldError>{errors.estimator_item_id}</FieldError>
            </Field>

            {priceMode === 'manual' && (
                <fieldset className="space-y-3">
                    <legend className="text-sm font-medium">
                        Montants « à partir de » *
                    </legend>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {CURRENCIES.map((currency) => (
                            <Field key={currency.value}>
                                <FieldLabel
                                    htmlFor={`manual-${currency.value}`}
                                >
                                    {currency.label}
                                </FieldLabel>
                                <Input
                                    id={`manual-${currency.value}`}
                                    name={`manual_prices[${currency.value}]`}
                                    type="number"
                                    min={0}
                                    defaultValue={
                                        service?.manual_prices?.[
                                            currency.value
                                        ] ?? ''
                                    }
                                />
                            </Field>
                        ))}
                    </div>
                    <FieldDescription>
                        Une devise vide prend le premier montant saisi, dans sa
                        devise.
                    </FieldDescription>
                    <FieldError>{errors.manual_prices}</FieldError>
                </fieldset>
            )}

            {projects.length > 0 && (
                <div>
                    <CheckboxGroup
                        label="Projets en exemple (3 affichés)"
                        name="project_ids"
                        options={projects}
                        defaultSelectedIds={service?.project_ids ?? []}
                    />
                    <FieldDescription>
                        Leurs avis publiés s'affichent avec l'offre.
                    </FieldDescription>
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.status}>
                    <FieldLabel htmlFor="status">Statut *</FieldLabel>
                    <FormSelect
                        id="status"
                        name="status"
                        required
                        defaultValue={service?.status ?? 'draft'}
                    >
                        {PUBLICATION_STATUSES.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldDescription>
                        Publier demande le nom en anglais.
                    </FieldDescription>
                    <FieldError>{errors.status}</FieldError>
                </Field>

                <Field data-invalid={!!errors.sort_order}>
                    <FieldLabel htmlFor="sort_order">Ordre</FieldLabel>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        min={0}
                        defaultValue={service?.sort_order ?? 0}
                    />
                    <FieldError>{errors.sort_order}</FieldError>
                </Field>
            </div>
        </>
    );
}
