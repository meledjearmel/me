import CelebrationController from '@/actions/App/Http/Controllers/Admin/CelebrationController';
import DeleteButton from '@/components/admin/delete-button';
import ShowPage, { Bilingual, formatDate } from '@/components/admin/show-page';
import { Badge } from '@/components/ui/badge';
import { index as celebrationsIndex } from '@/routes/admin/celebrations';
import type { Celebration } from '@/types';

/** La surprise est-elle visible aujourd'hui (active et dans sa période) ? */
function isShowableToday(celebration: Celebration): boolean {
    const today = new Date().toISOString().slice(0, 10);

    return (
        celebration.is_active &&
        (!celebration.starts_at || celebration.starts_at <= today) &&
        (!celebration.ends_at || celebration.ends_at >= today)
    );
}

export default function CelebrationShow({
    celebration: row,
}: {
    celebration: Celebration;
}) {
    const showable = isShowableToday(row);

    return (
        <ShowPage
            title="Surprise"
            description={`${row.congratulations_count.toLocaleString('fr-FR')} félicitations reçues`}
            badge={
                <Badge variant={showable ? 'default' : 'secondary'}>
                    {showable
                        ? 'Affichée en ce moment'
                        : row.is_active
                          ? 'Hors période'
                          : 'Inactive'}
                </Badge>
            }
            backHref={celebrationsIndex()}
            backLabel="Surprises"
            editHref={CelebrationController.edit(row.id)}
            actions={
                <DeleteButton
                    href={CelebrationController.destroy.url(row.id)}
                    confirmMessage="Supprimer cette surprise et son compteur de félicitations ?"
                />
            }
            sections={[
                {
                    title: 'Annonce',
                    description: "Ce qu'Armi dit aux visiteurs dans sa bulle",
                    items: [
                        {
                            label: 'Message',
                            value: <Bilingual value={row.message} />,
                            wide: true,
                        },
                        {
                            label: 'Texte du bouton',
                            value: <Bilingual value={row.button_label} />,
                        },
                        {
                            label: 'Aperçu de la notification',
                            value: row.congratulated_for
                                ? `Vous avez reçu 3 félicitations pour ${row.congratulated_for}`
                                : null,
                        },
                    ],
                },
                {
                    title: "Période et fréquence d'apparition",
                    items: [
                        { label: 'Active', value: row.is_active ? 'Oui' : 'Non' },
                        {
                            label: 'Période',
                            value:
                                row.starts_at || row.ends_at
                                    ? `${formatDate(row.starts_at) ?? 'Dès maintenant'} — ${formatDate(row.ends_at) ?? 'sans fin'}`
                                    : 'Toujours',
                        },
                        {
                            label: "Chance d'apparition",
                            value: `${row.chance_percent} % des visites (une fois par session au plus)`,
                        },
                        {
                            label: 'Délai avant apparition',
                            value: `${row.delay_seconds} s`,
                        },
                        {
                            label: "Durée d'affichage",
                            value: `${row.display_seconds} s sans interaction`,
                        },
                        {
                            label: 'Poids au tirage',
                            value: row.weight,
                        },
                    ],
                },
                {
                    title: 'Félicitations',
                    items: [
                        {
                            label: 'Reçues',
                            value: row.congratulations_count.toLocaleString('fr-FR'),
                        },
                        {
                            label: 'Créée le',
                            value: formatDate(row.created_at),
                        },
                    ],
                },
            ]}
        />
    );
}

CelebrationShow.layout = {
    breadcrumbs: [
        { title: 'Surprises', href: celebrationsIndex() },
        { title: 'Détail', href: '' },
    ],
};
