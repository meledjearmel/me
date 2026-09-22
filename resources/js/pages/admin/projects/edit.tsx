import { Form, Head } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import CheckboxGroup from '@/components/admin/checkbox-group';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
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

            <div className="max-w-3xl space-y-6 p-4">
                <Heading
                    title="Modifier le projet"
                    description={project.title.fr}
                />

                <Form
                    {...ProjectController.update.form(project.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
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

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug *</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    defaultValue={project.slug}
                                    required
                                />
                                <InputError message={errors.slug} />
                            </div>

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
                                <div className="grid gap-2">
                                    <Label htmlFor="repo_url">URL dépôt</Label>
                                    <Input
                                        id="repo_url"
                                        name="repo_url"
                                        defaultValue={project.repo_url ?? ''}
                                    />
                                    <InputError message={errors.repo_url} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="demo_url">URL démo</Label>
                                    <Input
                                        id="demo_url"
                                        name="demo_url"
                                        defaultValue={project.demo_url ?? ''}
                                    />
                                    <InputError message={errors.demo_url} />
                                </div>
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Statut *</Label>
                                    <NativeSelect
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
                                    </NativeSelect>
                                    <InputError message={errors.status} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="sort_order">Ordre</Label>
                                    <Input
                                        id="sort_order"
                                        name="sort_order"
                                        type="number"
                                        defaultValue={project.sort_order}
                                    />
                                    <InputError message={errors.sort_order} />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="hidden"
                                    name="is_featured"
                                    value="0"
                                />
                                <input
                                    id="is_featured"
                                    type="checkbox"
                                    name="is_featured"
                                    value="1"
                                    defaultChecked={project.is_featured}
                                    className="size-4"
                                />
                                <Label htmlFor="is_featured">
                                    Mettre en avant
                                </Label>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="cover">
                                    Image de couverture
                                </Label>
                                {project.cover_url && (
                                    <img
                                        src={project.cover_url}
                                        alt=""
                                        className="h-32 w-auto rounded-md border object-cover"
                                    />
                                )}
                                <Input
                                    id="cover"
                                    name="cover"
                                    type="file"
                                    accept="image/*"
                                />
                                <InputError message={errors.cover} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="gallery">Galerie (ajout)</Label>
                                {project.gallery_urls &&
                                    project.gallery_urls.length > 0 && (
                                        <div className="flex flex-wrap gap-2">
                                            {project.gallery_urls.map((url) => (
                                                <img
                                                    key={url}
                                                    src={url}
                                                    alt=""
                                                    className="h-20 w-auto rounded-md border object-cover"
                                                />
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
                                <InputError message={errors.gallery} />
                            </div>

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
                        </>
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
