import { Form, Head } from "@inertiajs/react";
import PostController from "@/actions/App/Http/Controllers/Admin/PostController";
import FormPageHeader from "@/components/admin/form-page-header";
import PostForm from "@/components/admin/post-form";
import { index as postsIndex } from "@/routes/admin/posts";

export default function PostCreate({ tags }: { tags: string[] }) {
    return (
        <>
            <Head title="Nouvel article" />

            <div className="space-y-6 p-4">
                <FormPageHeader
                    title="Nouvel article"
                    backHref={postsIndex()}
                    backLabel="Blog"
                />

                <Form {...PostController.store.form()}>
                    {({ processing, errors }) => (
                        <PostForm
                            tags={tags}
                            errors={errors}
                            processing={processing}
                            submitLabel="Créer l’article"
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

PostCreate.layout = {
    breadcrumbs: [
        { title: "Blog", href: postsIndex() },
        { title: "Nouveau", href: "" },
    ],
};
