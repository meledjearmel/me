import { Form, Head } from '@inertiajs/react';
import UsesItemController from '@/actions/App/Http/Controllers/Admin/UsesItemController';
import FormPageHeader from '@/components/admin/form-page-header';
import UsesItemFields from '@/components/admin/uses-item-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as usesIndex } from '@/routes/admin/uses-items';
import type { UsesItem } from '@/types';

export default function UsesItemEdit({ item }: { item: UsesItem }) {
    return (
        <>
            <Head title={`Modifier — ${item.name}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier l’élément"
                    description={item.name}
                    backHref={usesIndex()}
                    backLabel="Uses"
                />

                <Form
                    {...UsesItemController.update.form(item.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <UsesItemFields item={item} errors={errors} />
                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

UsesItemEdit.layout = {
    breadcrumbs: [
        { title: 'Uses', href: usesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
