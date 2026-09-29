import { Head, Link } from '@inertiajs/react';
import { Eye, Plus, SquarePen } from 'lucide-react';
import CelebrationController from '@/actions/App/Http/Controllers/Admin/CelebrationController';
import { ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/celebrations';
import type { Celebration, ListFilters, Paginated } from '@/types';

function formatPeriod(celebration: Celebration): string {
    const format = (date: string) =>
        new Date(`${date}T00:00:00`).toLocaleDateString('fr-FR');

    if (celebration.starts_at && celebration.ends_at) {
        return `${format(celebration.starts_at)} → ${format(celebration.ends_at)}`;
    }

    if (celebration.starts_at) {
        return `Dès le ${format(celebration.starts_at)}`;
    }

    if (celebration.ends_at) {
        return `Jusqu'au ${format(celebration.ends_at)}`;
    }

    return 'Toujours';
}

export default function CelebrationsIndex({
    celebrations,
    filters,
}: {
    celebrations: Paginated<Celebration>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Surprises" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Surprises"
                        description="Bonnes nouvelles annoncées au hasard par le personnage du site"
                    />
                    <Button asChild>
                        <Link href={CelebrationController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle surprise
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={celebrations}
                    filters={filters}
                    searchPlaceholder="Message…"
                    columns={[
                        {
                            header: 'Message (FR)',
                            className: 'max-w-md whitespace-normal',
                            cell: (row) => row.message.fr,
                        },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.is_active ? 'default' : 'secondary'
                                    }
                                >
                                    {row.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            ),
                        },
                        { header: 'Période', cell: formatPeriod },
                        {
                            header: 'Fréquence',
                            cell: (row) =>
                                `${row.chance_percent} % après ${row.delay_seconds} s, ${row.display_seconds} s`,
                        },
                        { header: 'Poids', cell: (row) => row.weight },
                        {
                            header: 'Félicitations',
                            cell: (row) => row.congratulations_count,
                        },
                    ]}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={CelebrationController.show(row.id)}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={CelebrationController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={CelebrationController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage="Supprimer cette surprise et son compteur de félicitations ?"
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

CelebrationsIndex.layout = {
    breadcrumbs: [{ title: 'Surprises', href: pageIndex() }],
};
