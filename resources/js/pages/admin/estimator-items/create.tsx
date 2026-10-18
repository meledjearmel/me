import { Form, Head } from '@inertiajs/react';
import EstimatorItemController from '@/actions/App/Http/Controllers/Admin/EstimatorItemController';
import EstimatorItemFields from '@/components/admin/estimator-item-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as itemsIndex } from '@/routes/admin/estimator-items';
import { index as optionsIndex } from '@/routes/admin/estimator-options';
import type {
    EstimatorCurrency,
    EstimatorItem,
    EstimatorRateOption,
} from '@/types';

export default function EstimatorItemCreate({
    kind,
    projectTypes,
    rates,
    globalRates,
}: {
    kind: EstimatorItem['kind'];
    projectTypes: { id: number; label: string }[];
    rates: EstimatorRateOption[];
    globalRates: Partial<Record<EstimatorCurrency, number>>;
}) {
    const isFeature = kind === 'feature';
    const title = isFeature ? 'Nouvelle option' : 'Nouveau type de projet';

    return (
        <>
            <Head title={title} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title={title}
                    description={
                        isFeature
                            ? "Ce qui s'ajoute à un type de projet, en heures ou en pourcentage"
                            : "La base d'une estimation, chiffrée en heures"
                    }
                    backHref={isFeature ? optionsIndex() : itemsIndex()}
                    backLabel={isFeature ? 'Options' : 'Types de projet'}
                />

                <Form
                    {...EstimatorItemController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <EstimatorItemFields
                                rates={rates}
                                globalRates={globalRates}
                                errors={errors}
                                projectTypes={projectTypes}
                                defaultKind={kind}
                            />

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
