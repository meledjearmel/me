import { Head, Link } from "@inertiajs/react";
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from "@/components/admin/status-menu";
import { Plus, Eye, SquarePen } from "lucide-react";
import { index as pageIndex } from "@/routes/admin/projects";
import ProjectController from "@/actions/App/Http/Controllers/Admin/ProjectController";
import DeleteButton from "@/components/admin/delete-button";
import { FilterSelect, ResourceList } from "@/components/admin/data-list";
import Heading from "@/components/heading";
import AiDraftBadge from "@/components/admin/ai-draft-badge";
import { Button } from "@/components/ui/button";
import { PROJECT_STATUSES } from "@/lib/admin-options";
import type { ListFilters, Paginated, Project } from "@/types";
import ActionTooltip from "@/components/admin/action-tooltip";

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

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
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
                        { header: "Titre (FR)", cell: (row) => row.title.fr },
                        {
                            header: "Statut",
                            fit: true,
                            cell: (row) => (
                                <StatusMenu
                                    type="project"
                                    id={row.id}
                                    status={row.status}
                                    options={PROJECT_STATUSES}
                                    variants={PUBLICATION_VARIANTS}
                                />
                            ),
                        },
                        {
                            header: "Origine",
                            cell: (row) => (
                                <AiDraftBadge
                                    draftedBy={row.drafted_by}
                                    status={row.status}
                                />
                            ),
                        },
                        {
                            header: "Mis en avant",
                            cell: (row) => (row.is_featured ? "Oui" : "—"),
                        },
                        { header: "Ordre", cell: (row) => row.sort_order },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[...PROJECT_STATUSES]}
                            />
                            <FilterSelect
                                state={state}
                                name="is_featured"
                                value={filters.is_featured}
                                label="Mise en avant"
                                options={[
                                    { value: "1", label: "Mis en avant" },
                                    { value: "0", label: "Non mis en avant" },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="is_open_source"
                                value={filters.is_open_source}
                                label="Open source"
                                options={[
                                    { value: "1", label: "Open source" },
                                    { value: "0", label: "Privé" },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={ProjectController.show(row.id)}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={ProjectController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
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
    breadcrumbs: [{ title: "Projets", href: pageIndex() }],
};
