import { Head, Link } from '@inertiajs/react';
import { SquarePen } from 'lucide-react';
import PostTagController from '@/actions/App/Http/Controllers/Admin/PostTagController';
import DeleteButton from '@/components/admin/delete-button';
import { ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as pageIndex } from '@/routes/admin/post-tags';
import type { ListFilters, Paginated, PostTag } from '@/types';

export default function PostTagsIndex({
    tags,
    filters,
}: {
    tags: Paginated<PostTag>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Tags du blog" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Tags du blog"
                    description="Créés depuis les articles ; le nom anglais est traduit automatiquement, à corriger ici si besoin."
                />

                <ResourceList
                    paginator={tags}
                    filters={filters}
                    searchPlaceholder="Nom ou slug…"
                    columns={[
                        { header: 'Nom (FR)', cell: (row) => row.name.fr },
                        {
                            header: 'Nom (EN)',
                            cell: (row) =>
                                row.name.en === row.name.fr ? (
                                    <span className="inline-flex items-center gap-2">
                                        {row.name.en}
                                        <Badge variant="secondary">
                                            À traduire
                                        </Badge>
                                    </span>
                                ) : (
                                    row.name.en
                                ),
                        },
                        {
                            header: 'Slug',
                            className: 'font-mono text-xs',
                            cell: (row) => row.slug,
                        },
                        { header: 'Articles', cell: (row) => row.posts_count },
                    ]}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={PostTagController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={PostTagController.destroy.url(row.id)}
                                confirmMessage={`Supprimer le tag "${row.name.fr}" ? Il sera retiré de ${row.posts_count} article(s).`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

PostTagsIndex.layout = {
    breadcrumbs: [
        { title: 'Blog', href: postsIndex() },
        { title: 'Tags', href: pageIndex() },
    ],
};
