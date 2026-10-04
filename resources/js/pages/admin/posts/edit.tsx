import { Form, Head, router } from "@inertiajs/react";
import { ImageOff } from "lucide-react";
import PostController from "@/actions/App/Http/Controllers/Admin/PostController";
import FormPageHeader from "@/components/admin/form-page-header";
import PostForm from "@/components/admin/post-form";
import { Button } from "@/components/ui/button";
import { index as postsIndex } from "@/routes/admin/posts";
import type { Post } from "@/types";

export default function PostEdit({
    post,
    tags,
    seriesNames,
}: {
    post: Post;
    tags: string[];
    seriesNames: string[];
}) {
    return (
        <>
            <Head title={`Modifier — ${post.title.fr}`} />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <FormPageHeader
                        title="Modifier l’article"
                        description={post.title.fr}
                        backHref={postsIndex()}
                        backLabel="Blog"
                    />
                    {post.cover_url && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                router.delete(
                                    PostController.destroyCover.url(post.id),
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <ImageOff /> Retirer la couverture
                        </Button>
                    )}
                </div>

                <Form
                    {...PostController.update.form(post.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <PostForm
                            post={post}
                            tags={tags}
                            seriesNames={seriesNames}
                            errors={errors}
                            processing={processing}
                            submitLabel="Enregistrer"
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

PostEdit.layout = {
    breadcrumbs: [
        { title: "Blog", href: postsIndex() },
        { title: "Modifier", href: "" },
    ],
};
