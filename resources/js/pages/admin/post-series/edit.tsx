import { Form, Head } from '@inertiajs/react';
import PostSeriesController from '@/actions/App/Http/Controllers/Admin/PostSeriesController';
import FormPageHeader from '@/components/admin/form-page-header';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { index as seriesIndex } from '@/routes/admin/post-series';
import type { PostSeries } from '@/types';

export default function PostSeriesEdit({ series }: { series: PostSeries }) {
    return (
        <>
            <Head title={`Modifier — ${series.name.fr}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier la série"
                    description={`${series.name.fr} · ${series.posts_count} article(s)`}
                    backHref={seriesIndex()}
                    backLabel="Séries d’articles"
                />

                <Form
                    {...PostSeriesController.update.form(series.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <TranslatableField
                                name="name"
                                label="Nom"
                                required
                                maxLength={80}
                                defaultValue={series.name}
                                errors={{
                                    fr: errors['name.fr'],
                                    en: errors['name.en'],
                                }}
                            />

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

PostSeriesEdit.layout = {
    breadcrumbs: [
        { title: 'Séries d’articles', href: seriesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
