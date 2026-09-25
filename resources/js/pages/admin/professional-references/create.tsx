import { Form, Head } from '@inertiajs/react';
import ProfessionalReferenceController from '@/actions/App/Http/Controllers/Admin/ProfessionalReferenceController';
import Heading from '@/components/heading';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import FormSelect from '@/components/admin/form-select';
import { Textarea } from '@/components/ui/textarea';
import { REFERENCE_VISIBLE_FIELDS } from '@/lib/admin-options';
import { index as referencesIndex } from '@/routes/admin/professional-references';
import type { Project } from '@/types';

export default function ProfessionalReferenceCreate({
    projects,
}: {
    projects: Project[];
}) {
    return (
        <>
            <Head title="Nouvelle référence" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading title="Nouvelle référence professionnelle" />

                <Form
                    {...ProfessionalReferenceController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.name}>
                                <FieldLabel htmlFor="name">Nom *</FieldLabel>
                                <Input id="name" name="name" required />
                                <FieldError>{errors.name}</FieldError>
                            </Field>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <Field data-invalid={!!errors.role}>
                                    <FieldLabel htmlFor="role">Rôle</FieldLabel>
                                    <Input id="role" name="role" />
                                    <FieldError>{errors.role}</FieldError>
                                </Field>
                                <Field data-invalid={!!errors.company}>
                                    <FieldLabel htmlFor="company">
                                        Société
                                    </FieldLabel>
                                    <Input id="company" name="company" />
                                    <FieldError>{errors.company}</FieldError>
                                </Field>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <Field data-invalid={!!errors.email}>
                                    <FieldLabel htmlFor="email">
                                        Email
                                    </FieldLabel>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                    />
                                    <FieldError>{errors.email}</FieldError>
                                </Field>
                                <Field data-invalid={!!errors.phone}>
                                    <FieldLabel htmlFor="phone">
                                        Téléphone
                                    </FieldLabel>
                                    <Input id="phone" name="phone" />
                                    <FieldError>{errors.phone}</FieldError>
                                </Field>
                            </div>

                            <Field data-invalid={!!errors.relationship}>
                                <FieldLabel htmlFor="relationship">
                                    Relation
                                </FieldLabel>
                                <Input id="relationship" name="relationship" />
                                <FieldError>{errors.relationship}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.project_id}>
                                <FieldLabel htmlFor="project_id">
                                    Projet lié
                                </FieldLabel>
                                <FormSelect
                                    id="project_id"
                                    name="project_id"
                                    defaultValue=""
                                >
                                    <option value="">Aucun</option>
                                    {projects.map((project) => (
                                        <option
                                            key={project.id}
                                            value={project.id}
                                        >
                                            {project.title.fr}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.project_id}</FieldError>
                            </Field>

                            <Field orientation="horizontal">
                                <input
                                    type="hidden"
                                    name="is_public"
                                    value="0"
                                />
                                <Checkbox
                                    id="is_public"
                                    name="is_public"
                                    value="1"
                                />
                                <FieldLabel htmlFor="is_public">
                                    Inclure dans le CV envoyé
                                </FieldLabel>
                            </Field>

                            <Field data-invalid={!!errors.visible_fields}>
                                <FieldLabel>
                                    Champs affichés sur le CV
                                </FieldLabel>
                                <div className="grid grid-cols-2 gap-2">
                                    {REFERENCE_VISIBLE_FIELDS.map((field) => (
                                        <Field
                                            key={field.value}
                                            orientation="horizontal"
                                        >
                                            <Checkbox
                                                id={`visible_fields-${field.value}`}
                                                name="visible_fields[]"
                                                value={field.value}
                                            />
                                            <FieldLabel
                                                htmlFor={`visible_fields-${field.value}`}
                                                className="font-normal"
                                            >
                                                {field.label}
                                            </FieldLabel>
                                        </Field>
                                    ))}
                                </div>
                                <FieldError>{errors.visible_fields}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.notes}>
                                <FieldLabel htmlFor="notes">
                                    Notes (privées, jamais sur le CV)
                                </FieldLabel>
                                <Textarea id="notes" name="notes" />
                                <FieldError>{errors.notes}</FieldError>
                            </Field>

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

ProfessionalReferenceCreate.layout = {
    breadcrumbs: [
        { title: 'Références professionnelles', href: referencesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
