import { Link } from '@inertiajs/react';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import { TECHNOLOGY_CATEGORIES } from '@/lib/admin-options';
import ShowPage, { Pills } from '@/components/admin/show-page';
import { index as technologiesIndex } from '@/routes/admin/technologies';
import type { Technology } from '@/types';

export default function TechnologyShow({
    technology: row,
}: {
    technology: Technology;
}) {
    return (
        <ShowPage
            title={row.name}
            description={'Technologie de la stack'}
            backHref={technologiesIndex()}
            backLabel="Technologies"
            editHref={TechnologyController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        { label: 'Nom', value: row.name },
                        {
                            label: 'Catégorie',
                            value: TECHNOLOGY_CATEGORIES.find(
                                (category) => category.value === row.category,
                            )?.label,
                        },
                        { label: 'Icône', value: row.icon },
                    ],
                },
                {
                    title: 'Projets',
                    description: 'Les projets qui utilisent cette technologie',
                    content: (
                        <Pills
                            items={(row.projects ?? []).map((project) => (
                                <Link
                                    key={project.id}
                                    href={ProjectController.show(project.id)}
                                    className="hover:underline"
                                >
                                    {project.title.fr}
                                </Link>
                            ))}
                        />
                    ),
                },
            ]}
        />
    );
}

TechnologyShow.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: technologiesIndex() },
        { title: 'Détail', href: '' },
    ],
};
