import { Form, Head } from '@inertiajs/react';
import CelebrationController from '@/actions/App/Http/Controllers/Admin/CelebrationController';
import CelebrationFields from '@/components/admin/celebration-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as celebrationsIndex } from '@/routes/admin/celebrations';

export default function CelebrationCreate() {
    return (
        <>
            <Head title="Nouvelle surprise" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle surprise"
                    description="Une bonne nouvelle que le personnage annoncera aux visiteurs"
                    backHref={celebrationsIndex()}
                    backLabel="Surprises"
                />

                <Form
                    {...CelebrationController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <CelebrationFields errors={errors} />

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

CelebrationCreate.layout = {
    breadcrumbs: [
        { title: 'Surprises', href: celebrationsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
