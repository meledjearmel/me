import { Form, Head } from '@inertiajs/react';
import ProposalController from '@/actions/App/Http/Controllers/Admin/ProposalController';
import FormPageHeader from '@/components/admin/form-page-header';
import ProposalFields from '@/components/admin/proposal-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as proposalsIndex } from '@/routes/admin/proposals';
import type { Proposal } from '@/types';

export default function ProposalEdit({ proposal }: { proposal: Proposal }) {
    return (
        <>
            <Head title="Modifier la proposition" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la proposition"
                    description={
                        proposal.drafted_by
                            ? `Rédigée par ${proposal.drafted_by}`
                            : proposal.job_title
                    }
                    backHref={proposalsIndex()}
                    backLabel="Propositions"
                />

                <Form
                    {...ProposalController.update.form(proposal.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <ProposalFields
                                proposal={proposal}
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

ProposalEdit.layout = {
    breadcrumbs: [
        { title: 'Propositions', href: proposalsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
