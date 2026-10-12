import { Head, Link } from '@inertiajs/react';
import { Eye, Plus, SquarePen } from 'lucide-react';
import TechnologyCategoryController from '@/actions/App/Http/Controllers/Admin/TechnologyCategoryController';
import DeleteButton from '@/components/admin/delete-button';
import { ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/technology-categories';
import type { ListFilters, Paginated, TechnologyCategory } from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

export default function TechnologyCategoriesIndex({
    categories,
    filters,
}: {
    categories: Paginated<TechnologyCategory>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Catégories de technologies" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Catégories de technologies"
                        description="Regroupent la stack technique par type"
                    />
                    <Button asChild>
                        <Link href={TechnologyCategoryController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle catégorie
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={categories}
                    filters={filters}
                    searchPlaceholder="Clé ou libellé…"
                    columns={[
                        {
                            header: 'Clé',
                            className: 'font-mono text-xs',
                            cell: (row) => row.key,
                        },
                        { header: 'Libellé (FR)', cell: (row) => row.label.fr },
                        { header: 'Libellé (EN)', cell: (row) => row.label.en },
                        {
                            header: 'Technologies',
                            cell: (row) => row.technologies_count ?? 0,
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                    ]}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={TechnologyCategoryController.show(
                                            row.id,
                                        )}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={TechnologyCategoryController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={TechnologyCategoryController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer la catégorie "${row.label.fr}" et ses technologies ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

TechnologyCategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Catégories de technologies', href: pageIndex() }],
};
