import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ShowPage, { formatDate } from '@/components/admin/show-page';
import { index as engagementsIndex, update } from '@/routes/admin/engagements';
import type { Engagement } from '@/types';

export default function EngagementShow({
    engagement: row,
}: {
    engagement: Engagement;
}) {
    return (
        <ShowPage
            title={row.name}
            description={[row.company, row.email].filter(Boolean).join(' · ')}
            badge={
                <Badge
                    variant={row.type === 'hiring' ? 'default' : 'secondary'}
                >
                    {row.type === 'hiring' ? 'Embauche' : 'Freelance'}
                </Badge>
            }
            backHref={engagementsIndex()}
            backLabel="Collaborations"
            actions={
                <Button
                    variant="outline"
                    onClick={() =>
                        router.patch(
                            update.url(row.id),
                            {},
                            { preserveScroll: true },
                        )
                    }
                >
                    {row.status === 'new'
                        ? 'Marquer comme traité'
                        : 'Remettre en nouveau'}
                </Button>
            }
            sections={[
                {
                    title: 'Demande',
                    items: [
                        { label: 'Poste / projet', value: row.subject },
                        {
                            label: 'Statut',
                            value: row.status === 'new' ? 'Nouveau' : 'Traité',
                        },
                        {
                            label: 'Profil demandé',
                            value: row.job_profile?.label.fr,
                        },
                        { label: 'Contrat', value: row.contract },
                        { label: 'Budget', value: row.budget_label },
                        { label: 'Délai', value: row.timeline },
                        {
                            label: 'Langue',
                            value: row.locale === 'fr' ? 'Français' : 'Anglais',
                        },
                        {
                            label: 'Reçue le',
                            value: formatDate(row.created_at),
                        },
                        {
                            label: 'CV envoyé',
                            value:
                                row.type === 'hiring'
                                    ? row.cv_sent_at
                                        ? `Oui, le ${formatDate(row.cv_sent_at)}`
                                        : 'Non'
                                    : null,
                        },
                        { label: 'Message', value: row.message, wide: true },
                    ],
                },
                {
                    title: 'Contact',
                    items: [
                        { label: 'Nom', value: row.name },
                        {
                            label: 'Email',
                            value: (
                                <a
                                    href={`mailto:${row.email}`}
                                    className="underline"
                                >
                                    {row.email}
                                </a>
                            ),
                        },
                        { label: 'Société', value: row.company },
                    ],
                },
            ]}
        />
    );
}

EngagementShow.layout = {
    breadcrumbs: [
        { title: 'Collaborations', href: engagementsIndex() },
        { title: 'Détail', href: '' },
    ],
};
