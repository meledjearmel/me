import { Form, Head } from '@inertiajs/react';
import DistinctionController from '@/actions/App/Http/Controllers/Admin/DistinctionController';
import DistinctionFields from '@/components/admin/distinction-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as distinctionsIndex } from '@/routes/admin/distinctions';
import type { Distinction, DistinctionContextOptions } from '@/types';

export default function DistinctionEdit({
    distinction,
    contexts,
}: {
    distinction: Distinction;
    contexts: DistinctionContextOptions;
}) {
    return (
        <>
            <Head title={`Modifier — ${distinction.title.fr}`} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la distinction"
                    description={`${distinction.title.fr} · ${distinction.congratulations_count} félicitation${distinction.congratulations_count > 1 ? 's' : ''}`}
                    backHref={distinctionsIndex()}
                    backLabel="Distinctions"
                />

                <Form
                    {...DistinctionController.update.form(distinction.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <DistinctionFields
                                distinction={distinction}
                                contexts={contexts}
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

DistinctionEdit.layout = {
    breadcrumbs: [
        { title: 'Distinctions', href: distinctionsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
