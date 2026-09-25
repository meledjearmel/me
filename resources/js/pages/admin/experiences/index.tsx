import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/experiences';
import ExperienceController from '@/actions/App/Http/Controllers/Admin/ExperienceController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, Experience } from '@/types';

export default function ExperiencesIndex({
    experiences,
    filters,
}: {
    experiences: Paginated<Experience>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Expériences" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading title="Expériences" />
                    <Button asChild>
                        <Link href={ExperienceController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                            expérience
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={experiences}
                    filters={filters}
                    searchPlaceholder="Entreprise, rôle, lieu…"
                    columns={[
                        { header: 'Entreprise', cell: (row) => row.company },
                        { header: 'Rôle (FR)', cell: (row) => row.role.fr },
                        {
                            header: 'Période',
                            cell: (row) =>
                                `${row.start_date} — ${row.end_date ?? 'en cours'}`,
                        },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.status === 'published'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {row.status === 'published'
                                        ? 'Publié'
                                        : 'Brouillon'}
                                </Badge>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[
                                    { value: 'published', label: 'Publié' },
                                    { value: 'draft', label: 'Brouillon' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={ExperienceController.show(row.id)}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={ExperienceController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={ExperienceController.destroy.url(row.id)}
                                confirmMessage={`Supprimer l'expérience "${row.company}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

ExperiencesIndex.layout = {
    breadcrumbs: [{ title: 'Expériences', href: pageIndex() }],
};
