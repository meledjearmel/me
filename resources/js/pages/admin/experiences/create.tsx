import { Form, Head } from '@inertiajs/react';
import ExperienceController from '@/actions/App/Http/Controllers/Admin/ExperienceController';
import FormSelect from '@/components/admin/form-select';
import HighlightsField from '@/components/admin/highlights-field';
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
import { index as experiencesIndex } from '@/routes/admin/experiences';

export default function ExperienceCreate() {
    return (
        <>
            <Head title="Nouvelle expérience" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading title="Nouvelle expérience" />

                <Form
                    {...ExperienceController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.company}>
                                <FieldLabel htmlFor="company">
                                    Entreprise *
                                </FieldLabel>
                                <Input id="company" name="company" required />
                                <FieldError>{errors.company}</FieldError>
                            </Field>

                            <TranslatableField
                                name="role"
                                label="Rôle"
                                required
                                errors={{
                                    fr: errors['role.fr'],
                                    en: errors['role.en'],
                                }}
                            />

                            <Field data-invalid={!!errors.location}>
                                <FieldLabel htmlFor="location">Lieu</FieldLabel>
                                <Input id="location" name="location" />
                                <FieldError>{errors.location}</FieldError>
                            </Field>

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
                                        Fin (vide = poste actuel)
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

                            <HighlightsField errors={errors} />

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

ExperienceCreate.layout = {
    breadcrumbs: [
        { title: 'Expériences', href: experiencesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
