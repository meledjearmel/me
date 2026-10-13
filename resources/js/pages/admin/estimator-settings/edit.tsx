import { Head } from '@inertiajs/react';
import EstimatorSettingsForm from '@/components/admin/estimator-settings-form';
import Heading from '@/components/heading';
import { edit as pageEdit } from '@/routes/admin/estimator-settings';
import type { EstimatorSettings } from '@/types';

export default function EstimatorSettingsEdit({
    settings,
}: {
    settings: EstimatorSettings;
}) {
    return (
        <>
            <Head title="Tarifs de l'estimateur" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <Heading
                    title="Tarifs et calcul"
                    description="Ce qui transforme les heures de la grille en prix et en délai pour le visiteur"
                />

                <EstimatorSettingsForm settings={settings} />
            </div>
        </>
    );
}

EstimatorSettingsEdit.layout = {
    breadcrumbs: [{ title: "Tarifs de l'estimateur", href: pageEdit() }],
};
