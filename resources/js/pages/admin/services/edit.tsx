import { Form, Head } from '@inertiajs/react';
import ServiceController from '@/actions/App/Http/Controllers/Admin/ServiceController';
import FormPageHeader from '@/components/admin/form-page-header';
import ServiceFields from '@/components/admin/service-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as servicesIndex } from '@/routes/admin/services';
import type { Service } from '@/types';

type Option = { id: number; label: string };

export default function ServiceEdit({
    service,
    projectTypes,
    projects,
}: {
    service: Service;
    projectTypes: Option[];
    projects: Option[];
}) {
    return (
        <>
            <Head title={`Modifier — ${service.title.fr}`} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Modifier l'offre"
                    description={service.title.fr}
                    backHref={servicesIndex()}
                    backLabel="Services"
                />

                <Form
                    {...ServiceController.update.form(service.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <ServiceFields
                                service={service}
                                projectTypes={projectTypes}
                                projects={projects}
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

ServiceEdit.layout = {
    breadcrumbs: [
        { title: 'Services', href: servicesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
