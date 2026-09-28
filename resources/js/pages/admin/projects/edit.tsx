import { Form, Head, router } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import CheckboxGroup from '@/components/admin/checkbox-group';
import FormPageHeader from '@/components/admin/form-page-header';
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
    project: Project;
    domains: Domain[];
    jobProfiles: JobProfile[];
    technologies: Technology[];
    projects: Project[];
    relatedProjectIds: number[];
};

export default function ProjectEdit({
    project,
    domains,
    jobProfiles,
    technologies,
    projects,
    relatedProjectIds,
}: PageProps) {
    return (
        <>
            <Head title={`Modifier — ${project.title.fr}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier le projet"
                    description={project.title.fr}
                    backHref={projectsIndex()}
                    backLabel="Projets"
                />

                <Form
                    {...ProjectController.update.form(project.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <TranslatableField
                                name="title"
                                label="Titre"
                                required
                                defaultValue={project.title}
                                errors={{
                                    fr: errors['title.fr'],
                                    en: errors['title.en'],
                                }}
                            />

                            <Field data-invalid={!!errors.slug}>
                                <FieldLabel htmlFor="slug">Slug *</FieldLabel>
                                <Input
                                    id="slug"
                                    name="slug"
                                    defaultValue={project.slug}
                                    required
                                />
                                <FieldError>{errors.slug}</FieldError>
                            </Field>

                            <TranslatableField
                                name="context"
                                label="Contexte"
                                textarea
                                required
                                defaultValue={project.context}
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
                                defaultValue={project.realization}
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
                                defaultValue={project.result}
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
                                    <Input
                                        id="repo_url"
                                        name="repo_url"
                                        defaultValue={project.repo_url ?? ''}
                                    />
                                    <FieldError>{errors.repo_url}</FieldError>
                                </Field>
                                <Field data-invalid={!!errors.demo_url}>
                                    <FieldLabel htmlFor="demo_url">
                                        URL démo
                                    </FieldLabel>
                                    <Input
                                        id="demo_url"
                                        name="demo_url"
                                        defaultValue={project.demo_url ?? ''}
                                    />
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
                                        defaultValue={project.status}
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
                                        defaultValue={project.sort_order}
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
                                    defaultValue={
                                        project.accent_color ?? '#3456c8'
                                    }
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
                                    defaultChecked={project.is_featured}
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
                                    defaultChecked={project.is_open_source}
                                />
                                <FieldLabel htmlFor="is_open_source">
                                    Projet open source (badge sur la carte)
                                </FieldLabel>
                            </Field>

                            <Field data-invalid={!!errors.cover}>
                                <FieldLabel htmlFor="cover">
                                    Image de couverture
                                </FieldLabel>
                                {project.cover_url && (
                                    <div className="flex items-center gap-2">
                                        <img
                                            src={project.cover_url}
                                            alt=""
                                            className="h-32 w-auto rounded-md border object-cover"
                                        />
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => {
                                                if (
                                                    confirm(
                                                        'Retirer la couverture de ce projet ?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        ProjectController.destroyCover.url(
                                                            project.id,
                                                        ),
                                                    );
                                                }
                                            }}
                                        >
                                            Retirer
                                        </Button>
                                    </div>
                                )}
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
                                    Galerie (ajout)
                                </FieldLabel>
                                {project.gallery &&
                                    project.gallery.length > 0 && (
                                        <div className="flex flex-wrap gap-2">
                                            {project.gallery.map((image) => (
                                                <div
                                                    key={image.id}
                                                    className="relative"
                                                >
                                                    <img
                                                        src={image.url}
                                                        alt=""
                                                        className="h-20 w-auto rounded-md border object-cover"
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        size="icon"
                                                        className="absolute -top-2 -right-2 size-6"
                                                        aria-label="Retirer cette image"
                                                        onClick={() => {
                                                            if (
                                                                confirm(
                                                                    'Retirer cette image de la galerie ?',
                                                                )
                                                            ) {
                                                                router.delete(
                                                                    ProjectController.destroyGalleryImage.url(
                                                                        [
                                                                            project.id,
                                                                            image.id,
                                                                        ],
                                                                    ),
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        ×
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    )}
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
                                defaultSelectedIds={(project.domains ?? []).map(
                                    (domain) => domain.id,
                                )}
                            />

                            <CheckboxGroup
                                label="Profils métier"
                                name="job_profiles"
                                options={jobProfiles.map((jobProfile) => ({
                                    id: jobProfile.id,
                                    label: jobProfile.label.fr,
                                }))}
                                defaultSelectedIds={(
                                    project.job_profiles ?? []
                                ).map((jobProfile) => jobProfile.id)}
                            />

                            <CheckboxGroup
                                label="Technologies"
                                name="technologies"
                                options={technologies.map((technology) => ({
                                    id: technology.id,
                                    label: technology.name,
                                }))}
                                defaultSelectedIds={(
                                    project.technologies ?? []
                                ).map((technology) => technology.id)}
                            />

                            <CheckboxGroup
                                label="Projets liés"
                                name="related_projects"
                                options={projects
                                    .filter((p) => p.id !== project.id)
                                    .map((p) => ({
                                        id: p.id,
                                        label: p.title.fr,
                                    }))}
                                defaultSelectedIds={relatedProjectIds}
                            />

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

ProjectEdit.layout = {
    breadcrumbs: [
        { title: 'Projets', href: projectsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
