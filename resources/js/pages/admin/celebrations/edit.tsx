import { Form, Head } from '@inertiajs/react';
import CelebrationController from '@/actions/App/Http/Controllers/Admin/CelebrationController';
import CelebrationFields from '@/components/admin/celebration-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as celebrationsIndex } from '@/routes/admin/celebrations';
import type { Celebration } from '@/types';

export default function CelebrationEdit({
    celebration,
}: {
    celebration: Celebration;
}) {
    return (
        <>
            <Head title="Modifier la surprise" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la surprise"
                    description={`${celebration.congratulations_count} félicitations reçues`}
                    backHref={celebrationsIndex()}
                    backLabel="Surprises"
                />

                <Form
                    {...CelebrationController.update.form(celebration.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <CelebrationFields
                                celebration={celebration}
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

CelebrationEdit.layout = {
    breadcrumbs: [
        { title: 'Surprises', href: celebrationsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
