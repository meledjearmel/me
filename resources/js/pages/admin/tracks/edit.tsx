import { Form, Head } from '@inertiajs/react';
import TrackController from '@/actions/App/Http/Controllers/Admin/TrackController';
import FormSelect from '@/components/admin/form-select';
import FormPageHeader from '@/components/admin/form-page-header';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { index as tracksIndex } from '@/routes/admin/tracks';
import type { MusicGenre, Track } from '@/types';

export default function TrackEdit({
    track,
    genres,
}: {
    track: Track;
    genres: MusicGenre[];
}) {
    return (
        <>
            <Head title={`Modifier — ${track.title}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la piste"
                    description={track.title}
                    backHref={tracksIndex()}
                    backLabel="Pistes de musique"
                />

                <Form
                    {...TrackController.update.form(track.id)}
                    className="space-y-6"
                >
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
                                    defaultValue={track.music_genre_id}
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
                                <Input
                                    id="title"
                                    name="title"
                                    defaultValue={track.title}
                                    required
                                />
                                <FieldError>{errors.title}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.artist}>
                                <FieldLabel htmlFor="artist">
                                    Artiste
                                </FieldLabel>
                                <Input
                                    id="artist"
                                    name="artist"
                                    defaultValue={track.artist ?? ''}
                                />
                                <FieldError>{errors.artist}</FieldError>
                            </Field>

                            <Field data-invalid={!!errors.audio}>
                                <FieldLabel htmlFor="audio">
                                    Fichier audio
                                </FieldLabel>
                                {track.audio_url && (
                                    <audio
                                        src={track.audio_url}
                                        controls
                                        className="h-9 w-full"
                                    />
                                )}
                                <Input
                                    id="audio"
                                    name="audio"
                                    type="file"
                                    accept="audio/*"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Laisser vide pour garder le fichier actuel.
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
                                    defaultValue={track.sort_order}
                                />
                                <FieldError>{errors.sort_order}</FieldError>
                            </Field>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TrackEdit.layout = {
    breadcrumbs: [
        { title: 'Pistes de musique', href: tracksIndex() },
        { title: 'Modifier', href: '' },
    ],
};
