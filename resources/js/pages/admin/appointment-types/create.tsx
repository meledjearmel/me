import { Form, Head } from '@inertiajs/react';
import AppointmentTypeController from '@/actions/App/Http/Controllers/Admin/AppointmentTypeController';
import AppointmentTypeFields from '@/components/admin/appointment-type-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as typesIndex } from '@/routes/admin/appointment-types';

export default function AppointmentTypeCreate() {
    return (
        <>
            <Head title="Nouveau type de rendez-vous" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouveau type de rendez-vous"
                    description="Un rendez-vous que les visiteurs peuvent réserver"
                    backHref={typesIndex()}
                    backLabel="Types de rendez-vous"
                />

                <Form
                    {...AppointmentTypeController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <AppointmentTypeFields errors={errors} />

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

AppointmentTypeCreate.layout = {
    breadcrumbs: [
        { title: 'Types de rendez-vous', href: typesIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
