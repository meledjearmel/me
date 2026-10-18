import { Head, Link } from '@inertiajs/react';
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from '@/components/admin/status-menu';
import { Eye, Plus, SquarePen } from 'lucide-react';
import PostController from '@/actions/App/Http/Controllers/Admin/PostController';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import AiDraftBadge from '@/components/admin/ai-draft-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/posts';
import type { ListFilters, Paginated, Post } from '@/types';
import ActionTooltip from '@/components/admin/action-tooltip';

function publicationLabel(post: Post): {
    label: string;
    variant: 'default' | 'secondary' | 'outline';
} {
    if (post.status === 'draft') {
        return { label: 'Brouillon', variant: 'secondary' };
    }

    if (post.published_at && new Date(post.published_at) > new Date()) {
        return { label: 'Programmé', variant: 'outline' };
    }

    return { label: 'Publié', variant: 'default' };
}

export default function PostsIndex({
    posts,
    filters,
}: {
    posts: Paginated<Post>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Blog" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading title="Blog" />
                    <Button asChild>
                        <Link href={PostController.create()}>
                            <Plus data-icon="inline-start" /> Nouvel article
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={posts}
                    filters={filters}
                    searchPlaceholder="Titre ou slug…"
                    columns={[
                        {
                            header: 'Titre',
                            cell: (row) => (
                                <span className="font-medium">
                                    {row.title.fr}
                                    <AiDraftBadge
                                        draftedBy={row.drafted_by}
                                        status={row.status}
                                    />
                                    {!row.title.en && (
                                        <Badge
                                            variant="outline"
                                            className="ml-2"
                                        >
                                            FR seul
                                        </Badge>
                                    )}
                                </span>
                            ),
                        },
                        {
                            header: 'Statut',
                            fit: true,
                            cell: (row) => {
                                const { label, variant } =
                                    publicationLabel(row);

                                return (
                                    <StatusMenu
                                        type="post"
                                        id={row.id}
                                        status={row.status}
                                        options={[
                                            {
                                                value: 'published',
                                                label:
                                                    row.status === 'published'
                                                        ? label
                                                        : 'Publié',
                                            },
                                            {
                                                value: 'draft',
                                                label: 'Brouillon',
                                            },
                                        ]}
                                        variants={{
                                            ...PUBLICATION_VARIANTS,
                                            published: variant,
                                        }}
                                    />
                                );
                            },
                        },
                        {
                            header: 'Publication',
                            cell: (row) =>
                                row.published_at
                                    ? new Date(
                                          row.published_at,
                                      ).toLocaleDateString('fr-FR')
                                    : '—',
                        },
                        {
                            header: 'Lecture',
                            cell: (row) => `${row.reading_minutes} min`,
                        },
                        {
                            header: 'Mis en avant',
                            cell: (row) => (row.is_featured ? 'Oui' : '—'),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[
                                    { value: 'published', label: 'Publié' },
                                    { value: 'draft', label: 'Brouillon' },
                                ]}
                            />
                            <FilterSelect
                                state={state}
                                name="is_featured"
                                value={filters.is_featured}
                                label="Mise en avant"
                                options={[
                                    { value: '1', label: 'Mis en avant' },
                                    { value: '0', label: 'Non mis en avant' },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={PostController.show(row.id)}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={PostController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={PostController.destroy.url(row.id)}
                                confirmMessage={`Supprimer l’article "${row.title.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

PostsIndex.layout = {
    breadcrumbs: [{ title: 'Blog', href: pageIndex() }],
};
