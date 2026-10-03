import { Head, Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import AppointmentController from '@/actions/App/Http/Controllers/Admin/AppointmentController';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    APPOINTMENT_LOCATIONS,
    APPOINTMENT_STATUSES,
    APPOINTMENT_STATUS_VARIANT,
    formatSlot,
    optionLabel,
} from '@/lib/admin-options';
import { index as pageIndex } from '@/routes/admin/appointments';
import type { Appointment, ListFilters, Paginated } from '@/types';

export default function AppointmentsIndex({
    appointments,
    filters,
}: {
    appointments: Paginated<Appointment>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Rendez-vous" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Rendez-vous"
                    description="Demandes de rendez-vous reçues depuis le site"
                />

                <ResourceList
                    paginator={appointments}
                    filters={filters}
                    searchPlaceholder="Nom, email, société, téléphone…"
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[...APPOINTMENT_STATUSES]}
                            />
                            <FilterSelect
                                state={state}
                                name="location"
                                value={filters.location}
                                label="Lieu"
                                options={[...APPOINTMENT_LOCATIONS]}
                            />
                        </>
                    )}
                    columns={[
                        {
                            header: 'Quand',
                            cell: (row) =>
                                formatSlot(row.starts_at, row.ends_at),
                        },
                        {
                            header: 'Avec',
                            cell: (row) => (
                                <div className="flex flex-col">
                                    <span className="font-medium">
                                        {row.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {[row.company, row.email]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </span>
                                </div>
                            ),
                        },
                        {
                            header: 'Type',
                            cell: (row) => row.appointment_type?.name.fr,
                        },
                        {
                            header: 'Lieu',
                            cell: (row) =>
                                optionLabel(
                                    APPOINTMENT_LOCATIONS,
                                    row.location,
                                ),
                        },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        APPOINTMENT_STATUS_VARIANT[row.status]
                                    }
                                >
                                    {optionLabel(
                                        APPOINTMENT_STATUSES,
                                        row.status,
                                    )}
                                </Badge>
                            ),
                        },
                    ]}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={AppointmentController.show(row.id)}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={AppointmentController.destroy.url(row.id)}
                                confirmMessage={`Supprimer le rendez-vous de ${row.name} ? Son créneau sera libéré.`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

AppointmentsIndex.layout = {
    breadcrumbs: [{ title: 'Rendez-vous', href: pageIndex() }],
};
