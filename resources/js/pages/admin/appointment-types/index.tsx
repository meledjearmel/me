import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import AppointmentTypeController from '@/actions/App/Http/Controllers/Admin/AppointmentTypeController';
import { ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { APPOINTMENT_LOCATIONS, optionLabel } from '@/lib/admin-options';
import { index as pageIndex } from '@/routes/admin/appointment-types';
import type { AppointmentType, ListFilters, Paginated } from '@/types';

export default function AppointmentTypesIndex({
    appointmentTypes,
    filters,
}: {
    appointmentTypes: Paginated<AppointmentType>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Types de rendez-vous" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Types de rendez-vous"
                        description="Ce que les visiteurs peuvent réserver : durée et lieux possibles"
                    />
                    <Button asChild>
                        <Link href={AppointmentTypeController.create()}>
                            <Plus data-icon="inline-start" /> Nouveau type
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={appointmentTypes}
                    filters={filters}
                    searchPlaceholder="Nom…"
                    columns={[
                        { header: 'Nom', cell: (row) => row.name.fr },
                        {
                            header: 'Durée',
                            cell: (row) => `${row.duration_minutes} min`,
                        },
                        {
                            header: 'Lieux',
                            cell: (row) =>
                                row.locations
                                    .map((location) =>
                                        optionLabel(
                                            APPOINTMENT_LOCATIONS,
                                            location,
                                        ),
                                    )
                                    .join(', '),
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
                        {
                            header: 'Rendez-vous',
                            cell: (row) => row.appointments_count ?? 0,
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
                    ]}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={AppointmentTypeController.edit(
                                        row.id,
                                    )}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={AppointmentTypeController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer le type "${row.name.fr}" ? Les rendez-vous déjà pris sont conservés.`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

AppointmentTypesIndex.layout = {
    breadcrumbs: [{ title: 'Types de rendez-vous', href: pageIndex() }],
};
