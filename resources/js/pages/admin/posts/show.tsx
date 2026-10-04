import { router } from '@inertiajs/react';
import { Check, ExternalLink, Eye, X } from 'lucide-react';
import DeleteButton from '@/components/admin/delete-button';
import ShowPage, { formatDate } from '@/components/admin/show-page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    destroy as destroyComment,
    update as updateComment,
} from '@/routes/admin/post-comments';
import { edit, index as postsIndex } from '@/routes/admin/posts';
import type { Post, PostComment } from '@/types';

type ReactionType = 'like' | 'love' | 'fire' | 'idea' | 'think';

const REACTIONS: Record<ReactionType, { emoji: string; label: string }> = {
    like: { emoji: '👍', label: 'J’aime' },
    love: { emoji: '❤️', label: 'J’adore' },
    fire: { emoji: '🔥', label: 'Impressionnant' },
    idea: { emoji: '💡', label: 'Instructif' },
    think: { emoji: '💭', label: 'Ça fait réfléchir' },
};

const COMMENT_STATUS = {
    pending: { label: 'En attente', variant: 'secondary' },
    approved: { label: 'Publié', variant: 'default' },
    rejected: { label: 'Refusé', variant: 'destructive' },
} as const;

const number = new Intl.NumberFormat('fr-FR');

function moderate(comment: PostComment, status: PostComment['status']): void {
    router.patch(
        updateComment.url(comment.id),
        { status },
        { preserveScroll: true },
    );
}

