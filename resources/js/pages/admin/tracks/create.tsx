import { Form, Head } from '@inertiajs/react';
import TrackController from '@/actions/App/Http/Controllers/Admin/TrackController';
import FormSelect from '@/components/admin/form-select';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { index as tracksIndex } from '@/routes/admin/tracks';
import type { MusicGenre } from '@/types';

export default function TrackCreate({ genres }: { genres: MusicGenre[] }) {
    return (
        <>
            <Head title="Nouvelle piste" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Nouvelle piste"
                    description="Ajouter un morceau au lecteur"
                />

                <Form {...TrackController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.music_genre_id}>
                                <FieldLabel htmlFor="music_genre_id">
                                    Registre *
                                </FieldLabel>
                                <FormSelect
                                    id="music_genre_id"
                                    name="music_genre_id"
                                    required
                                >
                                    {genres.map((genre) => (
                                        <option key={genre.id} value={genre.id}>
                                            {genre.label.fr}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.music_genre_id}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.title}>
                                <FieldLabel htmlFor="title">Titre *</FieldLabel>
                                <Input id="title" name="title" required />
                                <FieldError>{errors.title}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.artist}>
                                <FieldLabel htmlFor="artist">Artiste</FieldLabel>
                                <Input id="artist" name="artist" />
                                <FieldError>{errors.artist}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.audio}>
                                <FieldLabel htmlFor="audio">
                                    Fichier audio *
                                </FieldLabel>
                                <Input
                                    id="audio"
                                    name="audio"
                                    type="file"
                                    accept="audio/*"
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    MP3, OGG, WAV, M4A ou AAC, 30 Mo max.
                                </p>
                                <FieldError>{errors.audio}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.sort_order}>
                                <FieldLabel htmlFor="sort_order">
                                    Ordre
                                </FieldLabel>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={0}
                                />
                                <FieldError>{errors.sort_order}</FieldError>
                            </Field>

                            <Button disabled={processing}>Créer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TrackCreate.layout = {
    breadcrumbs: [
        { title: 'Pistes de musique', href: tracksIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
