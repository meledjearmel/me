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
import { index as educationsIndex } from '@/routes/admin/educations';
import { PUBLICATION_STATUSES } from '@/lib/admin-options';
import type { Education } from '@/types';

export default function EducationEdit({ education }: { education: Education }) {
    return (
        <>
            <Head title={`Modifier — ${education.degree.fr}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier la formation"
                    description={education.degree.fr}
                />

                <Form
                    {...EducationController.update.form(education.id)}
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
                                    defaultValue={education.institution}
                                    required
                                />
                                <FieldError>{errors.institution}</FieldError>
                            </Field>

                            <TranslatableField
                                name="degree"
                                label="Diplôme"
                                required
                                defaultValue={education.degree}
                                errors={{
                                    fr: errors['degree.fr'],
                                    en: errors['degree.en'],
                                }}
                            />

                            <TranslatableField
                                name="field"
                                label="Filière"
                                required
                                defaultValue={education.field}
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
                                        defaultValue={education.start_date}
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
                                        defaultValue={education.end_date ?? ''}
                                    />
                                    <FieldError>{errors.end_date}</FieldError>
                                </Field>
                            </div>

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
                                defaultValue={
                                    education.description ?? undefined
                                }
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
                                    defaultValue={education.status}
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
                                    defaultValue={education.sort_order}
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

EducationEdit.layout = {
    breadcrumbs: [
        { title: 'Formation', href: educationsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
