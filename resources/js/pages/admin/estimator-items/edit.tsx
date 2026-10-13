import { Form, Head } from '@inertiajs/react';
import EstimatorItemController from '@/actions/App/Http/Controllers/Admin/EstimatorItemController';
import EstimatorItemFields from '@/components/admin/estimator-item-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as itemsIndex } from '@/routes/admin/estimator-items';
import type { EstimatorItem } from '@/types';

export default function EstimatorItemEdit({
    estimatorItem,
}: {
    estimatorItem: EstimatorItem;
}) {
    return (
        <>
            <Head title="Modifier l'élément de l'estimateur" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Modifier l'élément de l'estimateur"
                    description={estimatorItem.name.fr}
                    backHref={itemsIndex()}
                    backLabel="Estimateur de projet"
                />

                <Form
                    {...EstimatorItemController.update.form(estimatorItem.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <EstimatorItemFields
                                estimatorItem={estimatorItem}
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

EstimatorItemEdit.layout = {
    breadcrumbs: [
        { title: 'Estimateur de projet', href: itemsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
