import { Form, Head } from "@inertiajs/react";
import CommercialDocumentController from "@/actions/App/Http/Controllers/Admin/CommercialDocumentController";
import CommercialDocumentFields from "@/components/admin/commercial-document-fields";
import FormPageHeader from "@/components/admin/form-page-header";
import { Button } from "@/components/ui/button";
import { FieldGroup } from "@/components/ui/field";
import { DOCUMENT_TYPES } from "@/lib/commercial-documents";
import { index as documentsIndex } from "@/routes/admin/commercial-documents";
import type { CommercialDocument } from "@/types";

export default function CommercialDocumentEdit({
    document,
}: {
    document: CommercialDocument;
}) {
    return (
        <>
            <Head
                title={`Modifier le ${DOCUMENT_TYPES[document.type].toLowerCase()}`}
            />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title={`${DOCUMENT_TYPES[document.type]} en brouillon`}
                    description={`${document.client_company ?? document.client_name} · ${document.title}`}
                    backHref={CommercialDocumentController.show(document.id)}
                    backLabel="Retour au document"
                />

                <Form
                    {...CommercialDocumentController.update.form(document.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            {document.engagement_id && (
                                <input
                                    type="hidden"
                                    name="engagement_id"
                                    value={document.engagement_id}
                                />
                            )}
                            <CommercialDocumentFields
                                type={document.type}
                                document={document}
                                errors={errors}
                            />

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

CommercialDocumentEdit.layout = {
    breadcrumbs: [
        { title: "Devis et factures", href: documentsIndex() },
        { title: "Modifier", href: "" },
    ],
};
