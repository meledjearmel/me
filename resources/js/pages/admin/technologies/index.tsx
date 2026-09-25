import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/technologies';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, Technology } from '@/types';

const CATEGORY_LABEL = {
    langages: 'Langages',
    frameworks: 'Frameworks',
    donnees: 'Données',
    qualite: 'Qualité',
    securite: 'Sécurité',
    infra: 'Infra',
    ia: 'IA',
    design: 'Design',
    cms: 'CMS',
} as const;

export default function TechnologiesIndex({
    technologies,
    filters,
}: {
    technologies: Paginated<Technology>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Technologies" />

            <div className="flex flex-col gap-6 p-4">
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
                                    {CATEGORY_LABEL[row.category]}
                                </Badge>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="category"
                                value={filters.category}
                                label="Catégorie"
                                options={Object.entries(CATEGORY_LABEL).map(
                                    ([value, label]) => ({ value, label }),
                                )}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={TechnologyController.show(row.id)}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={TechnologyController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
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
