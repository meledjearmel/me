import { Link } from '@inertiajs/react';
import DomainController from '@/actions/App/Http/Controllers/Admin/DomainController';
import SkillController from '@/actions/App/Http/Controllers/Admin/SkillController';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import ShowPage, {
    Bilingual,
    PublicationBadge,
    Pills,
} from '@/components/admin/show-page';
import { index as domainsIndex } from '@/routes/admin/domains';
import type { Domain } from '@/types';

export default function DomainShow({ domain: row }: { domain: Domain }) {
    return (
        <ShowPage
            title={row.label.fr}
            description={`Domaine · ${row.key}`}
            badge={<PublicationBadge status={row.status} />}
            backHref={domainsIndex()}
            backLabel="Domaines"
            editHref={DomainController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        { label: 'Clé', value: row.key },
                        {
                            label: 'Libellé',
                            value: <Bilingual value={row.label} />,
                        },
                        {
                            label: 'Couleur',
                            value: (
                                <span className="inline-flex items-center gap-2">
                                    <span
                                        className="size-3 rounded-full border"
                                        style={{ backgroundColor: row.color }}
                                    />
                                    {row.color}
                                </span>
                            ),
                        },
                        { label: 'Icône', value: row.icon },
                        { label: 'Ordre', value: row.sort_order },
                        {
                            label: 'Projets rattachés',
                            value: row.projects_count,
                        },
                    ],
                },
                {
                    title: 'Compétences',
                    description: 'Les compétences rangées dans ce domaine',
                    content: (
                        <Pills
                            items={(row.skills ?? []).map((skill) => (
                                <Link
                                    key={skill.id}
                                    href={SkillController.show(skill.id)}
                                    className="hover:underline"
                                >
                                    {skill.name.fr}
                                </Link>
                            ))}
                        />
                    ),
                },
            ]}
        />
    );
}

DomainShow.layout = {
    breadcrumbs: [
        { title: 'Domaines', href: domainsIndex() },
        { title: 'Détail', href: '' },
    ],
};
