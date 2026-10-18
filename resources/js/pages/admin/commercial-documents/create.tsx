import { Form, Head } from '@inertiajs/react';
import CommercialDocumentController from '@/actions/App/Http/Controllers/Admin/CommercialDocumentController';
import CommercialDocumentFields from '@/components/admin/commercial-document-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { DOCUMENT_TYPES } from '@/lib/commercial-documents';
import { index as documentsIndex } from '@/routes/admin/commercial-documents';
import type { CommercialDocument, CommercialDocumentType } from '@/types';

export default function CommercialDocumentCreate({
    type,
    defaults,
}: {
    type: CommercialDocumentType;
    defaults: Pick<
        CommercialDocument,
        'deposit_percent' | 'payment_terms' | 'valid_until' | 'due_at'
    >;
}) {
    const title = `Nouveau ${DOCUMENT_TYPES[type].toLowerCase()}`;

    return (
        <>
            <Head title={title} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title={title}
                    description="Enregistré en brouillon : il reçoit son numéro quand vous l'émettez"
                    backHref={documentsIndex()}
                    backLabel="Devis et factures"
                />

                <Form
                    {...CommercialDocumentController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <input type="hidden" name="type" value={type} />
                            <CommercialDocumentFields
                                type={type}
                                defaults={defaults}
                                errors={errors}
                            />

                            <Button disabled={processing}>
                                Enregistrer le brouillon
                            </Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

CommercialDocumentCreate.layout = {
    breadcrumbs: [
        { title: 'Devis et factures', href: documentsIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
