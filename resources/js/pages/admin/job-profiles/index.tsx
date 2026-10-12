import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/job-profiles';
import JobProfileController from '@/actions/App/Http/Controllers/Admin/JobProfileController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, JobProfile } from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

export default function JobProfilesIndex({
    jobProfiles,
    filters,
}: {
    jobProfiles: Paginated<JobProfile>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Profils métier" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Profils métier"
                        description="Pilotent les projets affichés dans chaque CV généré"
                    />
                    <Button asChild>
                        <Link href={JobProfileController.create()}>
                            <Plus data-icon="inline-start" /> Nouveau profil
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={jobProfiles}
                    filters={filters}
                    searchPlaceholder="Clé ou libellé…"
                    columns={[
                        {
                            header: 'Clé',
                            className: 'font-mono text-xs',
                            cell: (row) => row.key,
                        },
                        { header: 'Libellé (FR)', cell: (row) => row.label.fr },
                        { header: 'Ordre', cell: (row) => row.sort_order },
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
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={JobProfileController.show(row.id)}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={JobProfileController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={JobProfileController.destroy.url(row.id)}
                                confirmMessage={`Supprimer le profil "${row.label.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

JobProfilesIndex.layout = {
    breadcrumbs: [{ title: 'Profils métier', href: pageIndex() }],
};
