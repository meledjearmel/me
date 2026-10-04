import { Form, Head } from '@inertiajs/react';
import UsesItemController from '@/actions/App/Http/Controllers/Admin/UsesItemController';
import FormPageHeader from '@/components/admin/form-page-header';
import UsesItemFields from '@/components/admin/uses-item-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as usesIndex } from '@/routes/admin/uses-items';

export default function UsesItemCreate() {
    return (
        <>
            <Head title="Nouvel élément « Uses »" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouvel élément"
                    description="Matériel, outil, application ou service"
                    backHref={usesIndex()}
                    backLabel="Uses"
                />

                <Form
                    {...UsesItemController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <UsesItemFields errors={errors} />
                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

UsesItemCreate.layout = {
    breadcrumbs: [
        { title: 'Uses', href: usesIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
