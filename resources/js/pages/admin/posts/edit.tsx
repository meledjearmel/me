import { Form, Head, router } from '@inertiajs/react';
import { Eye, ImageOff } from 'lucide-react';
import PostController from '@/actions/App/Http/Controllers/Admin/PostController';
import FormPageHeader from '@/components/admin/form-page-header';
import PostForm from '@/components/admin/post-form';
import { Button } from '@/components/ui/button';
import { index as postsIndex } from '@/routes/admin/posts';
import type { Post } from '@/types';

export default function PostEdit({
    post,
    tags,
    seriesNames,
    previewUrl,
}: {
    post: Post;
    tags: string[];
    seriesNames: string[];
    /** Lien signé (72 h) vers l'article tel qu'il apparaîtra sur le site. */
    previewUrl: string;
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
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            asChild
                        >
                            <a
                                href={previewUrl}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Eye /> Aperçu
                            </a>
                        </Button>
                        {post.cover_url && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.delete(
                                        PostController.destroyCover.url(
                                            post.id,
                                        ),
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <ImageOff /> Retirer la couverture
                            </Button>
                        )}
                    </div>
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
        { title: 'Blog', href: postsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
