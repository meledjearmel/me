import { Form, Head } from '@inertiajs/react';
import EstimatorRateController from '@/actions/App/Http/Controllers/Admin/EstimatorRateController';
import EstimatorRateFields from '@/components/admin/estimator-rate-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as ratesIndex } from '@/routes/admin/estimator-rates';
import type { EstimatorRate } from '@/types';

export default function EstimatorRateEdit({ rate }: { rate: EstimatorRate }) {
    return (
        <>
            <Head title="Modifier la grille tarifaire" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la grille tarifaire"
                    description={`${rate.name.fr} · ${rate.items_count ?? 0} élément(s)`}
                    backHref={ratesIndex()}
                    backLabel="Grilles tarifaires"
                />

                <Form
                    {...EstimatorRateController.update.form(rate.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <EstimatorRateFields rate={rate} errors={errors} />

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

EstimatorRateEdit.layout = {
    breadcrumbs: [
        { title: 'Grilles tarifaires', href: ratesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
