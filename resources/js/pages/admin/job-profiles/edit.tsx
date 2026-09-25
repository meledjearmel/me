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
import { index as jobProfilesIndex } from '@/routes/admin/job-profiles';
import { PUBLICATION_STATUSES } from '@/lib/admin-options';
import type { JobProfile } from '@/types';

export default function JobProfileEdit({
    jobProfile,
}: {
    jobProfile: JobProfile;
}) {
    return (
        <>
            <Head title={`Modifier — ${jobProfile.label.fr}`} />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    title="Modifier le profil métier"
                    description={jobProfile.label.fr}
                />

                <Form
                    {...JobProfileController.update.form(jobProfile.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input
                                    id="key"
                                    name="key"
                                    defaultValue={jobProfile.key}
                                    required
                                />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Titre long (libellé)"
                                required
                                defaultValue={jobProfile.label}
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
                                defaultValue={jobProfile.description}
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <TranslatableField
                                name="hero_title"
                                label="Titre court du hero (15 max)"
                                maxLength={15}
                                defaultValue={
                                    jobProfile.hero_title ?? undefined
                                }
                                errors={{
                                    fr: errors['hero_title.fr'],
                                    en: errors['hero_title.en'],
                                }}
                            />

                            <TranslatableField
                                name="hero_words"
                                label="Mots du hero (3, séparés par des virgules)"
                                defaultValue={
                                    jobProfile.hero_words ?? undefined
                                }
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
                                defaultValue={jobProfile.cv_description}
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
                                    defaultValue={jobProfile.status}
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
                                    defaultValue={jobProfile.sort_order}
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

JobProfileEdit.layout = {
    breadcrumbs: [
        { title: 'Profils métier', href: jobProfilesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
