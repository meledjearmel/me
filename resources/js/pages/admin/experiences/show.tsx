import ExperienceController from '@/actions/App/Http/Controllers/Admin/ExperienceController';
import ShowPage, {
    Bilingual,
    PublicationBadge,
    formatPeriod,
} from '@/components/admin/show-page';
import { index as experiencesIndex } from '@/routes/admin/experiences';
import type { Experience } from '@/types';

export default function ExperienceShow({
    experience: row,
}: {
    experience: Experience;
}) {
    return (
        <ShowPage
            title={row.role.fr}
            description={row.company}
            badge={<PublicationBadge status={row.status} />}
            backHref={experiencesIndex()}
            backLabel="Expériences"
            editHref={ExperienceController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        { label: 'Entreprise', value: row.company },
                        { label: 'Lieu', value: row.location },
                        {
                            label: 'Période',
                            value: formatPeriod(row.start_date, row.end_date),
                        },
                        { label: 'Ordre', value: row.sort_order },
                        {
                            label: 'Rôle',
                            value: <Bilingual value={row.role} />,
                            wide: true,
                        },
                        {
                            label: 'Description',
                            value: <Bilingual value={row.description} />,
                            wide: true,
                        },
                    ],
                },
                {
                    title: 'Réalisations marquantes',
                    content: (
                        <ul className="flex flex-col gap-2 text-sm">
                            {(row.highlights ?? []).map((highlight) => (
                                <li
                                    key={highlight.id}
                                    className="rounded-md border p-3"
                                >
                                    <Bilingual value={highlight.text} />
                                </li>
                            ))}
                            {(row.highlights ?? []).length === 0 && (
                                <li className="text-muted-foreground">
                                    Aucune puce.
                                </li>
                            )}
                        </ul>
                    ),
                },
            ]}
        />
    );
}

ExperienceShow.layout = {
    breadcrumbs: [
        { title: 'Expériences', href: experiencesIndex() },
        { title: 'Détail', href: '' },
    ],
};
