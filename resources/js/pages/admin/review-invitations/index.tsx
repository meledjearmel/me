import { Form, Head, Link } from '@inertiajs/react';
import { Copy, Plus, Users } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import ReviewInvitationController from '@/actions/App/Http/Controllers/Admin/ReviewInvitationController';
import ActionTooltip from '@/components/admin/action-tooltip';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import FormSelect from '@/components/admin/form-select';
import { formatDate } from '@/components/admin/show-page';
import SubjectFields from '@/components/admin/subject-fields';
import type { SubjectOptions } from '@/components/admin/subject-fields';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { destroy, index as pageIndex } from '@/routes/admin/review-invitations';
import { show as testimonialShow } from '@/routes/admin/testimonials';
import type { ListFilters, Paginated, ReviewInvitation } from '@/types';

const STATUSES: Record<
    ReviewInvitation['status'],
    { label: string; variant: 'default' | 'secondary' | 'outline' }
> = {
    pending: { label: 'En attente', variant: 'secondary' },
    used: { label: 'Avis reçu', variant: 'default' },
    expired: { label: 'Expiré', variant: 'outline' },
};

async function copyLink(url: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(url);
        toast.success('Lien copié.');
    } catch {
        toast.error('Copie impossible, sélectionnez le lien à la main.');
    }
}

/** « 3 / 10 avis », « 3 avis » (sans limite) ; rien pour un lien personnel. */
function usage(invitation: ReviewInvitation): string | null {
    if (!invitation.collective) {
        return null;
    }

    return invitation.max_uses === null
        ? `${invitation.uses_count} avis`
        : `${invitation.uses_count} / ${invitation.max_uses} avis`;
}

/** Le formulaire de création, dans une fenêtre : la liste garde toute la page. */
function CreateInvitationDialog({
    open,
    onOpenChange,
    ...options
}: SubjectOptions & {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [collective, setCollective] = useState(false);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90dvh] w-[calc(100%-1rem)] overflow-y-auto p-4 sm:max-w-2xl sm:p-6">
                <DialogHeader>
                    <DialogTitle>Nouvelle demande d’avis</DialogTitle>
                    <DialogDescription>
                        Le lien ouvre le formulaire d’avis prérempli, rattaché
                        aux projets, expériences et formations choisis.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ReviewInvitationController.store.form()}
                    resetOnSuccess
                    onSuccess={() => {
                        setCollective(false);
                        onOpenChange(false);
                    }}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <Field
                                orientation="horizontal"
                                className="sm:col-span-2"
                            >
                                <input
                                    type="hidden"
                                    name="collective"
                                    value={collective ? '1' : '0'}
                                />
                                <Checkbox
                                    id="collective"
                                    checked={collective}
                                    onCheckedChange={(checked) =>
                                        setCollective(checked === true)
                                    }
                                />
                                <div className="grid gap-1">
                                    <FieldLabel htmlFor="collective">
                                        Lien collectif
                                    </FieldLabel>
                                    <FieldDescription>
                                        À partager à toute une équipe : chacun
                                        laisse son avis (un seul par e-mail).
                                    </FieldDescription>
                                </div>
                            </Field>

                            {collective ? (
                                <Field data-invalid={!!errors.max_uses}>
                                    <FieldLabel htmlFor="max_uses">
                                        Nombre d’avis au plus
                                    </FieldLabel>
                                    <Input
                                        id="max_uses"
                                        name="max_uses"
                                        type="number"
                                        min={2}
                                        max={1000}
                                        placeholder="Sans limite"
                                    />
                                    <FieldError>{errors.max_uses}</FieldError>
                                </Field>
                            ) : (
                                <>
                                    <Field data-invalid={!!errors.name}>
                                        <FieldLabel htmlFor="name">
                                            Nom
                                        </FieldLabel>
                                        <Input id="name" name="name" />
                                        <FieldError>{errors.name}</FieldError>
                                    </Field>

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
                                </>
                            )}

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

                            <div className="grid gap-4 sm:col-span-2">
                                <SubjectFields {...options} errors={errors} />
                            </div>

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

                            <div className="flex justify-end gap-2 sm:col-span-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Annuler
                                </Button>
                                <Button disabled={processing}>
                                    Créer le lien
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Demandes d'avis : je crée un lien, je le copie et je l'envoie ; il ouvre le
 * formulaire d'avis prérempli. Personnel, il ne sert qu'une fois ; collectif,
 * il sert à toute une équipe.
 */
export default function ReviewInvitationsIndex({
    invitations,
    filters,
    ...options
}: SubjectOptions & {
    invitations: Paginated<ReviewInvitation>;
    filters: ListFilters;
}) {
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title="Demandes d’avis" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Demandes d’avis"
                        description="Un lien personnel ou collectif qui ouvre le formulaire d’avis prérempli"
                    />
                    <Button onClick={() => setCreating(true)}>
                        <Plus />
                        Nouvelle demande
                    </Button>
                </div>

                <CreateInvitationDialog
                    open={creating}
                    onOpenChange={setCreating}
                    {...options}
                />

                <ResourceList
                    paginator={invitations}
                    filters={filters}
                    searchPlaceholder="Nom, email, mémo…"
                    columns={[
                        {
                            header: 'Personne',
                            cell: (row) => (
                                <div className="flex flex-col">
                                    {row.collective ? (
                                        <span className="inline-flex items-center gap-1.5">
                                            <Users className="size-4" />
                                            Lien collectif
                                        </span>
                                    ) : (
                                        <span>{row.name ?? '—'}</span>
                                    )}
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
                        {
                            header: 'Sujet',
                            cell: (row) =>
                                row.subjects.length === 0 ? (
                                    'Général'
                                ) : (
                                    <div className="flex flex-wrap gap-1">
                                        {row.subjects.map((subject) => (
                                            <Badge
                                                key={`${subject.type}-${subject.id}`}
                                                variant="outline"
                                            >
                                                {subject.label}
                                            </Badge>
                                        ))}
                                    </div>
                                ),
                        },
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
                                const first = row.testimonial_ids[0];

                                return (
                                    <div className="flex flex-col items-start gap-1">
                                        {first && !row.collective ? (
                                            <Link href={testimonialShow(first)}>
                                                {badge}
                                            </Link>
                                        ) : (
                                            badge
                                        )}
                                        {usage(row) && (
                                            <span className="text-xs text-muted-foreground">
                                                {usage(row)}
                                            </span>
                                        )}
                                    </div>
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
                                <ActionTooltip label="Copier le lien">
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
                                </ActionTooltip>
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
