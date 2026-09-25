import { Form, Head } from '@inertiajs/react';
import SkillController from '@/actions/App/Http/Controllers/Admin/SkillController';
import CheckboxGroup from '@/components/admin/checkbox-group';
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
import FormSelect from '@/components/admin/form-select';
import { index as skillsIndex } from '@/routes/admin/skills';
import { PUBLICATION_STATUSES } from '@/lib/admin-options';
import type { Domain, Technology } from '@/types';

export default function SkillCreate({
    domains,
    technologies,
}: {
    domains: Domain[];
    technologies: Technology[];
}) {
    return (
        <>
            <Head title="Nouvelle compétence" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Nouvelle compétence"
                    description="Rattachée à un domaine"
                />

                <Form {...SkillController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.domain_id}>
                                <FieldLabel htmlFor="domain_id">
                                    Domaine *
                                </FieldLabel>
                                <FormSelect
                                    id="domain_id"
                                    name="domain_id"
                                    required
                                    defaultValue=""
                                >
                                    <option value="" disabled>
                                        Sélectionner...
                                    </option>
                                    {domains.map((domain) => (
                                        <option
                                            key={domain.id}
                                            value={domain.id}
                                        >
                                            {domain.label.fr}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.domain_id}</FieldError>
                            </Field>

                            <TranslatableField
                                name="name"
                                label="Nom"
                                required
                                errors={{
                                    fr: errors['name.fr'],
                                    en: errors['name.en'],
                                }}
                            />

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <TranslatableField
                                name="details"
                                label="Détails (fenêtre au clic)"
                                textarea
                                errors={{
                                    fr: errors['details.fr'],
                                    en: errors['details.en'],
                                }}
                            />

                            <CheckboxGroup
                                label="Technologies (logos sur la carte)"
                                name="technologies"
                                options={technologies.map((technology) => ({
                                    id: technology.id,
                                    label: technology.name,
                                }))}
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

SkillCreate.layout = {
    breadcrumbs: [
        { title: 'Compétences', href: skillsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
