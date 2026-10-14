import { Head, Link } from "@inertiajs/react";
import { Eye, Plus, Settings } from "lucide-react";
import CommercialDocumentController from "@/actions/App/Http/Controllers/Admin/CommercialDocumentController";
import ActionTooltip from "@/components/admin/action-tooltip";
import { FilterSelect, ResourceList } from "@/components/admin/data-list";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    DOCUMENT_STATUSES,
    DOCUMENT_TYPES,
    formatMoney,
} from "@/lib/commercial-documents";
import { edit as billingSettingsEdit } from "@/routes/admin/billing-settings";
import { index as documentsIndex } from "@/routes/admin/commercial-documents";
import type { CommercialDocument, ListFilters, Paginated } from "@/types";

const STATUS_VARIANT = {
    draft: "outline",
    issued: "secondary",
    accepted: "default",
    declined: "destructive",
    paid: "default",
    cancelled: "outline",
} as const;

/** Devis, proformas et factures, du brouillon au paiement. */
export default function CommercialDocumentsIndex({
    documents,
    filters,
}: {
    documents: Paginated<CommercialDocument>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Devis et factures" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Devis et factures"
                        description="Numérotés à l'émission (ADEV-DEV, ADEV-PRO, ADEV-FAC), puis figés"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={billingSettingsEdit()}>
                                <Settings data-icon="inline-start" /> Réglages
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={CommercialDocumentController.create({
                                    query: { type: "quote" },
                                })}
                            >
                                <Plus data-icon="inline-start" /> Nouveau devis
                            </Link>
                        </Button>
                    </div>
                </div>

                <ResourceList
                    paginator={documents}
                    filters={filters}
                    searchPlaceholder="Numéro, client, projet…"
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="type"
                                value={filters.type}
                                label="Type"
                                options={Object.entries(DOCUMENT_TYPES).map(
                                    ([value, label]) => ({ value, label }),
                                )}
                            />
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={Object.entries(DOCUMENT_STATUSES).map(
                                    ([value, label]) => ({ value, label }),
                                )}
                            />
                        </>
                    )}
                    columns={[
                        {
                            header: "Document",
                            cell: (row) => (
                                <>
                                    <div className="font-medium">
                                        {row.number ?? "Brouillon"}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {DOCUMENT_TYPES[row.type]}
                                    </div>
                                </>
                            ),
                        },
                        {
                            header: "Client",
                            cell: (row) => (
                                <>
                                    <div>
                                        {row.client_company ?? row.client_name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {row.title}
                                    </div>
                                </>
                            ),
                        },
                        {
                            header: "Montant",
                            className: "tabular-nums",
                            cell: (row) => formatMoney(row.total, row.currency),
                        },
                        {
                            header: "Statut",
                            cell: (row) => (
                                <Badge variant={STATUS_VARIANT[row.status]}>
                                    {DOCUMENT_STATUSES[row.status]}
                                </Badge>
                            ),
                        },
                    ]}
                    actions={(row) => (
                        <ActionTooltip label="Voir">
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={CommercialDocumentController.show(
                                        row.id,
                                    )}
                                    aria-label="Voir"
                                >
                                    <Eye />
                                </Link>
                            </Button>
                        </ActionTooltip>
                    )}
                />
            </div>
        </>
    );
}

CommercialDocumentsIndex.layout = {
    breadcrumbs: [{ title: "Devis et factures", href: documentsIndex() }],
};
