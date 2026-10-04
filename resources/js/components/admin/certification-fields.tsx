import FormSelect from '@/components/admin/form-select';
import TranslatableField from '@/components/translatable-field';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    CERTIFICATION_KINDS,
    PUBLICATION_STATUSES,
} from '@/lib/admin-options';
import type { Certification } from '@/types';

/** Champs d'une certification, partagés par la création et la modification. */
export default function CertificationFields({
    certification,
    errors,
}: {
    certification?: Certification;
    errors: Record<string, string>;
}) {
    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.kind}>
                    <FieldLabel htmlFor="kind">Type *</FieldLabel>
                    <FormSelect
                        id="kind"
                        name="kind"
                        required
                        defaultValue={certification?.kind ?? 'certification'}
                    >
                        {CERTIFICATION_KINDS.map((kind) => (
                            <option key={kind.value} value={kind.value}>
                                {kind.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.kind}</FieldError>
                </Field>

                <Field data-invalid={!!errors.status}>
                    <FieldLabel htmlFor="status">Statut *</FieldLabel>
                    <FormSelect
                        id="status"
                        name="status"
                        required
                        defaultValue={certification?.status ?? 'published'}
                    >
                        {PUBLICATION_STATUSES.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.status}</FieldError>
                </Field>
            </div>

            <TranslatableField
                name="name"
                label="Intitulé"
                required
                maxLength={160}
                defaultValue={certification?.name}
                errors={{ fr: errors['name.fr'], en: errors['name.en'] }}
            />

            <Field data-invalid={!!errors.issuer}>
                <FieldLabel htmlFor="issuer">Organisme *</FieldLabel>
                <Input
                    id="issuer"
                    name="issuer"
                    required
                    maxLength={120}
                    placeholder="AWS, Google, OpenClassrooms…"
                    defaultValue={certification?.issuer}
                />
                <FieldError>{errors.issuer}</FieldError>
            </Field>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.issued_on}>
                    <FieldLabel htmlFor="issued_on">
                        Date d’obtention *
                    </FieldLabel>
                    <Input
                        id="issued_on"
                        name="issued_on"
                        type="date"
                        required
                        defaultValue={certification?.issued_on}
                    />
                    <FieldError>{errors.issued_on}</FieldError>
                </Field>
                <Field data-invalid={!!errors.expires_on}>
                    <FieldLabel htmlFor="expires_on">
                        Expiration (facultatif)
                    </FieldLabel>
                    <Input
                        id="expires_on"
                        name="expires_on"
                        type="date"
                        defaultValue={certification?.expires_on ?? ''}
                    />
                    <FieldError>{errors.expires_on}</FieldError>
                </Field>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.credential_id}>
                    <FieldLabel htmlFor="credential_id">
                        Identifiant (facultatif)
                    </FieldLabel>
                    <Input
                        id="credential_id"
                        name="credential_id"
                        maxLength={120}
                        defaultValue={certification?.credential_id ?? ''}
                    />
                    <FieldError>{errors.credential_id}</FieldError>
                </Field>
                <Field data-invalid={!!errors.credential_url}>
                    <FieldLabel htmlFor="credential_url">
                        Lien de vérification (facultatif)
                    </FieldLabel>
                    <Input
                        id="credential_url"
                        name="credential_url"
                        type="url"
                        defaultValue={certification?.credential_url ?? ''}
                    />
                    <FieldError>{errors.credential_url}</FieldError>
                </Field>
            </div>

            <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                <Field data-invalid={!!errors.badge}>
                    <FieldLabel htmlFor="badge">Badge (facultatif)</FieldLabel>
                    <Input
                        id="badge"
                        name="badge"
                        type="file"
                        accept="image/*"
                    />
                    <FieldError>{errors.badge}</FieldError>
                </Field>
                <Field data-invalid={!!errors.sort_order}>
                    <FieldLabel htmlFor="sort_order">Ordre</FieldLabel>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        min={0}
                        defaultValue={certification?.sort_order ?? 0}
                    />
                    <FieldError>{errors.sort_order}</FieldError>
                </Field>
            </div>
        </>
    );
}
