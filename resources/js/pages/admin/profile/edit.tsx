import { Form, Head } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
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
                        <>
                            {profile.photo_url && (
                                <img
                                    src={profile.photo_url}
                                    alt={profile.name}
                                    className="size-24 rounded-full object-cover"
                                />
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="photo">Photo</Label>
                                <Input
                                    id="photo"
                                    name="photo"
                                    type="file"
                                    accept="image/*"
                                />
                                <InputError message={errors.photo} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={profile.name}
                                    required
                                />
                                <InputError message={errors.name} />
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
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email *</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={profile.email}
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Téléphone</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        defaultValue={profile.phone ?? ''}
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="location">Localisation</Label>
                                <Input
                                    id="location"
                                    name="location"
                                    defaultValue={profile.location ?? ''}
                                />
                                <InputError message={errors.location} />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="social_links_github">
                                        GitHub
                                    </Label>
                                    <Input
                                        id="social_links_github"
                                        name="social_links[github]"
                                        defaultValue={
                                            profile.social_links?.github ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors['social_links.github']}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="social_links_linkedin">
                                        LinkedIn
                                    </Label>
                                    <Input
                                        id="social_links_linkedin"
                                        name="social_links[linkedin]"
                                        defaultValue={
                                            profile.social_links?.linkedin ?? ''
                                        }
                                    />
                                    <InputError
                                        message={
                                            errors['social_links.linkedin']
                                        }
                                    />
                                </div>
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ProfileEdit.layout = {
    breadcrumbs: [{ title: 'Profil', href: profileEdit() }],
};
