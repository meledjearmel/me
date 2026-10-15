import { Form, Head } from "@inertiajs/react";
import AppReleaseController from "@/actions/App/Http/Controllers/Admin/AppReleaseController";
import FormPageHeader from "@/components/admin/form-page-header";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { index as releasesIndex } from "@/routes/admin/app-releases";

export default function AppReleaseCreate({
    nextBuildNumber,
}: {
    nextBuildNumber: number;
}) {
    return (
        <>
            <Head title="Envoyer un build" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <FormPageHeader
                    title="Envoyer un build"
                    description="APK compilé avec flutter build apk --release, toujours signé avec la même clé"
                    backHref={releasesIndex()}
                    backLabel="Application mobile"
                />

                <Form
                    {...AppReleaseController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors, progress }) => (
                        <FieldGroup>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field data-invalid={!!errors.version_name}>
                                    <FieldLabel htmlFor="version_name">
                                        Version *
                                    </FieldLabel>
                                    <Input
                                        id="version_name"
                                        name="version_name"
                                        placeholder="1.4.0"
                                        required
                                    />
                                    <FieldError>
                                        {errors.version_name}
                                    </FieldError>
                                </Field>
                                <Field data-invalid={!!errors.build_number}>
                                    <FieldLabel htmlFor="build_number">
                                        Numéro de build *
                                    </FieldLabel>
                                    <Input
                                        id="build_number"
                                        name="build_number"
                                        type="number"
                                        min={nextBuildNumber}
                                        defaultValue={nextBuildNumber}
                                        required
                                    />
                                    <FieldDescription>
                                        Partie après le « + » dans pubspec.yaml.
                                    </FieldDescription>
                                    <FieldError>
                                        {errors.build_number}
                                    </FieldError>
                                </Field>
                            </div>
                            <Field data-invalid={!!errors.notes}>
                                <FieldLabel htmlFor="notes">
                                    Notes de version
                                </FieldLabel>
                                <Textarea id="notes" name="notes" rows={4} />
                                <FieldError>{errors.notes}</FieldError>
                            </Field>
                            <Field data-invalid={!!errors.apk}>
                                <FieldLabel htmlFor="apk">APK *</FieldLabel>
                                <Input
                                    id="apk"
                                    name="apk"
                                    type="file"
                                    accept=".apk,application/vnd.android.package-archive"
                                    required
                                />
                                <FieldDescription>
                                    128 Mo au plus.
                                </FieldDescription>
                                <FieldError>{errors.apk}</FieldError>
                            </Field>

                            <Button disabled={processing}>
                                {progress
                                    ? `Envoi… ${progress.percentage ?? 0} %`
                                    : "Envoyer"}
                            </Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

AppReleaseCreate.layout = {
    breadcrumbs: [
        { title: "Application mobile", href: releasesIndex() },
        { title: "Envoyer un build", href: "" },
    ],
};
