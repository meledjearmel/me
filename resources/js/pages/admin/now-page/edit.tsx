import { Form, Head } from '@inertiajs/react';
import NowPageController from '@/actions/App/Http/Controllers/Admin/NowPageController';
import Heading from '@/components/heading';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { FieldDescription, FieldGroup } from '@/components/ui/field';
import { edit as pageEdit } from '@/routes/admin/now-page';

export default function NowPageEdit({
    content,
    updatedAt,
}: {
    content: { fr: string; en: string };
    updatedAt: string | null;
}) {
    return (
        <>
            <Head title="Page « Now »" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Page « Now »"
                    description={
                        updatedAt
                            ? `Mise à jour le ${new Date(updatedAt).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}`
                            : 'Ce sur quoi tu travailles en ce moment, affiché sur /now.'
                    }
                />

                <Form
                    {...NowPageController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <TranslatableField
                                name="now_content"
                                label="Texte"
                                textarea
                                mentions
                                maxLength={5000}
                                defaultValue={content}
                                errors={{
                                    fr: errors['now_content.fr'],
                                    en: errors['now_content.en'],
                                }}
                            />
                            <FieldDescription>
                                Une ligne vide sépare deux paragraphes ; des
                                lignes qui commencent par « - » forment une
                                liste. Sans texte français, la page est
                                masquée. La date de mise à jour avance quand le
                                texte change.
                            </FieldDescription>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

NowPageEdit.layout = {
    breadcrumbs: [{ title: 'Page « Now »', href: pageEdit() }],
};
