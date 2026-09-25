import { Form, Head } from '@inertiajs/react';
import EducationController from '@/actions/App/Http/Controllers/Admin/EducationController';
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
import { index as educationsIndex } from '@/routes/admin/educations';

export default function EducationCreate() {
    return (
        <>
            <Head title="Nouvelle formation" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading title="Nouvelle formation" />

                <Form
                    {...EducationController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.institution}>
                                <FieldLabel htmlFor="institution">
                                    Établissement *
                                </FieldLabel>
                                <Input
                                    id="institution"
                                    name="institution"
                                    required
                                />
                                <FieldError>{errors.institution}</FieldError>
                            </Field>

                            <TranslatableField
                                name="degree"
                                label="Diplôme"
                                required
                                errors={{
                                    fr: errors['degree.fr'],
                                    en: errors['degree.en'],
                                }}
                            />

                            <TranslatableField
                                name="field"
                                label="Filière"
                                required
                                errors={{
                                    fr: errors['field.fr'],
                                    en: errors['field.en'],
                                }}
                            />

                            <div className="grid gap-2 sm:grid-cols-2">
                                <Field data-invalid={!!errors.start_date}>
                                    <FieldLabel htmlFor="start_date">
                                        Début *
                                    </FieldLabel>
                                    <Input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        required
                                    />
                                    <FieldError>{errors.start_date}</FieldError>
                                </Field>
                                <Field data-invalid={!!errors.end_date}>
                                    <FieldLabel htmlFor="end_date">
                                        Fin
                                    </FieldLabel>
                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                    />
                                    <FieldError>{errors.end_date}</FieldError>
                                </Field>
                            </div>

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

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

EducationCreate.layout = {
    breadcrumbs: [
        { title: 'Formation', href: educationsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
