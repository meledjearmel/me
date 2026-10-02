import { Form, Head, router } from '@inertiajs/react';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import FormPageHeader from '@/components/admin/form-page-header';
import TranslatableField from '@/components/translatable-field';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FormSelect from '@/components/admin/form-select';
import { TESTIMONIAL_STATUSES } from '@/lib/admin-options';
import { index as testimonialsIndex } from '@/routes/admin/testimonials';
import type { Project, Testimonial, TestimonialVideo } from '@/types';

export default function TestimonialEdit({
    testimonial,
    video,
    projects,
}: {
    testimonial: Testimonial;
    video: TestimonialVideo | null;
    projects: Project[];
}) {
    return (
        <>
            <Head title={`Avis de ${testimonial.author_name}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modérer l'avis"
                    description={`${testimonial.author_name} — ${testimonial.author_email}`}
                    backHref={testimonialsIndex()}
                    backLabel="Avis"
                />

                <Form
                    {...TestimonialController.update.form(testimonial.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.author_name}>
                                <FieldLabel htmlFor="author_name">
                                    Nom de l'auteur *
                                </FieldLabel>
                                <Input
                                    id="author_name"
                                    name="author_name"
                                    required
                                    defaultValue={testimonial.author_name}
                                />
                                <FieldError>{errors.author_name}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.author_role}>
                                <FieldLabel htmlFor="author_role">
                                    Rôle de l'auteur
                                </FieldLabel>
                                <Input
                                    id="author_role"
                                    name="author_role"
                                    defaultValue={testimonial.author_role ?? ''}
                                />
                                <FieldError>{errors.author_role}</FieldError>
                            </Field>

                            <TranslatableField
                                name="content"
                                label="Avis"
                                defaultValue={testimonial.content}
                                errors={{
                                    fr: errors['content.fr'],
                                    en: errors['content.en'],
                                }}
                                textarea
                                required
                                maxLength={2000}
                            />

                            <div className="grid gap-2">
                                <TranslatableField
                                    name="highlight"
                                    label="Phrase d'accroche"
                                    defaultValue={
                                        testimonial.highlight ?? undefined
                                    }
                                    errors={{
                                        fr: errors['highlight.fr'],
                                        en: errors['highlight.en'],
                                    }}
                                    maxLength={280}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Facultative : la meilleure phrase de l'avis,
                                    affichée en grand sur les cartes. L'avis
                                    complet reste lisible au clic.
                                </p>
                            </div>

                            <div className="grid content-start gap-2">
                                <Label htmlFor="video">Vidéo de l'avis</Label>
                                {video && (
                                    <div className="flex flex-wrap items-start gap-3">
                                        <video
                                            src={video.url}
                                            poster={
                                                video.poster_url ?? undefined
                                            }
                                            controls
                                            preload="metadata"
                                            className="max-h-64 rounded-md bg-black"
                                        />
                                        <div className="grid gap-2 text-sm text-muted-foreground">
                                            {video.duration === null
                                                ? 'Compression en cours (ou ffmpeg absent du serveur).'
                                                : `Prête · ${Math.floor(video.duration / 60)}:${String(video.duration % 60).padStart(2, '0')}`}
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            "Retirer cette vidéo ? L'avis redeviendra un avis texte.",
                                                        )
                                                    ) {
                                                        router.delete(
                                                            TestimonialController.destroyVideo.url(
                                                                testimonial.id,
                                                            ),
                                                        );
                                                    }
                                                }}
                                            >
                                                Retirer la vidéo
                                            </Button>
                                        </div>
                                    </div>
                                )}
                                <Input
                                    id="video"
                                    name="video"
                                    type="file"
                                    accept="video/*"
                                />
                                <p className="text-sm text-muted-foreground">
                                    MP4, MOV ou WebM, 250 Mo max. Elle est
                                    compressée automatiquement après l'envoi
                                    (720p) et une image d'aperçu est extraite.
                                </p>
                                <FieldError>{errors.video}</FieldError>
                            </div>

                            {video && (
                                <TranslatableField
                                    name="video_transcript"
                                    label="Transcription de la vidéo"
                                    defaultValue={
                                        testimonial.video_transcript ??
                                        undefined
                                    }
                                    errors={{
                                        fr: errors['video_transcript.fr'],
                                        en: errors['video_transcript.en'],
                                    }}
                                    textarea
                                    maxLength={10000}
                                />
                            )}

                            <Field data-invalid={!!errors.status}>
                                <FieldLabel htmlFor="status">
                                    Statut *
                                </FieldLabel>
                                <FormSelect
                                    id="status"
                                    name="status"
                                    required
                                    defaultValue={testimonial.status}
                                >
                                    {TESTIMONIAL_STATUSES.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.status}</FieldError>
                            </Field>

                            <div className="grid gap-2">
                                <Field orientation="horizontal">
                                    <input
                                        type="hidden"
                                        name="is_featured"
                                        value="0"
                                    />
                                    <Checkbox
                                        id="is_featured"
                                        name="is_featured"
                                        value="1"
                                        defaultChecked={testimonial.is_featured}
                                    />
                                    <FieldLabel htmlFor="is_featured">
                                        À la une sur l'accueil (3 au maximum)
                                    </FieldLabel>
                                </Field>
                                <p className="text-sm text-muted-foreground">
                                    Seuls les avis approuvés s'affichent. Sans
                                    aucun avis choisi, trois avis approuvés sont
                                    tirés au hasard ; avec un seul choisi, il
                                    reste seul.
                                </p>
                                <FieldError>{errors.is_featured}</FieldError>
                            </div>

                            <Field data-invalid={!!errors.project_id}>
                                <FieldLabel htmlFor="project_id">
                                    Projet lié
                                </FieldLabel>
                                <FormSelect
                                    id="project_id"
                                    name="project_id"
                                    defaultValue={testimonial.project_id ?? ''}
                                >
                                    <option value="">Général</option>
                                    {projects.map((project) => (
                                        <option
                                            key={project.id}
                                            value={project.id}
                                        >
                                            {project.title.fr}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.project_id}</FieldError>
                            </Field>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TestimonialEdit.layout = {
    breadcrumbs: [
        { title: 'Avis', href: testimonialsIndex() },
        { title: 'Modérer', href: '' },
    ],
};
