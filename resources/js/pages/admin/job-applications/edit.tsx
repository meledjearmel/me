import { Form, Head } from '@inertiajs/react';
import JobApplicationController from '@/actions/App/Http/Controllers/Admin/JobApplicationController';
import FormPageHeader from '@/components/admin/form-page-header';
import JobApplicationFields from '@/components/admin/job-application-fields';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import {
    index as applicationsIndex,
    show,
} from '@/routes/admin/job-applications';
import type { JobApplication } from '@/types';

type Option = { id: number; label: string };

export default function JobApplicationEdit({
    application,
    jobProfiles,
    projects,
}: {
    application: JobApplication;
    jobProfiles: Option[];
    projects: Option[];
}) {
    return (
        <>
            <Head title={`Modifier — ${application.company}`} />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la candidature"
                    description={`${application.company} · ${application.position}`}
                    backHref={show(application.id)}
                    backLabel="Fiche"
                />

                <Form
                    {...JobApplicationController.update.form(application.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <JobApplicationFields
                                application={application}
                                jobProfiles={jobProfiles}
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

JobApplicationEdit.layout = {
    breadcrumbs: [
        { title: 'Candidatures', href: applicationsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
