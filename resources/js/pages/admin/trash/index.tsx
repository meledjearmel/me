import { Head } from '@inertiajs/react';
import TrashController from '@/actions/App/Http/Controllers/Admin/TrashController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import RestoreButton from '@/components/admin/restore-button';
import { formatDate } from '@/components/admin/show-page';
import Heading from '@/components/heading';
import { index as pageIndex } from '@/routes/admin/trash';
import type { ListFilters, Paginated, TrashItem } from '@/types';

export default function TrashIndex({
    items,
    filters,
    types,
}: {
    items: Paginated<TrashItem>;
    filters: ListFilters;
    types: { value: string; label: string }[];
}) {
    return (
        <>
            <Head title="Corbeille" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Corbeille"
                        description="Éléments supprimés, restaurables ou à effacer définitivement"
                    />
                </div>

                <ResourceList
                    paginator={items}
                    filters={filters}
                    searchPlaceholder="Rechercher…"
                    columns={[
                        { header: 'Type', cell: (row) => row.label },
                        { header: 'Élément', cell: (row) => row.title },
                        {
                            header: 'Supprimé le',
                            cell: (row) => formatDate(row.deleted_at),
                        },
                    ]}
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="type"
                            value={filters.type}
                            label="Type"
                            options={types}
                        />
                    )}
                    actions={(row) => (
                        <>
                            <RestoreButton
                                href={TrashController.restore.url([
                                    row.type,
                                    row.id,
                                ])}
                            />
                            <DeleteButton
                                href={TrashController.forceDelete.url([
                                    row.type,
                                    row.id,
                                ])}
                                confirmMessage={`Supprimer définitivement "${row.title}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

TrashIndex.layout = {
    breadcrumbs: [{ title: 'Corbeille', href: pageIndex() }],
};
