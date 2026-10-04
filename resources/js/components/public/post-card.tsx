import { Link } from '@inertiajs/react';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicPost } from '@/types';

export function formatPostDate(iso: string, locale: string): string {
    return new Date(iso).toLocaleDateString(
        locale === 'fr' ? 'fr-FR' : 'en-GB',
        { day: 'numeric', month: 'long', year: 'numeric' },
    );
}

/** Carte d'un article : couverture, date, temps de lecture, lectures, titre et résumé. */
export default function PostCard({
    post,
    large = false,
}: {
    post: PublicPost;
    large?: boolean;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();
    const href = path(`blog/${post.slug}`);

    return (
        <article
            className={`pub-post-card${large ? ' pub-post-card--large' : ''}`}
        >
            <Link
                href={href}
                className="pub-post-card__cover"
                tabIndex={-1}
                aria-hidden="true"
                data-cursor-label={t.projects.readNextCursor}
            >
                {post.cover_url ? (
                    <img src={post.cover_url} alt="" loading="lazy" />
                ) : (
                    <span className="pub-post-card__placeholder">
                        {post.title.charAt(0)}
                    </span>
                )}
            </Link>

            <div className="pub-post-card__body">
                <p className="pub-post-card__meta">
                    <time dateTime={post.published_at}>
                        {formatPostDate(post.published_at, locale)}
                    </time>
                    <span aria-hidden="true">·</span>
                    {t.blog.minutes(post.reading_minutes)}
                    {post.views_count > 0 && (
                        <>
                            <span aria-hidden="true">·</span>
                            {t.blog.views(post.views_count, locale)}
                        </>
                    )}
                </p>
                <h3 className="pub-post-card__title">
                    <Link href={href} prefetch>
                        {post.title}
                    </Link>
                </h3>
                {post.excerpt && (
                    <p className="pub-post-card__excerpt">{post.excerpt}</p>
                )}
                {post.tags.length > 0 && (
                    <ul className="pub-post-card__tags">
                        {post.tags.map((tag) => (
                            <li key={tag.slug}>{tag.name}</li>
                        ))}
                    </ul>
                )}
            </div>
        </article>
    );
}
