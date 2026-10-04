import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import UsesItemController from '@/actions/App/Http/Controllers/Admin/UsesItemController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { USES_CATEGORIES } from '@/lib/admin-options';
import { index as pageIndex } from '@/routes/admin/uses-items';
import type { ListFilters, Paginated, UsesItem } from '@/types';

const categoryLabel = (value: string) =>
    USES_CATEGORIES.find((category) => category.value === value)?.label ??
    value;

export default function UsesItemsIndex({
    items,
    filters,
}: {
    items: Paginated<UsesItem>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Uses" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Uses"
                        description="Le matériel et les outils de la page publique « Uses » (masquée tant qu’elle est vide)"
                    />
                    <Button asChild>
                        <Link href={UsesItemController.create()}>
                            <Plus data-icon="inline-start" /> Nouvel élément
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={items}
                    filters={filters}
                    searchPlaceholder="Nom ou description…"
                    columns={[
                        {
                            header: 'Rubrique',
                            cell: (row) => categoryLabel(row.category),
                        },
                        { header: 'Nom', cell: (row) => row.name },
                        {
                            header: 'Description (FR)',
                            cell: (row) => row.description?.fr ?? '—',
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                    ]}
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="category"
                            value={filters.category}
                            label="Rubrique"
                            options={[...USES_CATEGORIES]}
                        />
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={UsesItemController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={UsesItemController.destroy.url(row.id)}
                                confirmMessage={`Supprimer « ${row.name} » ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

UsesItemsIndex.layout = {
    breadcrumbs: [{ title: 'Uses', href: pageIndex() }],
};
