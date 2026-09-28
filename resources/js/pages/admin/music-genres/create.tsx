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

export default function MusicGenreCreate() {
    return (
        <>
            <Head title="Nouveau registre" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouveau registre"
                    description="Ajouter un onglet au lecteur de musique"
                    backHref={genresIndex()}
                    backLabel="Registres de musique"
                />

                <Form
                    {...MusicGenreController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.key}>
                                <FieldLabel htmlFor="key">Clé *</FieldLabel>
                                <Input
                                    id="key"
                                    name="key"
                                    placeholder="lofi"
                                    required
                                />
                                <FieldError>{errors.key}</FieldError>
                            </Field>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
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

MusicGenreCreate.layout = {
    breadcrumbs: [
        { title: 'Registres de musique', href: genresIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
