import { ArrowDown, ArrowUp, Loader2, Sparkles, X } from 'lucide-react';
import { useState } from 'react';
import AiAssistController from '@/actions/App/Http/Controllers/Admin/AiAssistController';
import FormSelect from '@/components/admin/form-select';
import MentionTextarea from '@/components/admin/mention-textarea';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { post } from '@/hooks/use-ai-text-assist';
import type { JobApplication } from '@/types';

type Option = { id: number; label: string };

type Draft = {
    company: string;
    contact_name: string | null;
    position: string;
    intro: string;
    project_ids: number[];
    job_profile_id: number | null;
    reasoning: string | null;
};

export const JOB_APPLICATION_STATUSES = [
    { value: 'draft', label: 'Brouillon (lien inactif)' },
    { value: 'active', label: 'En ligne' },
    { value: 'archived', label: 'Archivée (lien coupé)' },
] as const;

/**
 * Champs d'une page de candidature, avec « Préparer avec l'IA » : d'après l'offre collée,
 * l'IA remplit l'entreprise, le poste, le mot d'accompagnement, le profil et les projets.
 * Les projets s'affichent dans l'ordre choisi ici (6 au plus).
 */
export default function JobApplicationFields({
    application,
    jobProfiles,
    projects,
    errors,
}: {
    application?: JobApplication;
    jobProfiles: Option[];
    projects: Option[];
    errors: Record<string, string | undefined>;
}) {
    const [locale, setLocale] = useState<'fr' | 'en'>(
        application?.locale ?? 'fr',
    );
    const [jobPosting, setJobPosting] = useState(
        application?.job_posting_text ?? '',
    );
    const [selected, setSelected] = useState<number[]>(
        application?.project_ids ?? [],
    );
    const [pending, setPending] = useState(false);
    const [aiError, setAiError] = useState<string | null>(null);
    const [draft, setDraft] = useState<Draft | null>(null);
    // Une proposition de l'IA change les valeurs par défaut : on recrée les champs pour l'afficher.
    const [version, setVersion] = useState(0);

    async function prepare() {
        setPending(true);
        setAiError(null);

        try {
            const response = await post<{ application: Draft }>(
                AiAssistController.draftJobApplication.url(),
                { job_posting: jobPosting, locale },
            );

            setDraft(response.application);
            setSelected(response.application.project_ids);
            setVersion((current) => current + 1);
        } catch {
            setAiError('Préparation indisponible pour le moment, réessayez.');
        } finally {
            setPending(false);
        }
    }

    const toggle = (id: number) =>
        setSelected((current) =>
            current.includes(id)
                ? current.filter((candidate) => candidate !== id)
                : current.length < 6
                  ? [...current, id]
                  : current,
        );

    const move = (index: number, offset: number) =>
        setSelected((current) => {
            const next = [...current];
            const target = index + offset;

            if (target < 0 || target >= next.length) {
                return current;
            }

            [next[index], next[target]] = [next[target], next[index]];

            return next;
        });

    const label = (id: number) =>
        projects.find((project) => project.id === id)?.label ?? `#${id}`;

    return (
        <>
            <Field>
                <FieldLabel htmlFor="locale">Langue de la page *</FieldLabel>
                <FormSelect
                    id="locale"
                    name="locale"
                    required
                    defaultValue={locale}
                    onValueChange={(value) => setLocale(value as 'fr' | 'en')}
                >
                    <option value="fr">Français</option>
                    <option value="en">Anglais</option>
                </FormSelect>
                <FieldDescription>
                    Celle de l'offre : la page et le CV proposé sont dans cette
                    langue.
                </FieldDescription>
            </Field>

            <div className="space-y-3 rounded-lg border border-dashed p-4">
                <p className="flex items-center gap-2 text-sm font-medium">
                    <Sparkles className="size-4" /> Préparer avec l'IA
                </p>
                <Textarea
                    name="job_posting_text"
                    value={jobPosting}
                    onChange={(event) => setJobPosting(event.target.value)}
                    rows={6}
                    placeholder="Collez ici le texte de l'offre d'emploi."
                    aria-label="Texte de l'offre d'emploi"
                />
                <div className="flex flex-wrap items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => void prepare()}
                        disabled={pending || jobPosting.trim().length < 50}
                    >
                        {pending ? (
                            <Loader2 className="animate-spin" />
                        ) : (
                            <Sparkles />
                        )}
                        Préparer la page d'après l'offre
                    </Button>
                    {aiError && (
                        <span className="text-sm text-destructive">
                            {aiError}
                        </span>
                    )}
                </div>
                {draft?.reasoning && (
                    <p className="text-sm text-muted-foreground">
                        <strong>Choix de l'IA :</strong> {draft.reasoning}{' '}
                        Relisez le mot avant de mettre la page en ligne.
                    </p>
                )}
                <FieldError>{errors.job_posting_text}</FieldError>
            </div>

            <div key={version} className="contents">
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field data-invalid={!!errors.company}>
                        <FieldLabel htmlFor="company">Entreprise *</FieldLabel>
                        <Input
                            id="company"
                            name="company"
                            required
                            defaultValue={
                                draft?.company ?? application?.company ?? ''
                            }
                        />
                        <FieldError>{errors.company}</FieldError>
                    </Field>
                    <Field data-invalid={!!errors.contact_name}>
                        <FieldLabel htmlFor="contact_name">
                            Recruteur
                        </FieldLabel>
                        <Input
                            id="contact_name"
                            name="contact_name"
                            defaultValue={
                                draft?.contact_name ??
                                application?.contact_name ??
                                ''
                            }
                        />
                        <FieldDescription>
                            Pour « Bonjour Claire, » en tête de page.
                        </FieldDescription>
                        <FieldError>{errors.contact_name}</FieldError>
                    </Field>
                </div>

                <Field data-invalid={!!errors.position}>
                    <FieldLabel htmlFor="position">Poste *</FieldLabel>
                    <Input
                        id="position"
                        name="position"
                        required
                        defaultValue={
                            draft?.position ?? application?.position ?? ''
                        }
                    />
                    <FieldError>{errors.position}</FieldError>
                </Field>

                <Field data-invalid={!!errors.intro}>
                    <FieldLabel htmlFor="intro">
                        Mot d'accompagnement *
                    </FieldLabel>
                    <MentionTextarea
                        id="intro"
                        name="intro"
                        rows={8}
                        required
                        defaultValue={draft?.intro ?? application?.intro ?? ''}
                    />
                    <FieldDescription>
                        Paragraphes séparés par une ligne vide. Tapez « @ » pour
                        citer un projet : il devient un lien avec sa carte sur
                        la page.
                    </FieldDescription>
                    <FieldError>{errors.intro}</FieldError>
                </Field>

                <Field data-invalid={!!errors.job_profile_id}>
                    <FieldLabel htmlFor="job_profile_id">
                        CV proposé (profil métier)
                    </FieldLabel>
                    <FormSelect
                        id="job_profile_id"
                        name="job_profile_id"
                        defaultValue={
                            draft?.job_profile_id ??
                            application?.job_profile_id ??
                            ''
                        }
                    >
                        <option value="">Aucun CV</option>
                        {jobProfiles.map((profile) => (
                            <option key={profile.id} value={profile.id}>
                                {profile.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.job_profile_id}</FieldError>
                </Field>
            </div>

            <fieldset className="space-y-3">
                <legend className="text-sm font-medium">
                    Projets montrés, dans l'ordre (6 au plus)
                </legend>
                {selected.map((id) => (
                    <input
                        key={id}
                        type="hidden"
                        name="project_ids[]"
                        value={id}
                    />
                ))}
                {selected.length > 0 && (
                    <ol className="space-y-1">
                        {selected.map((id, index) => (
                            <li
                                key={id}
                                className="flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm"
                            >
                                <span className="w-5 text-muted-foreground">
                                    {index + 1}.
                                </span>
                                <span className="flex-1">{label(id)}</span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Monter"
                                    onClick={() => move(index, -1)}
                                    disabled={index === 0}
                                >
                                    <ArrowUp />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Descendre"
                                    onClick={() => move(index, 1)}
                                    disabled={index === selected.length - 1}
                                >
                                    <ArrowDown />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Retirer"
                                    onClick={() => toggle(id)}
                                >
                                    <X />
                                </Button>
                            </li>
                        ))}
                    </ol>
                )}
                <div className="flex flex-wrap gap-2">
                    {projects
                        .filter((project) => !selected.includes(project.id))
                        .map((project) => (
                            <Button
                                key={project.id}
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => toggle(project.id)}
                                disabled={selected.length >= 6}
                            >
                                + {project.label}
                            </Button>
                        ))}
                </div>
                <FieldError>
                    {errors.project_ids ?? errors['project_ids.0']}
                </FieldError>
            </fieldset>

            <Field data-invalid={!!errors.job_posting_url}>
                <FieldLabel htmlFor="job_posting_url">
                    Lien de l'offre
                </FieldLabel>
                <Input
                    id="job_posting_url"
                    name="job_posting_url"
                    type="url"
                    defaultValue={application?.job_posting_url ?? ''}
                />
                <FieldError>{errors.job_posting_url}</FieldError>
            </Field>

            <Field data-invalid={!!errors.notes}>
                <FieldLabel htmlFor="notes">Notes privées</FieldLabel>
                <Textarea
                    id="notes"
                    name="notes"
                    rows={3}
                    defaultValue={application?.notes ?? ''}
                />
                <FieldDescription>
                    Jamais affichées sur la page.
                </FieldDescription>
                <FieldError>{errors.notes}</FieldError>
            </Field>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field data-invalid={!!errors.status}>
                    <FieldLabel htmlFor="status">Statut *</FieldLabel>
                    <FormSelect
                        id="status"
                        name="status"
                        required
                        defaultValue={application?.status ?? 'draft'}
                    >
                        {JOB_APPLICATION_STATUSES.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.status}</FieldError>
                </Field>
                <Field data-invalid={!!errors.expires_on}>
                    <FieldLabel htmlFor="expires_on">
                        Lien valable jusqu'au
                    </FieldLabel>
                    <Input
                        id="expires_on"
                        name="expires_on"
                        type="date"
                        defaultValue={application?.expires_on ?? ''}
                    />
                    <FieldDescription>Vide : sans limite.</FieldDescription>
                    <FieldError>{errors.expires_on}</FieldError>
                </Field>
            </div>
        </>
    );
}
