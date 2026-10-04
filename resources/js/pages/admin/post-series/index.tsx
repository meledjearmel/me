import { Head, Link } from '@inertiajs/react';
import { SquarePen } from 'lucide-react';
import PostSeriesController from '@/actions/App/Http/Controllers/Admin/PostSeriesController';
import DeleteButton from '@/components/admin/delete-button';
import { ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as pageIndex } from '@/routes/admin/post-series';
import type { ListFilters, Paginated, PostSeries } from '@/types';

export default function PostSeriesIndex({
    series,
    filters,
}: {
    series: Paginated<PostSeries>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Séries d’articles" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Séries d’articles"
                    description="Créées depuis le formulaire d’un article (champ « Série ») ; leur nom anglais se corrige ici."
                />

                <ResourceList
                    paginator={series}
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
                        { header: 'Articles', cell: (row) => row.posts_count },
                    ]}
                    actions={(row) => (
                        <>
                            <Button variant="ghost" size="icon" asChild>
                                <Link
                                    href={PostSeriesController.edit(row.id)}
                                    aria-label="Modifier"
                                >
                                    <SquarePen />
                                </Link>
                            </Button>
                            <DeleteButton
                                href={PostSeriesController.destroy.url(row.id)}
                                confirmMessage={`Supprimer la série "${row.name.fr}" ? Ses ${row.posts_count} article(s) restent publiés, hors série.`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

PostSeriesIndex.layout = {
    breadcrumbs: [
        { title: 'Blog', href: postsIndex() },
        { title: 'Séries', href: pageIndex() },
    ],
};
