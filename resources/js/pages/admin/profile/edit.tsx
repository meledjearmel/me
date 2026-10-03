import { Form, Head, router } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import Heading from '@/components/heading';
import TranslatableField from '@/components/translatable-field';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import FormSelect from '@/components/admin/form-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit as profileEdit } from '@/routes/admin/profile';
import type { JobProfile, Profile } from '@/types';

export default function ProfileEdit({
    profile,
    jobProfiles,
}: {
    profile: Profile;
    jobProfiles: Pick<JobProfile, 'id' | 'label'>[];
}) {
    return (
        <>
            <Head title="Profil" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Profil"
                    description="Informations affichées sur le portfolio et utilisées pour le CV"
                />

                <Form
                    {...ProfileController.update.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid content-start gap-2">
                                    {profile.photo_url && (
                                        <img
                                            src={profile.photo_url}
                                            alt={profile.name}
                                            className="size-24 rounded-full object-cover"
                                        />
                                    )}
                                    <Label htmlFor="photo">
                                        Photo du profil (affichée sur le site)
                                    </Label>
                                    <Input
                                        id="photo"
                                        name="photo"
                                        type="file"
                                        accept="image/*"
                                    />
                                    <FieldError>{errors.photo}</FieldError>
                                </div>

                                <div className="grid content-start gap-2">
                                    {profile.cv_photo_url && (
                                        <img
                                            src={profile.cv_photo_url}
                                            alt={`${profile.name} (CV)`}
                                            className="size-24 rounded-full object-cover"
                                        />
                                    )}
                                    <Label htmlFor="cv_photo">
                                        Photo du CV (distincte de celle du site)
                                    </Label>
                                    <Input
                                        id="cv_photo"
                                        name="cv_photo"
                                        type="file"
                                        accept="image/*"
                                    />
                                    <FieldError>{errors.cv_photo}</FieldError>
                                </div>
                            </div>

                            <div className="grid content-start gap-2">
                                <Label htmlFor="music">
                                    Bande audio du site
                                </Label>
                                {profile.music && (
                                    <div className="flex items-center justify-between gap-2 text-sm">
                                        <audio
                                            src={profile.music.url}
                                            controls
                                            className="h-9 min-w-0 flex-1"
                                        />
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => {
                                                if (
                                                    confirm(
                                                        'Retirer cette bande audio ? La piste par défaut sera utilisée.',
                                                    )
                                                ) {
                                                    router.delete(
                                                        ProfileController.destroyMusic.url(),
                                                    );
                                                }
                                            }}
                                        >
                                            Retirer
                                        </Button>
                                    </div>
                                )}
                                <Input
                                    id="music"
                                    name="music"
                                    type="file"
                                    accept="audio/*"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Sans fichier, la piste par défaut est
                                    utilisée. MP3, OGG, WAV, M4A ou AAC, 20 Mo
                                    max.
                                </p>
                                <FieldError>{errors.music}</FieldError>
                            </div>

                            <Field data-invalid={!!errors.name}>
                                <FieldLabel htmlFor="name">Nom *</FieldLabel>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={profile.name}
                                    required
                                />
                                <FieldError>{errors.name}</FieldError>
                            </Field>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <Field data-invalid={!!errors.cv_last_name}>
                                    <FieldLabel htmlFor="cv_last_name">
                                        Nom sur le CV
                                    </FieldLabel>
                                    <Input
                                        id="cv_last_name"
                                        name="cv_last_name"
                                        defaultValue={
                                            profile.cv_last_name ?? ''
                                        }
                                        placeholder="MELEDJE GNAGNE"
                                    />
                                    <FieldError>
                                        {errors.cv_last_name}
                                    </FieldError>
                                </Field>

                                <Field data-invalid={!!errors.cv_first_name}>
                                    <FieldLabel htmlFor="cv_first_name">
                                        Prénoms sur le CV
                                    </FieldLabel>
                                    <Input
                                        id="cv_first_name"
                                        name="cv_first_name"
                                        defaultValue={
                                            profile.cv_first_name ?? ''
                                        }
                                        placeholder="Christian Armel"
                                    />
                                    <FieldError>
                                        {errors.cv_first_name}
                                    </FieldError>
                                </Field>
                            </div>

                            <TranslatableField
                                name="headline"
                                label="Accroche"
                                required
                                defaultValue={profile.headline}
                                errors={{
                                    fr: errors['headline.fr'],
                                    en: errors['headline.en'],
                                }}
                            />

                            <TranslatableField
                                name="bio_short"
                                label="Bio courte"
                                textarea
                                required
                                defaultValue={profile.bio_short}
                                errors={{
                                    fr: errors['bio_short.fr'],
                                    en: errors['bio_short.en'],
                                }}
                            />

                            <TranslatableField
                                name="bio_full"
                                label="Bio complète"
                                textarea
                                required
                                defaultValue={profile.bio_full}
                                errors={{
                                    fr: errors['bio_full.fr'],
                                    en: errors['bio_full.en'],
                                }}
                            />

                            <div className="grid gap-2 sm:grid-cols-2">
                                <Field data-invalid={!!errors.email}>
                                    <FieldLabel htmlFor="email">
                                        Email *
                                    </FieldLabel>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={profile.email}
                                        required
                                    />
                                    <FieldError>{errors.email}</FieldError>
                                </Field>
                                <Field data-invalid={!!errors.phone}>
                                    <FieldLabel htmlFor="phone">
                                        Téléphone
                                    </FieldLabel>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        defaultValue={profile.phone ?? ''}
                                    />
                                    <FieldError>{errors.phone}</FieldError>
                                </Field>
                            </div>

                            <Field data-invalid={!!errors.location}>
                                <FieldLabel htmlFor="location">
                                    Localisation
                                </FieldLabel>
                                <Input
                                    id="location"
                                    name="location"
                                    defaultValue={profile.location ?? ''}
                                />
                                <FieldError>{errors.location}</FieldError>
                            </Field>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <Field
                                    data-invalid={
                                        !!errors['social_links.github']
                                    }
                                >
                                    <FieldLabel htmlFor="social_links_github">
                                        GitHub
                                    </FieldLabel>
                                    <Input
                                        id="social_links_github"
                                        name="social_links[github]"
                                        defaultValue={
                                            profile.social_links?.github ?? ''
                                        }
                                    />
                                    <FieldError>
                                        {errors['social_links.github']}
                                    </FieldError>
                                </Field>
                                <Field
                                    data-invalid={
                                        !!errors['social_links.linkedin']
                                    }
                                >
                                    <FieldLabel htmlFor="social_links_linkedin">
                                        LinkedIn
                                    </FieldLabel>
                                    <Input
                                        id="social_links_linkedin"
                                        name="social_links[linkedin]"
                                        defaultValue={
                                            profile.social_links?.linkedin ?? ''
                                        }
                                    />
                                    <FieldError>
                                        {errors['social_links.linkedin']}
                                    </FieldError>
                                </Field>
                            </div>

                            <Field
                                data-invalid={
                                    !!errors.congratulation_notify_minutes
                                }
                            >
                                <FieldLabel htmlFor="congratulation_notify_minutes">
                                    Notification de félicitations au plus toutes
                                    les (minutes)
                                </FieldLabel>
                                <Input
                                    id="congratulation_notify_minutes"
                                    name="congratulation_notify_minutes"
                                    type="number"
                                    min={0}
                                    max={1440}
                                    defaultValue={
                                        profile.congratulation_notify_minutes
                                    }
                                    required
                                />
                                <FieldDescription>
                                    Par motif (chaque surprise, la page À
                                    propos). 0 : une notification à chaque
                                    envoi. L'historique garde toutes les
                                    félicitations.
                                </FieldDescription>
                                <FieldError>
                                    {errors.congratulation_notify_minutes}
                                </FieldError>
                            </Field>

                            <div className="grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                                <p className="text-sm font-medium sm:col-span-2">
                                    CV téléchargeable (page contact)
                                </p>

                                <Field
                                    data-invalid={!!errors.cv_job_profile_id}
                                >
                                    <FieldLabel htmlFor="cv_job_profile_id">
                                        Profil métier principal
                                    </FieldLabel>
                                    <FormSelect
                                        id="cv_job_profile_id"
                                        name="cv_job_profile_id"
                                        defaultValue={
                                            profile.cv_job_profile_id ?? ''
                                        }
                                    >
                                        <option value="">
                                            Le premier profil publié
                                        </option>
                                        {jobProfiles.map((jobProfile) => (
                                            <option
                                                key={jobProfile.id}
                                                value={jobProfile.id}
                                            >
                                                {jobProfile.label.fr}
                                            </option>
                                        ))}
                                    </FormSelect>
                                    <FieldDescription>
                                        Son CV est proposé au téléchargement,
                                        dans la langue de la page.
                                    </FieldDescription>
                                    <FieldError>
                                        {errors.cv_job_profile_id}
                                    </FieldError>
                                </Field>

                                <Field data-invalid={!!errors.cv_source}>
                                    <FieldLabel htmlFor="cv_source">
                                        Source prioritaire
                                    </FieldLabel>
                                    <FormSelect
                                        id="cv_source"
                                        name="cv_source"
                                        defaultValue={profile.cv_source}
                                    >
                                        <option value="uploaded">
                                            CV importé (PDF du profil métier)
                                        </option>
                                        <option value="generated">
                                            CV généré depuis le site
                                        </option>
                                    </FormSelect>
                                    <FieldDescription>
                                        Sans CV importé, le CV généré prend le
                                        relais. S'applique aussi au CV envoyé
                                        aux recruteurs.
                                    </FieldDescription>
                                    <FieldError>{errors.cv_source}</FieldError>
                                </Field>
                            </div>

                            <div className="grid gap-2 rounded-lg border p-4">
                                <p className="text-sm font-medium">
                                    Avis des visiteurs
                                </p>
                                <Field orientation="horizontal">
                                    <input
                                        type="hidden"
                                        name="testimonial_video_enabled"
                                        value="0"
                                    />
                                    <Checkbox
                                        id="testimonial_video_enabled"
                                        name="testimonial_video_enabled"
                                        value="1"
                                        defaultChecked={
                                            profile.testimonial_video_enabled
                                        }
                                    />
                                    <FieldLabel htmlFor="testimonial_video_enabled">
                                        Autoriser les avis vidéo
                                    </FieldLabel>
                                </Field>
                                <FieldDescription>
                                    Les visiteurs peuvent joindre une vidéo à
                                    leur avis ou se filmer depuis la page.
                                    Désactivé, le formulaire ne propose plus que
                                    le texte. Les vidéos déjà reçues restent
                                    affichées.
                                </FieldDescription>
                                <FieldError>
                                    {errors.testimonial_video_enabled}
                                </FieldError>
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

ProfileEdit.layout = {
    breadcrumbs: [{ title: 'Profil', href: profileEdit() }],
};
