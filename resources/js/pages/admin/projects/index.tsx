import { Head, Link } from '@inertiajs/react';
import { Plus, Eye, SquarePen } from 'lucide-react';
import { index as pageIndex } from '@/routes/admin/projects';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ListFilters, Paginated, Project } from '@/types';

export default function ProjectsIndex({
    projects,
    filters,
}: {
    projects: Paginated<Project>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Projets" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading title="Projets" />
                    <Button asChild>
                        <Link href={ProjectController.create()}>
                            <Plus data-icon="inline-start" /> Nouveau projet
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={projects}
                    filters={filters}
                    searchPlaceholder="Titre ou slug…"
                    columns={[
                        { header: 'Titre (FR)', cell: (row) => row.title.fr },
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
                                        : 'Archivé'}
                                </Badge>
                            ),
                        },
                        {
                            header: 'Mis en avant',
                            cell: (row) => (row.is_featured ? 'Oui' : '—'),
                        },
                        { header: 'Ordre', cell: (row) => row.sort_order },
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
                                    { value: 'archived', label: 'Archivé' },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="is_featured"
                                value={filters.is_featured}
                                label="Mise en avant"
                                options={[
                                    { value: '1', label: 'Mis en avant' },
                                    { value: '0', label: 'Non mis en avant' },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="is_open_source"
                                value={filters.is_open_source}
                                label="Open source"
                                options={[
                                    { value: '1', label: 'Open source' },
                                    { value: '0', label: 'Privé' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={ProjectController.show(row.id)}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={ProjectController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={ProjectController.destroy.url(row.id)}
                                confirmMessage={`Supprimer le projet "${row.title.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projets', href: pageIndex() }],
};