export default function PostShow({
    post,
    reactions,
    comments,
    previewUrl,
}: {
    post: Post & { is_live: boolean };
    reactions: Record<ReactionType, number>;
    comments: Omit<PostComment, 'post'>[];
    /** Lien signé (72 h) vers l'article tel qu'il apparaîtra sur le site. */
    previewUrl: string;
}) {
    const totalReactions = Object.values(reactions).reduce(
        (sum, count) => sum + count,
        0,
    );
    const pending = comments.filter(
        (comment) => comment.status === 'pending',
    ).length;
    const tags = (post.tags as string[]).join(', ');

    return (
        <ShowPage
            title={post.title.fr ?? post.slug}
            description={post.title.en}
            badge={
                <Badge variant={post.is_live ? 'default' : 'secondary'}>
                    {post.is_live
                        ? 'En ligne'
                        : post.status === 'published'
                          ? 'Programmé'
                          : 'Brouillon'}
                </Badge>
            }
            backHref={postsIndex()}
            backLabel="Blog"
            editHref={edit(post.id)}
            actions={
                <Button variant="outline" asChild>
                    <a
                        href={
                            post.is_live ? `/fr/blog/${post.slug}` : previewUrl
                        }
                        target="_blank"
                        rel="noreferrer"
                    >
                        {post.is_live ? (
                            <ExternalLink data-icon="inline-start" />
                        ) : (
                            <Eye data-icon="inline-start" />
                        )}
                        {post.is_live ? 'Voir sur le site' : 'Aperçu'}
                    </a>
                </Button>
            }
            sections={[
                {
                    title: 'Audience',
                    content: (
                        <dl className="grid gap-4 sm:grid-cols-3">
                            {[
                                ['Lectures', post.views_count ?? 0],
                                ['Réactions', totalReactions],
                                [
                                    'Commentaires',
                                    comments.length,
                                    pending > 0
                                        ? `${pending} en attente`
                                        : undefined,
                                ],
                            ].map(([label, value, hint]) => (
                                <div
                                    key={label}
                                    className="rounded-lg border p-4"
                                >
                                    <dt className="text-sm text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="mt-1 text-2xl font-semibold tabular-nums">
                                        {number.format(Number(value))}
                                    </dd>
                                    {hint && (
                                        <p className="text-xs text-muted-foreground">
                                            {hint}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </dl>
                    ),
                },
                {
                    title: 'Réactions',
                    description: 'Données sans compte, une fois par lecteur',
                    content: (
                        <ul className="grid gap-2 sm:grid-cols-5">
                            {(Object.keys(REACTIONS) as ReactionType[]).map(
                                (type) => (
                                    <li
                                        key={type}
                                        className="flex items-center gap-3 rounded-lg border px-3 py-2"
                                    >
                                        <span
                                            className="text-xl"
                                            aria-hidden="true"
                                        >
                                            {REACTIONS[type].emoji}
                                        </span>
                                        <span className="grid">
                                            <span className="font-medium tabular-nums">
                                                {number.format(reactions[type])}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {REACTIONS[type].label}
                                            </span>
                                        </span>
                                    </li>
                                ),
                            )}
                        </ul>
                    ),
                },
                {
                    title: 'Commentaires',
                    description:
                        'Seuls les commentaires publiés apparaissent sous l’article',
                    content:
                        comments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Aucun commentaire pour l’instant.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {comments.map((comment) => (
                                    <li
                                        key={comment.id}
                                        className="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                    >
                                        <div className="grid min-w-0 flex-1 gap-1">
                                            <p className="flex flex-wrap items-center gap-2 text-sm">
                                                <span className="font-medium">
                                                    {comment.author_name}
                                                </span>
                                                {comment.author_email && (
                                                    <span className="text-muted-foreground">
                                                        {comment.author_email}
                                                    </span>
                                                )}
                                                <Badge
                                                    variant={
                                                        COMMENT_STATUS[
                                                            comment.status
                                                        ].variant
                                                    }
                                                >
                                                    {
                                                        COMMENT_STATUS[
                                                            comment.status
                                                        ].label
                                                    }
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {formatDate(
                                                        comment.created_at,
                                                    )}{' '}
                                                    ·{' '}
                                                    {comment.locale.toUpperCase()}
                                                </span>
                                            </p>
                                            <p className="text-sm whitespace-pre-line">
                                                {comment.body}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 items-center">
                                            {comment.status !== 'approved' && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Publier"
                                                    title="Publier"
                                                    onClick={() =>
                                                        moderate(
                                                            comment as PostComment,
                                                            'approved',
                                                        )
                                                    }
                                                >
                                                    <Check />
                                                </Button>
                                            )}
                                            {comment.status !== 'rejected' && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Refuser"
                                                    title="Refuser"
                                                    onClick={() =>
                                                        moderate(
                                                            comment as PostComment,
                                                            'rejected',
                                                        )
                                                    }
                                                >
                                                    <X />
                                                </Button>
                                            )}
                                            <DeleteButton
                                                href={destroyComment.url(
                                                    comment.id,
                                                )}
                                                confirmMessage={`Supprimer le commentaire de "${comment.author_name}" ?`}
                                            />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ),
                },
                {
                    title: 'Article',
                    items: [
                        { label: 'Adresse', value: `/blog/${post.slug}` },
                        {
                            label: 'Publication',
                            value: post.published_at
                                ? formatDate(post.published_at)
                                : null,
                        },
                        {
                            label: 'Temps de lecture',
                            value: `${post.reading_minutes} min`,
                        },
                        {
                            label: 'À la une',
                            value: post.is_featured ? 'Oui' : 'Non',
                        },
                        { label: 'Tags', value: tags },
                        {
                            label: 'Série',
                            value: post.series
                                ? `${post.series}${post.series_position ? ` · partie ${post.series_position}` : ''}`
                                : null,
                        },
                        {
                            label: 'Extrait',
                            value: post.excerpt?.fr,
                            wide: true,
                        },
                        {
                            label: 'Couverture',
                            value: post.cover_url ? (
                                <img
                                    src={post.cover_url}
                                    alt=""
                                    className="max-h-56 rounded-md object-cover"
                                />
                            ) : null,
                            wide: true,
                        },
                    ],
                },
                {
                    title: 'Contenu',
                    description: post.body.en
                        ? 'Version française (traduit en anglais)'
                        : 'Version française (pas encore traduit)',
                    content: (
                        <div
                            className="post-content max-w-3xl"
                            dangerouslySetInnerHTML={{
                                __html: post.body.fr ?? '',
                            }}
                        />
                    ),
                },
            ]}
        />
    );
}

PostShow.layout = {
    breadcrumbs: [
        { title: 'Blog', href: postsIndex() },
        { title: 'Article', href: '' },
    ],
};
