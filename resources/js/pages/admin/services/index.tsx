import { Head, Link } from "@inertiajs/react";
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from "@/components/admin/status-menu";
import { ExternalLink, Plus, SquarePen } from "lucide-react";
import ServiceController from "@/actions/App/Http/Controllers/Admin/ServiceController";
import ActionTooltip from "@/components/admin/action-tooltip";
import AiDraftBadge from "@/components/admin/ai-draft-badge";
import { FilterSelect, ResourceList } from "@/components/admin/data-list";
import DeleteButton from "@/components/admin/delete-button";
import Heading from "@/components/heading";
import { Button } from "@/components/ui/button";
import { PUBLICATION_STATUSES } from "@/lib/admin-options";
import { index as pageIndex } from "@/routes/admin/services";
import type { ListFilters, Paginated, Service } from "@/types";

const PRICE_LABELS: Record<Service["price_mode"], string> = {
    estimator: "Estimateur",
    manual: "Saisi",
    quote: "Sur devis",
};

export default function ServicesIndex({
    services,
    filters,
}: {
    services: Paginated<Service>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Services" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Services"
                        description="Les offres de la page publique « Services » (seules les offres publiées s’affichent ; sans aucune, la page est masquée)"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href="/fr/services"
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink data-icon="inline-start" /> Voir
                                la page
                            </a>
                        </Button>
                        <Button asChild>
                            <Link href={ServiceController.create()}>
                                <Plus data-icon="inline-start" /> Nouvelle offre
                            </Link>
                        </Button>
                    </div>
                </div>

                <ResourceList
                    paginator={services}
                    filters={filters}
                    searchPlaceholder="Nom ou ancre…"
                    columns={[
                        { header: "Nom", cell: (row) => row.title.fr },
                        {
                            header: "Prix",
                            cell: (row) => PRICE_LABELS[row.price_mode],
                        },
                        {
                            header: "Projets",
                            cell: (row) => row.projects_count ?? 0,
                        },
                        { header: "Ordre", cell: (row) => row.sort_order },
                        {
                            header: "Statut",
                            fit: true,
                            cell: (row) => (
                                <>
                                    <StatusMenu
                                        type="service"
                                        id={row.id}
                                        status={row.status}
                                        options={PUBLICATION_STATUSES}
                                        variants={PUBLICATION_VARIANTS}
                                    />
                                    <AiDraftBadge
                                        draftedBy={row.drafted_by}
                                        status={row.status}
                                    />
                                </>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="status"
                            value={filters.status}
                            label="Statut"
                            options={[...PUBLICATION_STATUSES]}
                        />
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={ServiceController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={ServiceController.destroy.url(row.id)}
                                confirmMessage={`Supprimer l'offre « ${row.title.fr} » ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

ServicesIndex.layout = {
    breadcrumbs: [{ title: "Services", href: pageIndex() }],
};
