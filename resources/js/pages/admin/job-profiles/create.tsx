import { Form, Head } from '@inertiajs/react';
import JobProfileController from '@/actions/App/Http/Controllers/Admin/JobProfileController';
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
import { index as jobProfilesIndex } from '@/routes/admin/job-profiles';

export default function JobProfileCreate() {
    return (
        <>
            <Head title="Nouveau profil métier" />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    title="Nouveau profil métier"
                    description="Ex. Ingénieur full-stack, Chargé IT, Lead Tech..."
                />

                <Form
                    {...JobProfileController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input id="key" name="key" required />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Titre long (libellé)"
                                required
                                errors={{
                                    fr: errors['label.fr'],
                                    en: errors['label.en'],
                                }}
                            />

                            <TranslatableField
                                name="description"
                                label="Description (publique)"
                                textarea
                                required
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <TranslatableField
                                name="hero_title"
                                label="Titre court du hero (15 max)"
                                maxLength={15}
                                errors={{
                                    fr: errors['hero_title.fr'],
                                    en: errors['hero_title.en'],
                                }}
                            />

                            <TranslatableField
                                name="hero_words"
                                label="Mots du hero (3, séparés par des virgules)"
                                errors={{
                                    fr: errors['hero_words.fr'],
                                    en: errors['hero_words.en'],
                                }}
                            />

                            <TranslatableField
                                name="cv_description"
                                label="Description CV (jamais publique)"
                                textarea
                                required
                                errors={{
                                    fr: errors['cv_description.fr'],
                                    en: errors['cv_description.en'],
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

JobProfileCreate.layout = {
    breadcrumbs: [
        { title: 'Profils métier', href: jobProfilesIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
