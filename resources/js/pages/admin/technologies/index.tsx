import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/technologies';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    ListFilters,
    Paginated,
    Technology,
    TechnologyCategory,
} from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

export default function TechnologiesIndex({
    technologies,
    filters,
    categories,
}: {
    technologies: Paginated<Technology>;
    filters: ListFilters;
    categories: Pick<TechnologyCategory, 'id' | 'label'>[];
}) {
    return (
        <>
            <Head title="Technologies" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Technologies"
                        description="Stack technique affichée sur les projets et le CV"
                    />
                    <Button asChild>
                        <Link href={TechnologyController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                            technologie
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={technologies}
                    filters={filters}
                    searchPlaceholder="Nom de la technologie…"
                    columns={[
                        { header: 'Nom', cell: (row) => row.name },
                        {
                            header: 'Catégorie',
                            cell: (row) => (
                                <Badge variant="secondary">
                                    {row.category?.label.fr}
                                </Badge>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="category_id"
                                value={filters.category_id}
                                label="Catégorie"
                                options={categories.map((category) => ({
                                    value: String(category.id),
                                    label: category.label.fr,
                                }))}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={TechnologyController.show(row.id)}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={TechnologyController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={TechnologyController.destroy.url(row.id)}
                                confirmMessage={`Supprimer la technologie "${row.name}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

TechnologiesIndex.layout = {
    breadcrumbs: [{ title: 'Technologies', href: pageIndex() }],
};
