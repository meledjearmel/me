import { Form, router } from '@inertiajs/react';
import { FileText, Loader2, RotateCcw, Sparkles } from 'lucide-react';
import { useState } from 'react';
import JobApplicationCvController from '@/actions/App/Http/Controllers/Admin/JobApplicationCvController';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

export type CvTailoring = {
    title: string | null;
    summary: string | null;
    experiences: Record<string, number[]>;
    skill_ids: number[];
    reasoning: string | null;
};

/**
 * Le CV d'une candidature sur sa fiche : standard ou adapté à l'offre par l'IA (titre et
 * résumé réécrits, réalisations et compétences choisies parmi celles du site), avec aperçu,
 * retouche du titre et du résumé, et retour au CV standard.
 */
export default function JobApplicationCv({
    applicationId,
    tailoring,
    tailoredAt,
    hasProfile,
    hasPosting,
}: {
    applicationId: number;
    tailoring: CvTailoring | null;
    tailoredAt: string | null;
    hasProfile: boolean;
    hasPosting: boolean;
}) {
    const [pending, setPending] = useState(false);

    if (!hasProfile) {
        return (
            <p className="text-sm text-muted-foreground">
                Choisissez un profil métier dans la candidature pour proposer un
                CV.
            </p>
        );
    }

    const tailor = () =>
        router.post(
            JobApplicationCvController.tailor.url(applicationId),
            {},
            {
                preserveScroll: true,
                onStart: () => setPending(true),
                onFinish: () => setPending(false),
            },
        );

    return (
        <div className="space-y-4">
            <p className="text-sm">
                {tailoring
                    ? `CV adapté à l'offre le ${new Date(tailoredAt ?? '').toLocaleDateString('fr-FR', { dateStyle: 'long' })} : c'est lui que l'entreprise télécharge.`
                    : 'CV standard du profil métier. Adaptez-le pour mettre en avant ce que demande l’offre, sans rien inventer.'}
            </p>

            {tailoring?.reasoning && (
                <p className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                    <strong>Choix de l'IA :</strong> {tailoring.reasoning}
                </p>
            )}

            <div className="flex flex-wrap gap-2">
                <Button variant="outline" asChild>
                    <a
                        href={JobApplicationCvController.show.url(
                            applicationId,
                        )}
                        target="_blank"
                        rel="noreferrer"
                    >
                        <FileText /> Voir le CV
                    </a>
                </Button>
                <Button
                    variant="outline"
                    onClick={tailor}
                    disabled={pending}
                    title={
                        hasPosting
                            ? undefined
                            : "Sans texte d'offre, l'IA s'appuie seulement sur l'intitulé du poste."
                    }
                >
                    {pending ? (
                        <Loader2 className="animate-spin" />
                    ) : (
                        <Sparkles />
                    )}
                    {tailoring
                        ? 'Adapter de nouveau'
                        : 'Adapter le CV avec l’IA'}
                </Button>
                {tailoring && (
                    <Button
                        variant="ghost"
                        onClick={() =>
                            router.delete(
                                JobApplicationCvController.destroy.url(
                                    applicationId,
                                ),
                                { preserveScroll: true },
                            )
                        }
                    >
                        <RotateCcw /> Revenir au CV standard
                    </Button>
                )}
            </div>

            {tailoring && (
                <Form
                    {...JobApplicationCvController.update.form(applicationId)}
                    options={{ preserveScroll: true }}
                    className="space-y-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <Field data-invalid={!!errors.title}>
                                <FieldLabel htmlFor="cv_title">
                                    Titre du CV
                                </FieldLabel>
                                <Input
                                    id="cv_title"
                                    name="title"
                                    defaultValue={tailoring.title ?? ''}
                                />
                                <FieldError>{errors.title}</FieldError>
                            </Field>
                            <Field data-invalid={!!errors.summary}>
                                <FieldLabel htmlFor="cv_summary">
                                    Résumé du CV
                                </FieldLabel>
                                <Textarea
                                    id="cv_summary"
                                    name="summary"
                                    rows={4}
                                    defaultValue={tailoring.summary ?? ''}
                                />
                                <FieldDescription>
                                    {Object.keys(tailoring.experiences).length}{' '}
                                    expérience(s) resserrée(s) sur leurs
                                    réalisations clés,{' '}
                                    {tailoring.skill_ids.length} compétence(s)
                                    mises en avant.
                                </FieldDescription>
                                <FieldError>{errors.summary}</FieldError>
                            </Field>
                            <Button size="sm" disabled={processing}>
                                Enregistrer le titre et le résumé
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
}
