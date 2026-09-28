import { Link } from '@inertiajs/react';
import TechnologyCategoryController from '@/actions/App/Http/Controllers/Admin/TechnologyCategoryController';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
import ShowPage, { Bilingual, Pills } from '@/components/admin/show-page';
import { index as categoriesIndex } from '@/routes/admin/technology-categories';
import type { TechnologyCategory } from '@/types';

export default function TechnologyCategoryShow({
    category: row,
}: {
    category: TechnologyCategory;
}) {
    return (
        <ShowPage
            title={row.label.fr}
            description={`Catégorie de technologies · ${row.key}`}
            backHref={categoriesIndex()}
            backLabel="Catégories de technologies"
            editHref={TechnologyCategoryController.edit(row.id)}
            sections={[
                {
                    title: 'Détails',
                    items: [
                        { label: 'Clé', value: row.key },
                        {
                            label: 'Libellé',
                            value: <Bilingual value={row.label} />,
                        },
                        { label: 'Ordre', value: row.sort_order },
                        {
                            label: 'Technologies rattachées',
                            value: row.technologies_count,
                        },
                    ],
                },
                {
                    title: 'Technologies',
                    description:
                        'Les technologies rangées dans cette catégorie',
                    content: (
                        <Pills
                            items={(row.technologies ?? []).map(
                                (technology) => (
                                    <Link
                                        key={technology.id}
                                        href={TechnologyController.show(
                                            technology.id,
                                        )}
                                        className="hover:underline"
                                    >
                                        {technology.name}
                                    </Link>
                                ),
                            )}
                        />
                    ),
                },
            ]}
        />
    );
}

TechnologyCategoryShow.layout = {
    breadcrumbs: [
        { title: 'Catégories de technologies', href: categoriesIndex() },
        { title: 'Détail', href: '' },
    ],
};
