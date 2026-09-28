import { Form, Head } from '@inertiajs/react';
import MusicGenreController from '@/actions/App/Http/Controllers/Admin/MusicGenreController';
import FormPageHeader from '@/components/admin/form-page-header';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { index as genresIndex } from '@/routes/admin/music-genres';
import type { MusicGenre } from '@/types';

export default function MusicGenreEdit({ genre }: { genre: MusicGenre }) {
    return (
        <>
            <Head title={`Modifier — ${genre.label.fr}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier le registre"
                    description={genre.label.fr}
                    backHref={genresIndex()}
                    backLabel="Registres de musique"
                />

                <Form
                    {...MusicGenreController.update.form(genre.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input
                                    id="key"
                                    name="key"
                                    defaultValue={genre.key}
                                    required
                                />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
                                defaultValue={genre.label}
                                errors={{
                                    fr: errors['label.fr'],
                                    en: errors['label.en'],
                                }}
                            />

                            <Field data-invalid={!!errors.sort_order}>
                                <FieldLabel htmlFor="sort_order">
                                    Ordre
                                </FieldLabel>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={genre.sort_order}
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

MusicGenreEdit.layout = {
    breadcrumbs: [
        { title: 'Registres de musique', href: genresIndex() },
        { title: 'Modifier', href: '' },
    ],
};
