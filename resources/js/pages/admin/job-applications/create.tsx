import { Form, Head } from '@inertiajs/react';
import JobApplicationController from '@/actions/App/Http/Controllers/Admin/JobApplicationController';
import FormPageHeader from '@/components/admin/form-page-header';
import JobApplicationFields from '@/components/admin/job-application-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as applicationsIndex } from '@/routes/admin/job-applications';

type Option = { id: number; label: string };

export default function JobApplicationCreate({
    jobProfiles,
    projects,
}: {
    jobProfiles: Option[];
    projects: Option[];
}) {
    return (
        <>
            <Head title="Nouvelle candidature" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Nouvelle candidature"
                    description="Une page privée pour une entreprise et un poste"
                    backHref={applicationsIndex()}
                    backLabel="Candidatures"
                />

                <Form
                    {...JobApplicationController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <JobApplicationFields
                                jobProfiles={jobProfiles}
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

JobApplicationCreate.layout = {
    breadcrumbs: [
        { title: 'Candidatures', href: applicationsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
