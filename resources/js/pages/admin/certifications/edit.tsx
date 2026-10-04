import { Form, Head, router } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import CertificationController from '@/actions/App/Http/Controllers/Admin/CertificationController';
import CertificationFields from '@/components/admin/certification-fields';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as certificationsIndex } from '@/routes/admin/certifications';
import type { Certification } from '@/types';

export default function CertificationEdit({
    certification,
}: {
    certification: Certification;
}) {
    return (
        <>
            <Head title={`Modifier — ${certification.name.fr}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la certification"
                    description={certification.name.fr}
                    backHref={certificationsIndex()}
                    backLabel="Certifications"
                />

                {certification.badge_url && (
                    <div className="flex items-center gap-3">
                        <img
                            src={certification.badge_url}
                            alt=""
                            className="size-16 rounded-md border object-contain"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                router.delete(
                                    CertificationController.destroyBadge.url(
                                        certification.id,
                                    ),
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <ImageOff /> Retirer le badge
                        </Button>
                    </div>
                )}

                <Form
                    {...CertificationController.update.form(certification.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <CertificationFields
                                certification={certification}
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

CertificationEdit.layout = {
    breadcrumbs: [
        { title: 'Certifications', href: certificationsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
