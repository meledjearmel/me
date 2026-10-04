import { Form, Head } from '@inertiajs/react';
import CertificationController from '@/actions/App/Http/Controllers/Admin/CertificationController';
import CertificationFields from '@/components/admin/certification-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as certificationsIndex } from '@/routes/admin/certifications';

export default function CertificationCreate() {
    return (
        <>
            <Head title="Nouvelle certification" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle certification ou formation"
                    backHref={certificationsIndex()}
                    backLabel="Certifications"
                />

                <Form
                    {...CertificationController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <CertificationFields errors={errors} />
                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

CertificationCreate.layout = {
    breadcrumbs: [
        { title: 'Certifications', href: certificationsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
