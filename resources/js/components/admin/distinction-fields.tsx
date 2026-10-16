import { useState } from 'react';
import FormSelect from '@/components/admin/form-select';
import TranslatableField from '@/components/translatable-field';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PUBLICATION_STATUSES } from '@/lib/admin-options';
import type {
    Distinction,
    DistinctionContextOptions,
    DistinctionContextType,
} from '@/types';

const CONTEXT_TYPES: { value: DistinctionContextType; label: string }[] = [
    { value: 'experience', label: 'Une expérience' },
    { value: 'education', label: 'Une formation' },
    { value: 'project', label: 'Un projet' },
];

/** Champs d'une distinction, partagés par la création et la modification. */
export default function DistinctionFields({
    distinction,
    contexts,
    errors,
}: {
    distinction?: Distinction;
    contexts: DistinctionContextOptions;
    errors: Record<string, string>;
}) {
    const [contextType, setContextType] = useState<
        DistinctionContextType | ''
    >(distinction?.context_type ?? '');
    const options = contextType === '' ? [] : contexts[contextType];

    return (
        <>
            <TranslatableField
                name="title"
                label="Intitulé"
                required
                maxLength={160}
                defaultValue={distinction?.title}
                errors={{ fr: errors['title.fr'], en: errors['title.en'] }}
            />

            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.issuer}>
                    <FieldLabel htmlFor="issuer">Organisme *</FieldLabel>
                    <Input
                        id="issuer"
                        name="issuer"
                        required
                        maxLength={120}
                        placeholder="CIAPOL, GITEX, ESATIC…"
                        defaultValue={distinction?.issuer}
                    />
                    <FieldError>{errors.issuer}</FieldError>
                </Field>
                <Field data-invalid={!!errors.awarded_on}>
                    <FieldLabel htmlFor="awarded_on">
                        Date d’obtention *
                    </FieldLabel>
                    <Input
                        id="awarded_on"
                        name="awarded_on"
                        type="date"
                        required
                        defaultValue={distinction?.awarded_on}
                    />
                    <FieldError>{errors.awarded_on}</FieldError>
                </Field>
            </div>

            <TranslatableField
                name="description"
                label="Contexte (facultatif)"
                textarea
                maxLength={500}
                defaultValue={distinction?.description ?? undefined}
                errors={{
                    fr: errors['description.fr'],
                    en: errors['description.en'],
                }}
            />

            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.context_type}>
                    <FieldLabel htmlFor="context_type">
                        Obtenue dans le cadre de
                    </FieldLabel>
                    <FormSelect
                        id="context_type"
                        name="context_type"
                        defaultValue={contextType}
                        onValueChange={(value) =>
                            setContextType(value as DistinctionContextType | '')
                        }
                    >
                        <option value="">Aucun contexte</option>
                        {CONTEXT_TYPES.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.context_type}</FieldError>
                </Field>

                <Field data-invalid={!!errors.context_id}>
                    <FieldLabel htmlFor="context_id">Lequel ?</FieldLabel>
                    {/* La clé remonte la liste quand le type change : aucun choix ne reste d'un autre type. */}
                    <FormSelect
                        key={contextType}
                        id="context_id"
                        name="context_id"
                        disabled={contextType === ''}
                        required={contextType !== ''}
                        defaultValue={
                            contextType === distinction?.context_type
                                ? String(distinction?.context_id ?? '')
                                : ''
                        }
                    >
                        <option value="" disabled>
                            {contextType === '' ? '—' : 'Choisir…'}
                        </option>
                        {options.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.context_id}</FieldError>
                </Field>
            </div>

            <Field data-invalid={!!errors.url}>
                <FieldLabel htmlFor="url">
                    Lien vers une preuve (facultatif)
                </FieldLabel>
                <Input
                    id="url"
                    name="url"
                    type="url"
                    placeholder="Article, publication LinkedIn…"
                    defaultValue={distinction?.url ?? ''}
                />
                <FieldError>{errors.url}</FieldError>
            </Field>

            <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                <Field data-invalid={!!errors.status}>
                    <FieldLabel htmlFor="status">Statut *</FieldLabel>
                    <FormSelect
                        id="status"
                        name="status"
                        required
                        defaultValue={distinction?.status ?? 'published'}
                    >
                        {PUBLICATION_STATUSES.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.status}</FieldError>
                </Field>
                <Field data-invalid={!!errors.sort_order}>
                    <FieldLabel htmlFor="sort_order">Ordre</FieldLabel>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        min={0}
                        defaultValue={distinction?.sort_order ?? 0}
                    />
                    <FieldError>{errors.sort_order}</FieldError>
                </Field>
            </div>
        </>
    );
}
