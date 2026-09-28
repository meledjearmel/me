import { Link } from '@inertiajs/react';
import TechnologyCategoryController from '@/actions/App/Http/Controllers/Admin/TechnologyCategoryController';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import ShowPage, { Bilingual, Pills } from '@/components/admin/show-page';
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
                            value: row.category && (
                                <Link
                                    href={TechnologyCategoryController.show(
                                        row.category.id,
                                    )}
                                    className="hover:underline"
                                >
                                    {row.category.label.fr}
                                </Link>
                            ),
                        },
                        { label: 'Icône', value: row.icon },
                        {
                            label: 'Description (infobulle)',
                            value: <Bilingual value={row.description} />,
                            wide: true,
                        },
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
