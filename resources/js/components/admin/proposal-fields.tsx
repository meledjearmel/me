import FormSelect from '@/components/admin/form-select';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { Proposal } from '@/types';

export const PROPOSAL_PLATFORMS = [
    { value: 'upwork', label: 'Upwork' },
    { value: 'malt', label: 'Malt' },
    { value: 'fiverr', label: 'Fiverr' },
    { value: 'freelancer', label: 'Freelancer' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'other', label: 'Autre' },
] as const;

export const PROPOSAL_STATUSES = [
    { value: 'draft', label: 'Brouillon' },
    { value: 'sent', label: 'Envoyée' },
    { value: 'viewed', label: 'Vue' },
    { value: 'interview', label: 'Entretien' },
    { value: 'hired', label: 'Mission obtenue' },
    { value: 'declined', label: 'Refusée' },
] as const;

/** Champs communs aux formulaires de création et de modification d'une proposition. */
export default function ProposalFields({
    proposal,
    errors,
}: {
    proposal?: Proposal;
    errors: Record<string, string | undefined>;
}) {
    return (
        <>
            <div className="grid gap-2 sm:grid-cols-3">
                <Field data-invalid={!!errors.platform}>
                    <FieldLabel htmlFor="platform">Plateforme *</FieldLabel>
                    <FormSelect
                        id="platform"
                        name="platform"
                        defaultValue={proposal?.platform ?? 'upwork'}
                        required
                    >
                        {PROPOSAL_PLATFORMS.map((platform) => (
                            <option key={platform.value} value={platform.value}>
                                {platform.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.platform}</FieldError>
                </Field>

                <Field data-invalid={!!errors.locale}>
                    <FieldLabel htmlFor="locale">Langue *</FieldLabel>
                    <FormSelect
                        id="locale"
                        name="locale"
                        defaultValue={proposal?.locale ?? 'fr'}
                        required
                    >
                        <option value="fr">Français</option>
                        <option value="en">Anglais</option>
                    </FormSelect>
                    <FieldError>{errors.locale}</FieldError>
                </Field>

                <Field data-invalid={!!errors.status}>
                    <FieldLabel htmlFor="status">Statut *</FieldLabel>
                    <FormSelect
                        id="status"
                        name="status"
                        defaultValue={proposal?.status ?? 'draft'}
                        required
                    >
                        {PROPOSAL_STATUSES.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </FormSelect>
                    <FieldError>{errors.status}</FieldError>
                </Field>
            </div>

            <div className="grid gap-2 sm:grid-cols-2">
                <Field data-invalid={!!errors.job_title}>
                    <FieldLabel htmlFor="job_title">
                        Titre de l’offre *
                    </FieldLabel>
                    <Input
                        id="job_title"
                        name="job_title"
                        maxLength={255}
                        defaultValue={proposal?.job_title ?? ''}
                        required
                    />
                    <FieldError>{errors.job_title}</FieldError>
                </Field>

                <Field data-invalid={!!errors.client_name}>
                    <FieldLabel htmlFor="client_name">Client</FieldLabel>
                    <Input
                        id="client_name"
                        name="client_name"
                        maxLength={255}
                        defaultValue={proposal?.client_name ?? ''}
                    />
                    <FieldDescription>
                        Son prénom se trouve souvent dans les avis de son
                        historique (« Thanks John! »).
                    </FieldDescription>
                    <FieldError>{errors.client_name}</FieldError>
                </Field>
            </div>

            <Field data-invalid={!!errors.job_url}>
                <FieldLabel htmlFor="job_url">Lien de l’offre</FieldLabel>
                <Input
                    id="job_url"
                    name="job_url"
                    type="url"
                    defaultValue={proposal?.job_url ?? ''}
                />
                <FieldError>{errors.job_url}</FieldError>
            </Field>

            <Field data-invalid={!!errors.job_posting}>
                <FieldLabel htmlFor="job_posting">Annonce</FieldLabel>
                <Textarea
                    id="job_posting"
                    name="job_posting"
                    rows={6}
                    defaultValue={proposal?.job_posting ?? ''}
                />
                <FieldError>{errors.job_posting}</FieldError>
            </Field>

            <Field data-invalid={!!errors.body}>
                <FieldLabel htmlFor="body">Proposition *</FieldLabel>
                <Textarea
                    id="body"
                    name="body"
                    rows={12}
                    defaultValue={proposal?.body ?? ''}
                    required
                />
                <FieldDescription>
                    Collez la version réellement envoyée : c’est elle qui sert
                    d’exemple à l’IA.
                </FieldDescription>
                <FieldError>{errors.body}</FieldError>
            </Field>

            <Field data-invalid={!!errors.screening_answers}>
                <FieldLabel htmlFor="screening_answers">
                    Réponses aux questions
                </FieldLabel>
                <Textarea
                    id="screening_answers"
                    name="screening_answers"
                    rows={5}
                    defaultValue={proposal?.screening_answers ?? ''}
                />
                <FieldError>{errors.screening_answers}</FieldError>
            </Field>

            <div className="grid gap-2 sm:grid-cols-2">
                <Field data-invalid={!!errors.bid}>
                    <FieldLabel htmlFor="bid">Tarif proposé</FieldLabel>
                    <Input
                        id="bid"
                        name="bid"
                        maxLength={120}
                        placeholder="40 $/h, 1 200 € au forfait…"
                        defaultValue={proposal?.bid ?? ''}
                    />
                    <FieldError>{errors.bid}</FieldError>
                </Field>

                <Field data-invalid={!!errors.connects}>
                    <FieldLabel htmlFor="connects">Connects</FieldLabel>
                    <Input
                        id="connects"
                        name="connects"
                        type="number"
                        min={0}
                        defaultValue={proposal?.connects ?? ''}
                    />
                    <FieldError>{errors.connects}</FieldError>
                </Field>
            </div>

            <Field data-invalid={!!errors.notes}>
                <FieldLabel htmlFor="notes">Notes privées</FieldLabel>
                <Textarea
                    id="notes"
                    name="notes"
                    rows={3}
                    defaultValue={proposal?.notes ?? ''}
                />
                <FieldError>{errors.notes}</FieldError>
            </Field>
        </>
    );
}
