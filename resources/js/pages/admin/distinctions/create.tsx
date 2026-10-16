import { Form, Head } from '@inertiajs/react';
import DistinctionController from '@/actions/App/Http/Controllers/Admin/DistinctionController';
import DistinctionFields from '@/components/admin/distinction-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as distinctionsIndex } from '@/routes/admin/distinctions';
import type { DistinctionContextOptions } from '@/types';

export default function DistinctionCreate({
    contexts,
}: {
    contexts: DistinctionContextOptions;
}) {
    return (
        <>
            <Head title="Nouvelle distinction" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle distinction"
                    backHref={distinctionsIndex()}
                    backLabel="Distinctions"
                />

                <Form
                    {...DistinctionController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <DistinctionFields
                                contexts={contexts}
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

DistinctionCreate.layout = {
    breadcrumbs: [
        { title: 'Distinctions', href: distinctionsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
