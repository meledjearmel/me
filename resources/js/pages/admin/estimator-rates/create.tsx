import { Form, Head } from '@inertiajs/react';
import EstimatorRateController from '@/actions/App/Http/Controllers/Admin/EstimatorRateController';
import EstimatorRateFields from '@/components/admin/estimator-rate-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as ratesIndex } from '@/routes/admin/estimator-rates';

export default function EstimatorRateCreate() {
    return (
        <>
            <Head title="Nouvelle grille tarifaire" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle grille tarifaire"
                    description="Des taux horaires partagés par les types de projet et options qui la suivent"
                    backHref={ratesIndex()}
                    backLabel="Grilles tarifaires"
                />

                <Form
                    {...EstimatorRateController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <EstimatorRateFields errors={errors} />

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

EstimatorRateCreate.layout = {
    breadcrumbs: [
        { title: 'Grilles tarifaires', href: ratesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
