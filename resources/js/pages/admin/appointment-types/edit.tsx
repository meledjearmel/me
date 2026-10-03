import { Form, Head } from '@inertiajs/react';
import AppointmentTypeController from '@/actions/App/Http/Controllers/Admin/AppointmentTypeController';
import AppointmentTypeFields from '@/components/admin/appointment-type-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as typesIndex } from '@/routes/admin/appointment-types';
import type { AppointmentType } from '@/types';

export default function AppointmentTypeEdit({
    appointmentType,
}: {
    appointmentType: AppointmentType;
}) {
    return (
        <>
            <Head title="Modifier le type de rendez-vous" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier le type de rendez-vous"
                    description={appointmentType.name.fr}
                    backHref={typesIndex()}
                    backLabel="Types de rendez-vous"
                />

                <Form
                    {...AppointmentTypeController.update.form(
                        appointmentType.id,
                    )}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <AppointmentTypeFields
                                appointmentType={appointmentType}
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

AppointmentTypeEdit.layout = {
    breadcrumbs: [
        { title: 'Types de rendez-vous', href: typesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
