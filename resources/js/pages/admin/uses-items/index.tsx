import { Head, Link } from '@inertiajs/react';
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from '@/components/admin/status-menu';
import { Plus, SquarePen } from 'lucide-react';
import UsesItemController from '@/actions/App/Http/Controllers/Admin/UsesItemController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import AiDraftBadge from '@/components/admin/ai-draft-badge';
import { Button } from '@/components/ui/button';
import { PUBLICATION_STATUSES, USES_CATEGORIES } from '@/lib/admin-options';
import { index as pageIndex } from '@/routes/admin/uses-items';
import type { ListFilters, Paginated, UsesItem } from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

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

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Uses"
                        description="Le matériel et les outils de la page publique « Uses » (seuls les éléments publiés s’affichent ; sans aucun, la page est masquée)"
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
                        {
                            header: 'Statut',
                            fit: true,
                            cell: (row) => (
                                <>
                                    <StatusMenu
                                        type="uses_item"
                                        id={row.id}
                                        status={row.status}
                                        options={PUBLICATION_STATUSES}
                                        variants={PUBLICATION_VARIANTS}
                                    />
                                    <AiDraftBadge
                                        draftedBy={row.drafted_by}
                                        status={row.status}
                                    />
                                </>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="category"
                                value={filters.category}
                                label="Rubrique"
                                options={[...USES_CATEGORIES]}
                            />
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[...PUBLICATION_STATUSES]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={UsesItemController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
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
