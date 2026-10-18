import { Form, Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import CheckboxGroup from '@/components/admin/checkbox-group';
import ProjectCaseStudyFields, {
    ProjectDecisionsField,
} from '@/components/admin/project-case-study-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import {
    ProjectAiDraftButton,
    type ProjectDraft,
    TranslateFormButton,
} from '@/components/admin/project-ai-draft';
import ProjectGitHubField from '@/components/admin/project-github-field';
import type { GitHubRepositoryOption } from '@/components/admin/project-github-field';
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

/** Les champs français proposés par l'IA, au format des valeurs par défaut du formulaire. */
function draftToProject(draft: ProjectDraft): Partial<Project> {
    const french = (text: string | null) =>
        text === null ? null : { fr: text, en: '' };

    return {
        tagline: french(draft.tagline),
        role: french(draft.role),
        client: french(draft.client),
        platform: french(draft.platform),
        started_on: draft.started_on,
        ended_on: draft.ended_on,
        team_size: draft.team_size,
        key_figures: draft.key_figures.map((figure) => ({
            value: figure.value,
            label: { fr: figure.label, en: '' },
        })),
        decisions: draft.decisions.map((decision) => ({
            choice: { fr: decision.choice, en: '' },
            reason: { fr: decision.reason, en: '' },
        })),
    };
}

type PageProps = {
    domains: Domain[];
    jobProfiles: JobProfile[];
    technologies: Technology[];
    projects: Project[];
    githubRepositories: GitHubRepositoryOption[];
};

export default function ProjectCreate({
    domains,
    jobProfiles,
    technologies,
    projects,
    githubRepositories,
}: PageProps) {
    const [draft, setDraft] = useState<ProjectDraft | null>(null);
    // Le formulaire n'est pas contrôlé : on le remonte pour appliquer les valeurs proposées.
    const [formKey, setFormKey] = useState(0);
    const fieldsRef = useRef<HTMLDivElement>(null);
    const french = (text: string | null | undefined) =>
        text ? { fr: text } : undefined;

    function applyDraft(proposed: ProjectDraft) {
        setDraft(proposed);
        setFormKey((key) => key + 1);
    }

    return (
        <>
            <Head title="Nouveau projet" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouveau projet"
                    backHref={projectsIndex()}
                    backLabel="Projets"
                />

                <div className="flex flex-wrap items-center gap-3">
                    <ProjectAiDraftButton
                        repositories={githubRepositories}
                        onDraft={applyDraft}
                    />
                    {draft && <TranslateFormButton formRef={fieldsRef} />}
                </div>

                {draft && (
                    <p className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                        Champs pré-remplis par l'IA en français : relisez-les
                        (rien n'est inventé en principe, mais vérifiez dates,
                        client et chiffres), traduisez en anglais, puis créez le
                        projet.
                    </p>
                )}

                <Form
                    key={formKey}
                    {...ProjectController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <div ref={fieldsRef}>
                            <FieldGroup>
                                <TranslatableField
                                    name="title"
                                    label="Titre"
                                    required
                                    defaultValue={french(draft?.title)}
                                    errors={{
                                        fr: errors['title.fr'],
                                        en: errors['title.en'],
                                    }}
                                />

                                <Field data-invalid={!!errors.slug}>
                                    <FieldLabel htmlFor="slug">
                                        Slug *
                                    </FieldLabel>
                                    <Input
                                        id="slug"
                                        name="slug"
                                        defaultValue={draft?.slug}
                                        required
                                    />
                                    <FieldError>{errors.slug}</FieldError>
                                </Field>

                                <ProjectCaseStudyFields
                                    project={
                                        draft
                                            ? draftToProject(draft)
                                            : undefined
                                    }
                                    errors={errors}
                                />

                                <TranslatableField
                                    name="context"
                                    label="Contexte"
                                    textarea
                                    mentions
                                    required
                                    defaultValue={french(draft?.context)}
                                    errors={{
                                        fr: errors['context.fr'],
                                        en: errors['context.en'],
                                    }}
                                />

                                <TranslatableField
                                    name="challenges"
                                    label="Défis et contraintes (facultatif)"
                                    textarea
                                    mentions
                                    defaultValue={french(draft?.challenges)}
                                    errors={{
                                        fr: errors['challenges.fr'],
                                        en: errors['challenges.en'],
                                    }}
                                />

                                <TranslatableField
                                    name="realization"
                                    label="Réalisation"
                                    textarea
                                    mentions
                                    required
                                    defaultValue={french(draft?.realization)}
                                    errors={{
                                        fr: errors['realization.fr'],
                                        en: errors['realization.en'],
                                    }}
                                />

                                <ProjectDecisionsField
                                    project={
                                        draft
                                            ? draftToProject(draft)
                                            : undefined
                                    }
                                    errors={errors}
                                />

                                <TranslatableField
                                    name="result"
                                    label="Résultat"
                                    textarea
                                    mentions
                                    required
                                    defaultValue={french(draft?.result)}
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
                                            defaultValue={draft?.repo_url}
                                        />
                                        <FieldError>
                                            {errors.repo_url}
                                        </FieldError>
                                    </Field>
                                    <Field data-invalid={!!errors.demo_url}>
                                        <FieldLabel htmlFor="demo_url">
                                            URL démo
                                        </FieldLabel>
                                        <Input
                                            id="demo_url"
                                            name="demo_url"
                                            defaultValue={
                                                draft?.demo_url ?? undefined
                                            }
                                        />
                                        <FieldError>
                                            {errors.demo_url}
                                        </FieldError>
                                    </Field>
                                </div>

                                <ProjectGitHubField
                                    repositories={githubRepositories}
                                    defaultValue={draft?.github_repository}
                                    error={errors.github_repository}
                                />

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
                                        <FieldError>
                                            {errors.sort_order}
                                        </FieldError>
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
                                    <FieldError>
                                        {errors.accent_color}
                                    </FieldError>
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
                                        defaultChecked={draft?.is_open_source}
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
                                    defaultSelectedIds={draft?.domains}
                                    options={domains.map((domain) => ({
                                        id: domain.id,
                                        label: domain.label.fr,
                                    }))}
                                />

                                <CheckboxGroup
                                    label="Profils métier"
                                    name="job_profiles"
                                    defaultSelectedIds={draft?.job_profiles}
                                    options={jobProfiles.map((jobProfile) => ({
                                        id: jobProfile.id,
                                        label: jobProfile.label.fr,
                                    }))}
                                />

                                <CheckboxGroup
                                    label="Technologies"
                                    name="technologies"
                                    defaultSelectedIds={draft?.technologies}
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
                        </div>
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
