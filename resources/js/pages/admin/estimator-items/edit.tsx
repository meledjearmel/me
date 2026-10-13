import { Form, Head } from '@inertiajs/react';
import EstimatorItemController from '@/actions/App/Http/Controllers/Admin/EstimatorItemController';
import EstimatorItemFields from '@/components/admin/estimator-item-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as itemsIndex } from '@/routes/admin/estimator-items';
import { index as optionsIndex } from '@/routes/admin/estimator-options';
import type { EstimatorItem } from '@/types';

export default function EstimatorItemEdit({
    estimatorItem,
    projectTypes,
}: {
    estimatorItem: EstimatorItem;
    projectTypes: { id: number; label: string }[];
}) {
    const isFeature = estimatorItem.kind === 'feature';
    const title = isFeature
        ? "Modifier l'option"
        : 'Modifier le type de projet';

    return (
        <>
            <Head title={title} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title={title}
                    description={estimatorItem.name.fr}
                    backHref={isFeature ? optionsIndex() : itemsIndex()}
                    backLabel={isFeature ? 'Options' : 'Types de projet'}
                />

                <Form
                    {...EstimatorItemController.update.form(estimatorItem.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <EstimatorItemFields
                                estimatorItem={estimatorItem}
                                projectTypes={projectTypes.filter(
                                    (type) => type.id !== estimatorItem.id,
                                )}
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
