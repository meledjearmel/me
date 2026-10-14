import { Head, Link, router } from "@inertiajs/react";
import { Copy, Download, FileCheck2, SquarePen } from "lucide-react";
import CommercialDocumentController from "@/actions/App/Http/Controllers/Admin/CommercialDocumentController";
import CommercialDocumentSendDialog from "@/components/admin/commercial-document-send-dialog";
import DeleteButton from "@/components/admin/delete-button";
import FormPageHeader from "@/components/admin/form-page-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    DOCUMENT_STATUSES,
    DOCUMENT_TYPES,
    formatMoney,
} from "@/lib/commercial-documents";
import { index as documentsIndex } from "@/routes/admin/commercial-documents";
import { show as engagementShow } from "@/routes/admin/engagements";
import type {
    CommercialDocument,
    CommercialDocumentStatus,
    CommercialDocumentType,
} from "@/types";

const date = new Intl.DateTimeFormat("fr-FR", { dateStyle: "long" });

/**
 * Un document : aperçu du PDF et ce qu'on peut en faire selon son état (modifier, émettre,
 * envoyer, accepter ou refuser, convertir en proforma ou facture, marquer payé, annuler).
 */
export default function CommercialDocumentShow({
    document,
    derived,
}: {
    document: CommercialDocument;
    derived: Pick<CommercialDocument, "id" | "type" | "number" | "status">[];
}) {
    const isDraft = document.status === "draft";
    const isQuote = document.type === "quote";
    const pdfUrl = CommercialDocumentController.pdf.url(document.id);

    const setStatus = (status: CommercialDocumentStatus) =>
        router.patch(
            CommercialDocumentController.status.url(document.id),
            { status },
            { preserveScroll: true },
        );
    const convert = (type: CommercialDocumentType) =>
        router.post(CommercialDocumentController.convert.url(document.id), {
            type,
        });

    return (
        <>
            <Head
                title={
                    document.number ??
                    `${DOCUMENT_TYPES[document.type]} en brouillon`
                }
            />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <FormPageHeader
                    title={
                        document.number ??
                        `${DOCUMENT_TYPES[document.type]} en brouillon`
                    }
                    description={`${DOCUMENT_TYPES[document.type]} · ${document.client_company ?? document.client_name} · ${formatMoney(document.total, document.currency)}`}
                    backHref={documentsIndex()}
                    backLabel="Devis et factures"
                />

                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant={isDraft ? "outline" : "secondary"}>
                        {DOCUMENT_STATUSES[document.status]}
                    </Badge>
                    {document.sent_at && (
                        <span className="text-sm text-muted-foreground">
                            Envoyé le {date.format(new Date(document.sent_at))}
                        </span>
                    )}
                    {document.engagement && (
                        <Link
                            href={engagementShow(document.engagement.id)}
                            className="text-sm underline"
                        >
                            Demande de {document.engagement.name}
                        </Link>
                    )}
                    {document.source?.number && (
                        <Link
                            href={CommercialDocumentController.show(
                                document.source.id,
                            )}
                            className="text-sm underline"
                        >
                            Issu de {document.source.number}
                        </Link>
                    )}
                    {derived.map((item) => (
                        <Link
                            key={item.id}
                            href={CommercialDocumentController.show(item.id)}
                            className="text-sm underline"
                        >
                            {DOCUMENT_TYPES[item.type]}{" "}
                            {item.number ?? "(brouillon)"}
                        </Link>
                    ))}
                </div>

                <div className="flex flex-wrap gap-2">
                    {isDraft && (
                        <Button variant="outline" asChild>
                            <Link
                                href={CommercialDocumentController.edit(
                                    document.id,
                                )}
                            >
                                <SquarePen data-icon="inline-start" /> Modifier
                            </Link>
                        </Button>
                    )}
                    {isDraft && (
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(
                                    CommercialDocumentController.issue.url(
                                        document.id,
                                    ),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <FileCheck2 data-icon="inline-start" /> Émettre sans
                            envoyer
                        </Button>
                    )}
                    {document.status !== "cancelled" && (
                        <CommercialDocumentSendDialog document={document} />
                    )}
                    <Button variant="outline" asChild>
                        <a href={`${pdfUrl}?download=1`}>
                            <Download data-icon="inline-start" /> PDF
                        </a>
                    </Button>
                    {document.public_url && !isDraft && (
                        <Button
                            variant="outline"
                            onClick={() =>
                                void navigator.clipboard.writeText(
                                    document.public_url ?? "",
                                )
                            }
                        >
                            <Copy data-icon="inline-start" /> Copier le lien du
                            client
                        </Button>
                    )}
                    {isQuote && document.status === "issued" && (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => setStatus("accepted")}
                            >
                                Marquer accepté
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setStatus("declined")}
                            >
                                Marquer refusé
                            </Button>
                        </>
                    )}
                    {isQuote &&
                        ["issued", "accepted"].includes(document.status) && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => convert("proforma")}
                                >
                                    Créer la proforma
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() => convert("invoice")}
                                >
                                    Créer la facture
                                </Button>
                            </>
                        )}
                    {document.type === "proforma" &&
                        document.status !== "draft" && (
                            <Button
                                variant="outline"
                                onClick={() => convert("invoice")}
                            >
                                Créer la facture
                            </Button>
                        )}
                    {!isQuote && document.status === "issued" && (
                        <Button
                            variant="outline"
                            onClick={() => setStatus("paid")}
                        >
                            Marquer payé
                        </Button>
                    )}
                    {!isDraft &&
                        !["cancelled", "paid"].includes(document.status) && (
                            <Button
                                variant="ghost"
                                onClick={() => setStatus("cancelled")}
                            >
                                Annuler le document
                            </Button>
                        )}
                    {isDraft && (
                        <DeleteButton
                            href={CommercialDocumentController.destroy.url(
                                document.id,
                            )}
                            confirmMessage="Supprimer ce brouillon ?"
                        />
                    )}
                </div>

                <iframe
                    key={`${document.status}-${document.total}`}
                    src={pdfUrl}
                    title="Aperçu du PDF"
                    className="h-[80vh] w-full rounded-lg border bg-white"
                />
            </div>
        </>
    );
}

CommercialDocumentShow.layout = {
    breadcrumbs: [
        { title: "Devis et factures", href: documentsIndex() },
        { title: "Document", href: "" },
    ],
};
