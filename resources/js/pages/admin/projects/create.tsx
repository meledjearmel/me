import { Form, Head } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import CheckboxGroup from '@/components/admin/checkbox-group';
import Heading from '@/components/heading';
import TranslatableField from '@/components/translatable-field';
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
import { PROJECT_STATUSES } from '@/lib/admin-options';
import { index as projectsIndex } from '@/routes/admin/projects';
import type { Domain, JobProfile, Project, Technology } from '@/types';

type PageProps = {
    domains: Domain[];
    jobProfiles: JobProfile[];
    technologies: Technology[];
    projects: Project[];
};

export default function ProjectCreate({
    domains,
    jobProfiles,
    technologies,
    projects,
}: PageProps) {
    return (
        <>
            <Head title="Nouveau projet" />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading title="Nouveau projet" />

                <Form {...ProjectController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <TranslatableField
                                name="title"
                                label="Titre"
                                required
                                errors={{
                                    fr: errors['title.fr'],
                                    en: errors['title.en'],
                                }}
                            />

                            <Field data-invalid={!!errors.slug}>
                                <FieldLabel htmlFor="slug">Slug *</FieldLabel>
                                <Input id="slug" name="slug" required />
                                <FieldError>{errors.slug}</FieldError>
                            </Field>

                            <TranslatableField
                                name="context"
                                label="Contexte"
                                textarea
                                required
                                errors={{
                                    fr: errors['context.fr'],
                                    en: errors['context.en'],
                                }}
                            />

                            <TranslatableField
                                name="realization"
                                label="Réalisation"
                                textarea
                                required
                                errors={{
                                    fr: errors['realization.fr'],
                                    en: errors['realization.en'],
                                }}
                            />

                            <TranslatableField
                                name="result"
                                label="Résultat"
                                textarea
                                required
                                errors={{
                                    fr: errors['result.fr'],
                                    en: errors['result.en'],
                                }}
                            />

                            <div className="grid gap-2 sm:grid-cols-2">
                                <Field data-invalid={!!errors.repo_url}>
                                    <FieldLabel htmlFor="repo_url">
                                        URL dépôt
                                    </FieldLabel>
                                    <Input id="repo_url" name="repo_url" />
                                    <FieldError>{errors.repo_url}</FieldError>
                                </Field>
                                <Field data-invalid={!!errors.demo_url}>
                                    <FieldLabel htmlFor="demo_url">
                                        URL démo
                                    </FieldLabel>
                                    <Input id="demo_url" name="demo_url" />
                                    <FieldError>{errors.demo_url}</FieldError>
                                </Field>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
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
                                        {PROJECT_STATUSES.map((status) => (
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
                            </div>

                            <Field data-invalid={!!errors.accent_color}>
                                <FieldLabel htmlFor="accent_color">
                                    Couleur d'accent (cartes de l'accueil)
                                </FieldLabel>
                                <input
                                    id="accent_color"
                                    name="accent_color"
                                    type="color"
                                    defaultValue="#3456c8"
                                    className="h-10 w-20 cursor-pointer rounded-md border"
                                />
                                <FieldError>{errors.accent_color}</FieldError>
                            </Field>

                            <Field orientation="horizontal">
                                <input
                                    type="hidden"
                                    name="is_featured"
                                    value="0"
                                />
                                <Checkbox
                                    id="is_featured"
                                    name="is_featured"
                                    value="1"
                                />
                                <FieldLabel htmlFor="is_featured">
                                    Mettre en avant
                                </FieldLabel>
                            </Field>

                            <Field orientation="horizontal">
                                <input
                                    type="hidden"
                                    name="is_open_source"
                                    value="0"
                                />
                                <Checkbox
                                    id="is_open_source"
                                    name="is_open_source"
                                    value="1"
                                />
                                <FieldLabel htmlFor="is_open_source">
                                    Projet open source (badge sur la carte)
                                </FieldLabel>
                            </Field>

                            <Field data-invalid={!!errors.cover}>
                                <FieldLabel htmlFor="cover">
                                    Image de couverture
                                </FieldLabel>
                                <Input
                                    id="cover"
                                    name="cover"
                                    type="file"
                                    accept="image/*"
                                />
                                <FieldError>{errors.cover}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.gallery}>
                                <FieldLabel htmlFor="gallery">
                                    Galerie
                                </FieldLabel>
                                <Input
                                    id="gallery"
                                    name="gallery"
                                    type="file"
                                    accept="image/*"
                                    multiple
                                />
                                <FieldError>{errors.gallery}</FieldError>
                            </Field>

                            <CheckboxGroup
                                label="Domaines"
                                name="domains"
                                options={domains.map((domain) => ({
                                    id: domain.id,
                                    label: domain.label.fr,
                                }))}
                            />

                            <CheckboxGroup
                                label="Profils métier"
                                name="job_profiles"
                                options={jobProfiles.map((jobProfile) => ({
                                    id: jobProfile.id,
                                    label: jobProfile.label.fr,
                                }))}
                            />

                            <CheckboxGroup
                                label="Technologies"
                                name="technologies"
                                options={technologies.map((technology) => ({
                                    id: technology.id,
                                    label: technology.name,
                                }))}
                            />

                            <CheckboxGroup
                                label="Projets liés"
                                name="related_projects"
                                options={projects.map((project) => ({
                                    id: project.id,
                                    label: project.title.fr,
                                }))}
                            />

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

ProjectCreate.layout = {
    breadcrumbs: [
        { title: 'Projets', href: projectsIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
