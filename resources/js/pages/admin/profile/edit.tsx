import { Form, Head, router } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import Heading from '@/components/heading';
import TranslatableField from '@/components/translatable-field';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit as profileEdit } from '@/routes/admin/profile';
import type { Profile } from '@/types';

export default function ProfileEdit({ profile }: { profile: Profile }) {
    return (
        <>
            <Head title="Profil" />

            <div className="max-w-2xl space-y-6 p-4">
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
                                <Label htmlFor="music">Bande audio du site</Label>
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

                            <div className="grid gap-6 sm:grid-cols-2">
                                {(['fr', 'en'] as const).map((locale) => {
                                    const file = profile.cv_files?.[locale];
                                    const field = `cv_file_${locale}` as const;

                                    return (
                                        <div
                                            key={locale}
                                            className="grid content-start gap-2"
                                        >
                                            <Label htmlFor={field}>
                                                CV en PDF (
                                                {locale.toUpperCase()})
                                            </Label>
                                            {file && (
                                                <div className="flex items-center justify-between gap-2 text-sm">
                                                    <a
                                                        href={file.url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="truncate underline"
                                                    >
                                                        {file.file_name}
                                                    </a>
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() => {
                                                            if (
                                                                confirm(
                                                                    'Retirer ce CV ? Le CV sera de nouveau généré automatiquement.',
                                                                )
                                                            ) {
                                                                router.delete(
                                                                    ProfileController.destroyCv.url(
                                                                        locale,
                                                                    ),
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        Retirer
                                                    </Button>
                                                </div>
                                            )}
                                            <Input
                                                id={field}
                                                name={field}
                                                type="file"
                                                accept="application/pdf"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                {file
                                                    ? 'Choisir un fichier pour remplacer le CV. '
                                                    : 'Sans fichier, le CV est généré automatiquement. '}
                                                PDF, 10 Mo max.
                                            </p>
                                            <FieldError>
                                                {errors[field]}
                                            </FieldError>
                                        </div>
                                    );
                                })}
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
