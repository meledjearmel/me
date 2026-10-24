import { Form, Head } from '@inertiajs/react';
import ProposalController from '@/actions/App/Http/Controllers/Admin/ProposalController';
import FormPageHeader from '@/components/admin/form-page-header';
import ProposalFields from '@/components/admin/proposal-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as proposalsIndex } from '@/routes/admin/proposals';

export default function ProposalCreate() {
    return (
        <>
            <Head title="Nouvelle proposition" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle proposition"
                    description="Une proposition déjà envoyée, pour enrichir les exemples de l’IA"
                    backHref={proposalsIndex()}
                    backLabel="Propositions"
                />

                <Form
                    {...ProposalController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <ProposalFields errors={errors} />

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

ProposalCreate.layout = {
    breadcrumbs: [
        { title: 'Propositions', href: proposalsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
