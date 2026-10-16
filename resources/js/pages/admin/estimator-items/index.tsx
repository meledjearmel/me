import StatusMenu from "@/components/admin/status-menu";
import { Head, Link } from "@inertiajs/react";
import { ExternalLink, Plus, SquarePen } from "lucide-react";
import EstimatorItemController from "@/actions/App/Http/Controllers/Admin/EstimatorItemController";
import ActionTooltip from "@/components/admin/action-tooltip";
import { ResourceList } from "@/components/admin/data-list";
import DeleteButton from "@/components/admin/delete-button";
import EstimatorAiGrid from "@/components/admin/estimator-ai-grid";
import { ESTIMATOR_BILLINGS } from "@/components/admin/estimator-item-fields";
import Heading from "@/components/heading";
import { Button } from "@/components/ui/button";
import { index as projectTypesIndex } from "@/routes/admin/estimator-items";
import { index as publicEstimate } from "@/routes/estimate";
import type { EstimatorItem, ListFilters, Paginated } from "@/types";

/**
 * La grille de l'estimateur, une page par nature (entrées du menu « Estimateur ») :
 * les types de projet, puis les options, en heures ou en %, liées ou non à des types.
 */
export default function EstimatorItemsIndex({
    kind,
    estimatorItems,
    filters,
}: {
    kind: EstimatorItem["kind"];
    estimatorItems: Paginated<EstimatorItem>;
    filters: ListFilters;
}) {
    const isFeature = kind === "feature";
    const title = isFeature ? "Options" : "Types de projet";

    const statusColumn = {
        header: "Statut",
        fit: true,
        cell: (row: EstimatorItem) => (
            <StatusMenu
                type="estimator_item"
                id={row.id}
                status={row.is_active ? "active" : "hidden"}
                options={[
                    { value: "active", label: "Proposé" },
                    { value: "hidden", label: "Masqué" },
                ]}
                variants={{ active: "default", hidden: "secondary" }}
            />
        ),
    };

    const rateColumn = {
        header: "Tarif",
        cell: (row: EstimatorItem) =>
            row.pricing === "percent"
                ? "Celui du type"
                : [
                      row.hourly_rates && Object.keys(row.hourly_rates).length
                          ? "Taux propres"
                          : null,
                      row.rate?.name.fr ?? null,
                  ]
                      .filter(Boolean)
                      .join(" + ") || "Taux global",
    };

    return (
        <>
            <Head title={`Estimateur · ${title}`} />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title={title}
                        description={
                            isFeature
                                ? "Ce qui s'ajoute à un type de projet : des heures ou un pourcentage, pour tous les types ou certains seulement"
                                : "La base de chaque estimation, chiffrée en heures, en forfait ou à l’heure"
                        }
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={publicEstimate.url("fr")}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink data-icon="inline-start" /> Voir
                                la page
                            </a>
                        </Button>
                        <EstimatorAiGrid />
                        <Button asChild>
                            <Link
                                href={EstimatorItemController.create({
                                    query: { kind },
                                })}
                            >
                                <Plus data-icon="inline-start" />{" "}
                                {isFeature ? "Nouvelle option" : "Nouveau type"}
                            </Link>
                        </Button>
                    </div>
                </div>

                <ResourceList
                    paginator={estimatorItems}
                    filters={filters}
                    searchPlaceholder="Nom…"
                    columns={
                        isFeature
                            ? [
                                  { header: "Nom", cell: (row) => row.name.fr },
                                  {
                                      header: "Ajoute",
                                      cell: (row) =>
                                          row.pricing === "percent"
                                              ? `+${row.percent} %`
                                              : row.pricing === "recurring"
                                                ? `${row.hours_min} – ${row.hours_max} h / mois`
                                                : `${row.hours_min} – ${row.hours_max} h`,
                                  },
                                  {
                                      header: "Pour",
                                      cell: (row) =>
                                          row.project_types?.length
                                              ? row.project_types
                                                    .map((type) => type.name.fr)
                                                    .join(", ")
                                              : "Tous les types",
                                  },
                                  rateColumn,
                                  statusColumn,
                                  {
                                      header: "Ordre",
                                      cell: (row) => row.sort_order,
                                  },
                              ]
                            : [
                                  { header: "Nom", cell: (row) => row.name.fr },
                                  {
                                      header: "Heures",
                                      cell: (row) =>
                                          `${row.hours_min} – ${row.hours_max} h`,
                                  },
                                  {
                                      header: "Facturation",
                                      cell: (row) =>
                                          ESTIMATOR_BILLINGS.find(
                                              (billing) =>
                                                  billing.value === row.billing,
                                          )?.label ?? "—",
                                  },
                                  rateColumn,
                                  statusColumn,
                                  {
                                      header: "Ordre",
                                      cell: (row) => row.sort_order,
                                  },
                              ]
                    }
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={EstimatorItemController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={EstimatorItemController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer « ${row.name.fr} » ? Les demandes déjà reçues gardent leur estimation.`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

EstimatorItemsIndex.layout = {
    breadcrumbs: [{ title: "Estimateur de projet", href: projectTypesIndex() }],
};
