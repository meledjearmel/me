import { Form, Head } from '@inertiajs/react';
import DomainController from '@/actions/App/Http/Controllers/Admin/DomainController';
import FormSelect from '@/components/admin/form-select';
import Heading from '@/components/heading';
import TranslatableField from '@/components/translatable-field';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as domainsIndex } from '@/routes/admin/domains';
import { PUBLICATION_STATUSES } from '@/lib/admin-options';
import type { Domain } from '@/types';

export default function DomainEdit({ domain }: { domain: Domain }) {
    return (
        <>
            <Head title={`Modifier — ${domain.label.fr}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier le domaine"
                    description={domain.label.fr}
                />

                <Form
                    {...DomainController.update.form(domain.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input
                                    id="key"
                                    name="key"
                                    defaultValue={domain.key}
                                    required
                                />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
                                defaultValue={domain.label}
                                errors={{
                                    fr: errors['label.fr'],
                                    en: errors['label.en'],
                                }}
                            />

                            <Field data-invalid={!!errors.color}>
                                <FieldLabel htmlFor="color">
                                    Couleur (hex) *
                                </FieldLabel>
                                <Input
                                    id="color"
                                    name="color"
                                    defaultValue={domain.color}
                                    required
                                />
                                <FieldError>{errors.color}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.icon}>
                                <FieldLabel htmlFor="icon">Icône *</FieldLabel>
                                <Input
                                    id="icon"
                                    name="icon"
                                    defaultValue={domain.icon}
                                    required
                                />
                                <FieldError>{errors.icon}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.status}>
                                <FieldLabel htmlFor="status">
                                    Statut *
                                </FieldLabel>
                                <FormSelect
                                    id="status"
                                    name="status"
                                    required
                                    defaultValue={domain.status}
                                >
                                    {PUBLICATION_STATUSES.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.status}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.sort_order}>
                                <FieldLabel htmlFor="sort_order">
                                    Ordre
                                </FieldLabel>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={domain.sort_order}
                                />
                                <FieldError>{errors.sort_order}</FieldError>
                            </Field>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

DomainEdit.layout = {
    breadcrumbs: [
        { title: 'Domaines', href: domainsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
