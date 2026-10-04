import { Form, Head } from '@inertiajs/react';
import PostTagController from '@/actions/App/Http/Controllers/Admin/PostTagController';
import FormPageHeader from '@/components/admin/form-page-header';
import TranslatableField from '@/components/translatable-field';
import { FieldDescription, FieldGroup } from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import { index as tagsIndex } from '@/routes/admin/post-tags';
import type { PostTag } from '@/types';

export default function PostTagEdit({ tag }: { tag: PostTag }) {
    return (
        <>
            <Head title={`Modifier — ${tag.name.fr}`} />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Modifier le tag"
                    description={`${tag.name.fr} · ${tag.posts_count} article(s)`}
                    backHref={tagsIndex()}
                    backLabel="Tags du blog"
                />

                <Form
                    {...PostTagController.update.form(tag.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <TranslatableField
                                name="name"
                                label="Nom"
                                required
                                defaultValue={tag.name}
                                errors={{
                                    fr: errors['name.fr'],
                                    en: errors['name.en'],
                                }}
                            />
                            <FieldDescription>
                                Le lien de filtre (
                                <code className="font-mono">
                                    ?tag={tag.slug}
                                </code>
                                ) ne change pas.
                            </FieldDescription>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

PostTagEdit.layout = {
    breadcrumbs: [
        { title: 'Tags du blog', href: tagsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
