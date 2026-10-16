import { Head, Link } from "@inertiajs/react";
import { PUBLICATION_STATUSES } from "@/lib/admin-options";
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from "@/components/admin/status-menu";
import { Plus, Eye, SquarePen } from "lucide-react";
import { index as pageIndex } from "@/routes/admin/domains";
import DomainController from "@/actions/App/Http/Controllers/Admin/DomainController";
import DeleteButton from "@/components/admin/delete-button";
import { FilterSelect, ResourceList } from "@/components/admin/data-list";
import Heading from "@/components/heading";
import { Button } from "@/components/ui/button";
import type { ListFilters, Paginated, Domain } from "@/types";
import ActionTooltip from "@/components/admin/action-tooltip";

export default function DomainsIndex({
    domains,
    filters,
}: {
    domains: Paginated<Domain>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Domaines" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Domaines"
                        description="Dev, Infra, Sécurité, Design…"
                    />
                    <Button asChild>
                        <Link href={DomainController.create()}>
                            <Plus data-icon="inline-start" /> Nouveau domaine
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={domains}
                    filters={filters}
                    searchPlaceholder="Clé ou libellé…"
                    columns={[
                        {
                            header: "Clé",
                            className: "font-mono text-xs",
                            cell: (row) => row.key,
                        },
                        { header: "Libellé (FR)", cell: (row) => row.label.fr },
                        {
                            header: "Couleur",
                            cell: (row) => (
                                <span className="inline-flex items-center gap-2">
                                    <span
                                        className="size-3 rounded-full border"
                                        style={{ backgroundColor: row.color }}
                                    />
                                    {row.color}
                                </span>
                            ),
                        },
                        { header: "Ordre", cell: (row) => row.sort_order },
                        {
                            header: "Statut",
                            fit: true,
                            cell: (row) => (
                                <StatusMenu
                                    type="domain"
                                    id={row.id}
                                    status={row.status}
                                    options={PUBLICATION_STATUSES}
                                    variants={PUBLICATION_VARIANTS}
                                />
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
                                    { value: "published", label: "Publié" },
                                    { value: "draft", label: "Brouillon" },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={DomainController.show(row.id)}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={DomainController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={DomainController.destroy.url(row.id)}
                                confirmMessage={`Supprimer le domaine "${row.label.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

DomainsIndex.layout = {
    breadcrumbs: [{ title: "Domaines", href: pageIndex() }],
};
