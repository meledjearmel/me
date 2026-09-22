import { Form, Head } from '@inertiajs/react';
import ProfessionalReferenceController from '@/actions/App/Http/Controllers/Admin/ProfessionalReferenceController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { REFERENCE_VISIBLE_FIELDS } from '@/lib/admin-options';
import { index as referencesIndex } from '@/routes/admin/professional-references';
import type { ProfessionalReference, Project } from '@/types';

export default function ProfessionalReferenceEdit({
    professionalReference,
    projects,
}: {
    professionalReference: ProfessionalReference;
    projects: Project[];
}) {
    return (
        <>
            <Head title={`Modifier — ${professionalReference.name}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier la référence"
                    description={professionalReference.name}
                />

                <Form
                    {...ProfessionalReferenceController.update.form(
                        professionalReference.id,
                    )}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={professionalReference.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="role">Rôle</Label>
                                    <Input
                                        id="role"
                                        name="role"
                                        defaultValue={
                                            professionalReference.role ?? ''
                                        }
                                    />
                                    <InputError message={errors.role} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="company">Société</Label>
                                    <Input
                                        id="company"
                                        name="company"
                                        defaultValue={
                                            professionalReference.company ?? ''
                                        }
                                    />
                                    <InputError message={errors.company} />
                                </div>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={
                                            professionalReference.email ?? ''
                                        }
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Téléphone</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        defaultValue={
                                            professionalReference.phone ?? ''
                                        }
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="relationship">Relation</Label>
                                <Input
                                    id="relationship"
                                    name="relationship"
                                    defaultValue={
                                        professionalReference.relationship ?? ''
                                    }
                                />
                                <InputError message={errors.relationship} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="project_id">Projet lié</Label>
                                <NativeSelect
                                    id="project_id"
                                    name="project_id"
                                    defaultValue={
                                        professionalReference.project_id ?? ''
                                    }
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
                                </NativeSelect>
                                <InputError message={errors.project_id} />
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="hidden"
                                    name="is_public"
                                    value="0"
                                />
                                <input
                                    id="is_public"
                                    type="checkbox"
                                    name="is_public"
                                    value="1"
                                    defaultChecked={
                                        professionalReference.is_public
                                    }
                                    className="size-4"
                                />
                                <Label htmlFor="is_public">
                                    Visible publiquement
                                </Label>
                            </div>

                            <div className="grid gap-2">
                                <Label>Champs visibles</Label>
                                <div className="grid grid-cols-2 gap-2">
                                    {REFERENCE_VISIBLE_FIELDS.map((field) => (
                                        <label
                                            key={field.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                type="checkbox"
                                                name="visible_fields[]"
                                                value={field.value}
                                                defaultChecked={professionalReference.visible_fields.includes(
                                                    field.value,
                                                )}
                                                className="size-4"
                                            />
                                            {field.label}
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.visible_fields} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">
                                    Notes (privées, jamais publiques)
                                </Label>
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={
                                        professionalReference.notes ?? ''
                                    }
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ProfessionalReferenceEdit.layout = {
    breadcrumbs: [
        { title: 'Références professionnelles', href: referencesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
