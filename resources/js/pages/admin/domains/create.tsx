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
import { PUBLICATION_STATUSES } from '@/lib/admin-options';
import { index as domainsIndex } from '@/routes/admin/domains';

export default function DomainCreate() {
    return (
        <>
            <Head title="Nouveau domaine" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Nouveau domaine"
                    description="Ajouter un domaine de compétence"
                />

                <Form {...DomainController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input id="key" name="key" required />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
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
                                    placeholder="#6366F1"
                                    required
                                />
                                <FieldError>{errors.color}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.icon}>
                                <FieldLabel htmlFor="icon">Icône *</FieldLabel>
                                <Input id="icon" name="icon" required />
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
                                    defaultValue="published"
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
                                    defaultValue={0}
                                />
                                <FieldError>{errors.sort_order}</FieldError>
                            </Field>

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

DomainCreate.layout = {
    breadcrumbs: [
        { title: 'Domaines', href: domainsIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
