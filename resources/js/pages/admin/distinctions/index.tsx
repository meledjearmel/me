import { Head, Link } from "@inertiajs/react";
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from "@/components/admin/status-menu";
import { Plus, SquarePen } from "lucide-react";
import DistinctionController from "@/actions/App/Http/Controllers/Admin/DistinctionController";
import ActionTooltip from "@/components/admin/action-tooltip";
import AiDraftBadge from "@/components/admin/ai-draft-badge";
import { FilterSelect, ResourceList } from "@/components/admin/data-list";
import DeleteButton from "@/components/admin/delete-button";
import { formatDate } from "@/components/admin/show-page";
import Heading from "@/components/heading";
import { Button } from "@/components/ui/button";
import { PUBLICATION_STATUSES } from "@/lib/admin-options";
import { index as pageIndex } from "@/routes/admin/distinctions";
import type {
    Distinction,
    DistinctionContextType,
    ListFilters,
    Paginated,
} from "@/types";

const CONTEXT_LABELS: Record<DistinctionContextType, string> = {
    experience: "Expérience",
    education: "Formation",
    project: "Projet",
};

export default function DistinctionsIndex({
    distinctions,
    filters,
}: {
    distinctions: Paginated<Distinction>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Distinctions" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Distinctions"
                        description="Prix et distinctions de la carte « Distinction » de la page À propos (elles y alternent), avec leurs félicitations"
                    />
                    <Button asChild>
                        <Link href={DistinctionController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={distinctions}
                    filters={filters}
                    searchPlaceholder="Intitulé ou organisme…"
                    columns={[
                        { header: "Intitulé", cell: (row) => row.title.fr },
                        { header: "Organisme", cell: (row) => row.issuer },
                        {
                            header: "Contexte",
                            cell: (row) =>
                                row.context_type && row.context_label ? (
                                    <span className="text-sm">
                                        <span className="text-muted-foreground">
                                            {CONTEXT_LABELS[row.context_type]}{" "}
                                            ·{" "}
                                        </span>
                                        {row.context_label}
                                    </span>
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                ),
                        },
                        {
                            header: "Obtenue le",
                            cell: (row) => formatDate(row.awarded_on),
                        },
                        {
                            header: "Félicitations",
                            cell: (row) => row.congratulations_count,
                        },
                        {
                            header: "Statut",
                            fit: true,
                            cell: (row) => (
                                <>
                                    <StatusMenu
                                        type="distinction"
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
                                        href={DistinctionController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={DistinctionController.destroy.url(row.id)}
                                confirmMessage={`Supprimer « ${row.title.fr} » ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

DistinctionsIndex.layout = {
    breadcrumbs: [{ title: "Distinctions", href: pageIndex() }],
};
