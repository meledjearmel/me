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
                        <>
                            <TranslatableField
                                name="title"
                                label="Titre"
                                required
                                errors={{
                                    fr: errors['title.fr'],
                                    en: errors['title.en'],
                                }}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug *</Label>
                                <Input id="slug" name="slug" required />
                                <InputError message={errors.slug} />
                            </div>

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
                                <div className="grid gap-2">
                                    <Label htmlFor="repo_url">URL dépôt</Label>
                                    <Input id="repo_url" name="repo_url" />
                                    <InputError message={errors.repo_url} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="demo_url">URL démo</Label>
                                    <Input id="demo_url" name="demo_url" />
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
                                    </NativeSelect>
                                    <InputError message={errors.status} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="sort_order">Ordre</Label>
                                    <Input
                                        id="sort_order"
                                        name="sort_order"
                                        type="number"
                                        defaultValue={0}
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
                                <Input
                                    id="cover"
                                    name="cover"
                                    type="file"
                                    accept="image/*"
                                />
                                <InputError message={errors.cover} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="gallery">Galerie</Label>
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
                        </>
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
