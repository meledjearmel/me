import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/educations';
import EducationController from '@/actions/App/Http/Controllers/Admin/EducationController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, Education } from '@/types';

export default function EducationsIndex({
    educations,
    filters,
}: {
    educations: Paginated<Education>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Formation" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading title="Formation" />
                    <Button asChild>
                        <Link href={EducationController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle formation
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={educations}
                    filters={filters}
                    searchPlaceholder="Établissement, diplôme, domaine…"
                    columns={[
                        {
                            header: 'Établissement',
                            cell: (row) => row.institution,
                        },
                        {
                            header: 'Diplôme (FR)',
                            cell: (row) => row.degree.fr,
                        },
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
                                    href={EducationController.show(row.id)}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={EducationController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={EducationController.destroy.url(row.id)}
                                confirmMessage={`Supprimer la formation "${row.degree.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

EducationsIndex.layout = {
    breadcrumbs: [{ title: 'Formation', href: pageIndex() }],
};
