import { Head, Link } from '@inertiajs/react';
import { ExternalLink, Plus, SquarePen } from 'lucide-react';
import EstimatorItemController from '@/actions/App/Http/Controllers/Admin/EstimatorItemController';
import ActionTooltip from '@/components/admin/action-tooltip';
import { ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import {
    ESTIMATOR_BILLINGS,
    ESTIMATOR_KINDS,
} from '@/components/admin/estimator-item-fields';
import EstimatorSettingsForm from '@/components/admin/estimator-settings-form';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/estimator-items';
import { index as publicEstimate } from '@/routes/estimate';
import type {
    EstimatorItem,
    EstimatorSettings,
    ListFilters,
    Paginated,
} from '@/types';

export default function EstimatorItemsIndex({
    estimatorItems,
    filters,
    settings,
}: {
    estimatorItems: Paginated<EstimatorItem>;
    filters: ListFilters;
    settings: EstimatorSettings;
}) {
    return (
        <>
            <Head title="Estimateur de projet" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Estimateur de projet"
                        description="Types de projet et options : le visiteur obtient une fourchette de prix et de délai, puis envoie sa demande"
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={publicEstimate.url('fr')}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink data-icon="inline-start" /> Voir
                                la page
                            </a>
                        </Button>
                        <Button asChild>
                            <Link href={EstimatorItemController.create()}>
                                <Plus data-icon="inline-start" /> Nouvel élément
                            </Link>
                        </Button>
                    </div>
                </div>

                <EstimatorSettingsForm settings={settings} />

                <ResourceList
                    paginator={estimatorItems}
                    filters={filters}
                    searchPlaceholder="Nom…"
                    columns={[
                        { header: 'Nom', cell: (row) => row.name.fr },
                        {
                            header: 'Nature',
                            cell: (row) =>
                                ESTIMATOR_KINDS.find(
                                    (kind) => kind.value === row.kind,
                                )?.label,
                        },
                        {
                            header: 'Heures',
                            cell: (row) =>
                                `${row.hours_min} – ${row.hours_max} h`,
                        },
                        {
                            header: 'Facturation',
                            cell: (row) =>
                                ESTIMATOR_BILLINGS.find(
                                    (billing) => billing.value === row.billing,
                                )?.label ?? '—',
                        },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.is_active ? 'default' : 'secondary'
                                    }
                                >
                                    {row.is_active ? 'Proposé' : 'Masqué'}
                                </Badge>
                            ),
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                    ]}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={EstimatorItemController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={EstimatorItemController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer « ${row.name.fr} » ? Les demandes déjà reçues gardent leur estimation.`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

EstimatorItemsIndex.layout = {
    breadcrumbs: [{ title: 'Estimateur de projet', href: pageIndex() }],
};
