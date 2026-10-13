import { Form, Head } from '@inertiajs/react';
import ServiceController from '@/actions/App/Http/Controllers/Admin/ServiceController';
import FormPageHeader from '@/components/admin/form-page-header';
import ServiceFields from '@/components/admin/service-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as servicesIndex } from '@/routes/admin/services';

type Option = { id: number; label: string };

export default function ServiceCreate({
    projectTypes,
    projects,
}: {
    projectTypes: Option[];
    projects: Option[];
}) {
    return (
        <>
            <Head title="Nouvelle offre" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle offre"
                    description="Une offre de la page « Services »"
                    backHref={servicesIndex()}
                    backLabel="Services"
                />

                <Form {...ServiceController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <ServiceFields
                                projectTypes={projectTypes}
                                projects={projects}
                                errors={errors}
                            />

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

ServiceCreate.layout = {
    breadcrumbs: [
        { title: 'Services', href: servicesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
