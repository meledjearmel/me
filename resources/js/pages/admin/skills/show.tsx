import { Link } from '@inertiajs/react';
import SkillController from '@/actions/App/Http/Controllers/Admin/SkillController';
import DomainController from '@/actions/App/Http/Controllers/Admin/DomainController';
import ShowPage, {
    Bilingual,
    PublicationBadge,
    Pills,
} from '@/components/admin/show-page';
import { index as skillsIndex } from '@/routes/admin/skills';
import type { Skill } from '@/types';

export default function SkillShow({ skill: row }: { skill: Skill }) {
    return (
        <ShowPage
            title={row.name.fr}
            description={
                row.domain
                    ? `Compétence · ${row.domain.label.fr}`
                    : 'Compétence'
            }
            badge={<PublicationBadge status={row.status} />}
            backHref={skillsIndex()}
            backLabel="Compétences"
            editHref={SkillController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        { label: 'Nom', value: <Bilingual value={row.name} /> },
                        {
                            label: 'Domaine',
                            value: row.domain && (
                                <Link
                                    href={DomainController.show(row.domain.id)}
                                    className="hover:underline"
                                >
                                    {row.domain.label.fr}
                                </Link>
                            ),
                        },
                        { label: 'Ordre', value: row.sort_order },
                        {
                            label: 'Description',
                            value: <Bilingual value={row.description} />,
                            wide: true,
                        },
                        {
                            label: 'Détails (fenêtre au clic)',
                            value: <Bilingual value={row.details} />,
                            wide: true,
                        },
                    ],
                },
                {
                    title: 'Technologies',
                    description: 'Logos affichés sur la carte de la compétence',
                    content: (
                        <Pills
                            items={(row.technologies ?? []).map(
                                (technology) => technology.name,
                            )}
                        />
                    ),
                },
            ]}
        />
    );
}

SkillShow.layout = {
    breadcrumbs: [
        { title: 'Compétences', href: skillsIndex() },
        { title: 'Détail', href: '' },
    ],
};
