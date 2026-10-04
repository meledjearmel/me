import { Form, Head, Link } from '@inertiajs/react';
import { Copy } from 'lucide-react';
import { toast } from 'sonner';
import ReviewInvitationController from '@/actions/App/Http/Controllers/Admin/ReviewInvitationController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import FormSelect from '@/components/admin/form-select';
import { formatDate } from '@/components/admin/show-page';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { destroy, index as pageIndex } from '@/routes/admin/review-invitations';
import { show as testimonialShow } from '@/routes/admin/testimonials';
import type {
    Education,
    Experience,
    ListFilters,
    Paginated,
    Project,
    ReviewInvitation,
} from '@/types';

const STATUSES: Record<
    ReviewInvitation['status'],
    { label: string; variant: 'default' | 'secondary' | 'outline' }
> = {
    pending: { label: 'En attente', variant: 'secondary' },
    used: { label: 'Avis reçu', variant: 'default' },
    expired: { label: 'Expiré', variant: 'outline' },
};

function subjectOf(invitation: ReviewInvitation): string {
    if (invitation.project) {
        return invitation.project.title.fr;
    }

    if (invitation.experience) {
        return `${invitation.experience.role.fr} — ${invitation.experience.company}`;
    }

    if (invitation.education) {
        return `${invitation.education.degree.fr} — ${invitation.education.institution}`;
    }

    return 'Général';
}

async function copyLink(url: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(url);
        toast.success('Lien copié.');
    } catch {
        toast.error('Copie impossible, sélectionnez le lien à la main.');
    }
}

/**
 * Demandes d'avis : je crée un lien personnel, je le copie et je l'envoie ; il
 * ouvre le formulaire d'avis prérempli et ne sert qu'une fois.
 */
export default function ReviewInvitationsIndex({
    invitations,
    filters,
    projects,
    experiences,
    educations,
}: {
    invitations: Paginated<ReviewInvitation>;
    filters: ListFilters;
    projects: Pick<Project, 'id' | 'title'>[];
    experiences: Pick<Experience, 'id' | 'role' | 'company'>[];
    educations: Pick<Education, 'id' | 'degree' | 'institution'>[];
}) {
    return (
        <>
            <Head title="Demandes d’avis" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Demandes d’avis"
                    description="Un lien personnel, à usage unique, qui ouvre le formulaire d’avis prérempli"
                />

                <Form
                    {...ReviewInvitationController.store.form()}
                    resetOnSuccess
                    className="grid gap-4 rounded-lg border p-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <Field data-invalid={!!errors.name}>
                                <FieldLabel htmlFor="name">Nom</FieldLabel>
                                <Input id="name" name="name" />
                                <FieldError>{errors.name}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.email}>
                                <FieldLabel htmlFor="email">Email</FieldLabel>
                                <Input id="email" name="email" type="email" />
                                <FieldError>{errors.email}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.locale}>
                                <FieldLabel htmlFor="locale">Langue</FieldLabel>
                                <FormSelect
                                    id="locale"
                                    name="locale"
                                    defaultValue="fr"
                                >
                                    <option value="fr">Français</option>
                                    <option value="en">Anglais</option>
                                </FormSelect>
                                <FieldError>{errors.locale}</FieldError>
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

                            <Field data-invalid={!!errors.experience_id}>
                                <FieldLabel htmlFor="experience_id">
                                    Expérience liée
                                </FieldLabel>
                                <FormSelect
                                    id="experience_id"
                                    name="experience_id"
                                    defaultValue=""
                                >
                                    <option value="">Aucune</option>
                                    {experiences.map((experience) => (
                                        <option
                                            key={experience.id}
                                            value={experience.id}
                                        >
                                            {experience.role.fr} —{' '}
                                            {experience.company}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.experience_id}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.education_id}>
                                <FieldLabel htmlFor="education_id">
                                    Formation liée
                                </FieldLabel>
                                <FormSelect
                                    id="education_id"
                                    name="education_id"
                                    defaultValue=""
                                >
                                    <option value="">Aucune</option>
                                    {educations.map((education) => (
                                        <option
                                            key={education.id}
                                            value={education.id}
                                        >
                                            {education.degree.fr} —{' '}
                                            {education.institution}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.education_id}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.expires_at}>
                                <FieldLabel htmlFor="expires_at">
                                    Expire le (facultatif)
                                </FieldLabel>
                                <Input
                                    id="expires_at"
                                    name="expires_at"
                                    type="date"
                                />
                                <FieldError>{errors.expires_at}</FieldError>
                            </Field>

                            <Field
                                data-invalid={!!errors.note}
                                className="sm:col-span-2"
                            >
                                <FieldLabel htmlFor="note">
                                    Mémo (visible ici seulement)
                                </FieldLabel>
                                <Input id="note" name="note" />
                                <FieldError>{errors.note}</FieldError>
                            </Field>

                            <div className="sm:col-span-2 lg:col-span-3">
                                <Button disabled={processing}>
                                    Créer le lien
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <ResourceList
                    paginator={invitations}
                    filters={filters}
                    searchPlaceholder="Nom, email, mémo…"
                    columns={[
                        {
                            header: 'Personne',
                            cell: (row) => (
                                <div className="flex flex-col">
                                    <span>{row.name ?? '—'}</span>
                                    {row.email && (
                                        <span className="text-xs text-muted-foreground">
                                            {row.email}
                                        </span>
                                    )}
                                    {row.note && (
                                        <span className="text-xs text-muted-foreground italic">
                                            {row.note}
                                        </span>
                                    )}
                                </div>
                            ),
                        },
                        { header: 'Sujet', cell: (row) => subjectOf(row) },
                        {
                            header: 'État',
                            cell: (row) => {
                                const badge = (
                                    <Badge
                                        variant={STATUSES[row.status].variant}
                                    >
                                        {STATUSES[row.status].label}
                                    </Badge>
                                );

                                return row.testimonial_id ? (
                                    <Link
                                        href={testimonialShow(
                                            row.testimonial_id,
                                        )}
                                    >
                                        {badge}
                                    </Link>
                                ) : (
                                    badge
                                );
                            },
                        },
                        {
                            header: 'Expire',
                            cell: (row) =>
                                row.expires_at
                                    ? formatDate(row.expires_at)
                                    : 'Jamais',
                        },
                        {
                            header: 'Création',
                            cell: (row) => formatDate(row.created_at),
                        },
                    ]}
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="status"
                            value={filters.status}
                            label="État"
                            options={[
                                { value: 'pending', label: 'En attente' },
                                { value: 'used', label: 'Avis reçu' },
                                { value: 'expired', label: 'Expiré' },
                            ]}
                        />
                    )}
                    actions={(row) => (
                        <>
                            {row.status === 'pending' && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Copier le lien"
                                    title="Copier le lien"
                                    onClick={() => void copyLink(row.url)}
                                >
                                    <Copy />
                                </Button>
                            )}
                            <DeleteButton
                                href={destroy.url(row.id)}
                                confirmMessage="Supprimer ce lien ? Il ne fonctionnera plus."
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

ReviewInvitationsIndex.layout = {
    breadcrumbs: [{ title: 'Demandes d’avis', href: pageIndex() }],
};
