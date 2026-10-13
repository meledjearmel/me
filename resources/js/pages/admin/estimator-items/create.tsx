import { Form, Head } from '@inertiajs/react';
import EstimatorItemController from '@/actions/App/Http/Controllers/Admin/EstimatorItemController';
import EstimatorItemFields from '@/components/admin/estimator-item-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as itemsIndex } from '@/routes/admin/estimator-items';

export default function EstimatorItemCreate() {
    return (
        <>
            <Head title="Nouvel élément de l'estimateur" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Nouvel élément de l'estimateur"
                    description="Un type de projet ou une option, avec son prix et son délai"
                    backHref={itemsIndex()}
                    backLabel="Estimateur de projet"
                />

                <Form
                    {...EstimatorItemController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <EstimatorItemFields errors={errors} />

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

EstimatorItemCreate.layout = {
    breadcrumbs: [
        { title: 'Estimateur de projet', href: itemsIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
