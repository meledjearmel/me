import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import EstimatorRateController from '@/actions/App/Http/Controllers/Admin/EstimatorRateController';
import ActionTooltip from '@/components/admin/action-tooltip';
import { ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import { ESTIMATOR_CURRENCIES } from '@/components/admin/estimator-rate-inputs';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as ratesIndex } from '@/routes/admin/estimator-rates';
import type { EstimatorRate, ListFilters, Paginated } from '@/types';

/**
 * Les grilles tarifaires de l'estimateur : un élément suit ses propres taux, sinon sa
 * grille, sinon le taux global des réglages.
 */
export default function EstimatorRatesIndex({
    rates,
    filters,
}: {
    rates: Paginated<EstimatorRate>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Estimateur · Grilles tarifaires" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Grilles tarifaires"
                        description="Des taux horaires nommés, partagés par les types de projet et options qui les suivent"
                    />
                    <Button asChild>
                        <Link href={EstimatorRateController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle grille
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={rates}
                    filters={filters}
                    searchPlaceholder="Nom…"
                    columns={[
                        { header: 'Nom', cell: (row) => row.name.fr },
                        {
                            header: 'Taux',
                            cell: (row) =>
                                ESTIMATOR_CURRENCIES.filter(
                                    (currency) =>
                                        row.hourly_rates[currency.value],
                                )
                                    .map(
                                        (currency) =>
                                            `${row.hourly_rates[currency.value]} ${currency.label}/h`,
                                    )
                                    .join(' · ') || 'Taux global',
                        },
                        {
                            header: 'Éléments',
                            cell: (row) => row.items_count ?? 0,
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                    ]}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={EstimatorRateController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={EstimatorRateController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer « ${row.name.fr} » ? Ses ${row.items_count ?? 0} élément(s) suivront leurs propres taux ou le taux global.`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

EstimatorRatesIndex.layout = {
    breadcrumbs: [{ title: 'Grilles tarifaires', href: ratesIndex() }],
};
