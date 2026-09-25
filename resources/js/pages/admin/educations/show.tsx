import EducationController from '@/actions/App/Http/Controllers/Admin/EducationController';
import ShowPage, {
    Bilingual,
    PublicationBadge,
    formatPeriod,
} from '@/components/admin/show-page';
import { index as educationsIndex } from '@/routes/admin/educations';
import type { Education } from '@/types';

export default function EducationShow({
    education: row,
}: {
    education: Education;
}) {
    return (
        <ShowPage
            title={row.degree.fr}
            description={row.institution}
            badge={<PublicationBadge status={row.status} />}
            backHref={educationsIndex()}
            backLabel="Formation"
            editHref={EducationController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        { label: 'Établissement', value: row.institution },
                        {
                            label: 'Période',
                            value: formatPeriod(row.start_date, row.end_date),
                        },
                        {
                            label: 'Diplôme',
                            value: <Bilingual value={row.degree} />,
                        },
                        {
                            label: 'Domaine',
                            value: <Bilingual value={row.field} />,
                        },
                        { label: 'Ordre', value: row.sort_order },
                        {
                            label: 'Description',
                            value: <Bilingual value={row.description} />,
                            wide: true,
                        },
                    ],
                },
            ]}
        />
    );
}

EducationShow.layout = {
    breadcrumbs: [
        { title: 'Formation', href: educationsIndex() },
        { title: 'Détail', href: '' },
    ],
};
