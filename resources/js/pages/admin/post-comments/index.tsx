import { Head, router } from '@inertiajs/react';
import { Check, ExternalLink, X } from 'lucide-react';
import DeleteButton from '@/components/admin/delete-button';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    destroy,
    index as pageIndex,
    update,
} from '@/routes/admin/post-comments';
import type { ListFilters, Paginated, PostComment } from '@/types';

const STATUS_VARIANT = {
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive',
} as const;

const STATUS_LABEL = {
    pending: 'En attente',
    approved: 'Publié',
    rejected: 'Refusé',
} as const;

function moderate(comment: PostComment, status: PostComment['status']): void {
    router.patch(update.url(comment.id), { status }, { preserveScroll: true });
}

export default function PostCommentsIndex({
    comments,
    filters,
}: {
    comments: Paginated<PostComment>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Commentaires" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Commentaires"
                    description="Modération des commentaires du blog : seuls les commentaires publiés apparaissent sous les articles"
                />

                <ResourceList
                    paginator={comments}
                    filters={filters}
                    searchPlaceholder="Auteur, email, commentaire…"
                    columns={[
                        {
                            header: 'Auteur',
                            cell: (row) => (
                                <div className="grid">
                                    <span>{row.author_name}</span>
                                    {row.author_email && (
                                        <span className="text-xs text-muted-foreground">
                                            {row.author_email}
                                        </span>
                                    )}
                                </div>
                            ),
                        },
                        {
                            header: 'Commentaire',
                            cell: (row) => (
                                <div className="grid max-w-md gap-1">
                                    <p className="line-clamp-3 whitespace-pre-line">
                                        {row.body}
                                    </p>
                                    {row.post && (
                                        <a
                                            href={`/${row.locale}/blog/${row.post.slug}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:underline"
                                        >
                                            {row.post.title.fr}
                                            <ExternalLink className="size-3" />
                                        </a>
                                    )}
                                </div>
                            ),
                        },
                        {
                            header: 'Statut',
                            cell: (row) => (
                                <Badge variant={STATUS_VARIANT[row.status]}>
                                    {STATUS_LABEL[row.status]}
                                </Badge>
                            ),
                        },
                        {
                            header: 'Reçu le',
                            cell: (row) =>
                                new Date(row.created_at).toLocaleDateString(
                                    'fr-FR',
                                ),
                        },
                    ]}
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="status"
                            value={filters.status}
                            label="Statut"
                            options={[
                                { value: 'pending', label: 'En attente' },
                                { value: 'approved', label: 'Publié' },
                                { value: 'rejected', label: 'Refusé' },
                            ]}
                        />
                    )}
                    actions={(row) => (
                        <>
                            {row.status !== 'approved' && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Publier"
                                    title="Publier"
                                    onClick={() => moderate(row, 'approved')}
                                >
                                    <Check />
                                </Button>
                            )}
                            {row.status !== 'rejected' && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Refuser"
                                    title="Refuser"
                                    onClick={() => moderate(row, 'rejected')}
                                >
                                    <X />
                                </Button>
                            )}
                            <DeleteButton
                                href={destroy.url(row.id)}
                                confirmMessage={`Supprimer le commentaire de "${row.author_name}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

PostCommentsIndex.layout = {
    breadcrumbs: [{ title: 'Commentaires', href: pageIndex() }],
};
