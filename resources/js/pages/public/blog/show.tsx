import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { RefObject } from 'react';
import NewsletterSignup from '@/components/public/newsletter-signup';
import PageHero from '@/components/public/page-hero';
import PostCard, { formatPostDate } from '@/components/public/post-card';
import PostComments from '@/components/public/post-comments';
import type { PublicComment } from '@/components/public/post-comments';
import PostReactions from '@/components/public/post-reactions';
import type { ReactionSummary } from '@/components/public/post-reactions';
import { ProjectCta } from '@/components/public/project-parts';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicPost, PublicProfile } from '@/types';

/** Titre du sommaire à surligner : le dernier passé sous le haut de l'écran. */
function useActiveHeading(ids: string[]): string | null {
    const [active, setActive] = useState<string | null>(ids[0] ?? null);

    useEffect(() => {
        if (ids.length === 0) {
            return;
        }

        const onScroll = () => {
            let current = ids[0];

            for (const id of ids) {
                const element = document.getElementById(id);

                if (element && element.getBoundingClientRect().top < 160) {
                    current = id;
                }
            }

            setActive(current);
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, [ids]);

    return active;
}

/** Ajoute un bouton « copier » à chaque bloc de code de l'article. */
function useCopyButtons(
    article: RefObject<HTMLElement | null>,
    labels: { copy: string; copied: string },
    html: string,
): void {
    useEffect(() => {
        const blocks = article.current?.querySelectorAll('pre') ?? [];
        const buttons: HTMLButtonElement[] = [];

        blocks.forEach((block) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'post-content__copy';
            button.textContent = labels.copy;
            button.addEventListener('click', () => {
                void navigator.clipboard
                    ?.writeText(block.querySelector('code')?.innerText ?? '')
                    .then(() => {
                        button.textContent = labels.copied;
                        window.setTimeout(() => {
                            button.textContent = labels.copy;
                        }, 1600);
                    });
            });
            block.appendChild(button);
            buttons.push(button);
        });

        return () => buttons.forEach((button) => button.remove());
    }, [article, labels.copy, labels.copied, html]);
}

/** Part de l'article déjà lue, de 0 à 1. */
function useReadingProgress(article: RefObject<HTMLElement | null>): number {
    const [progress, setProgress] = useState(0);

    useEffect(() => {
        let frame = 0;

        const measure = () => {
            frame = 0;
            const element = article.current;

            if (!element) {
                return;
            }

            const { top, height } = element.getBoundingClientRect();
            const readable = height - window.innerHeight;

            if (readable <= 0) {
                setProgress(top <= 0 ? 1 : 0);

                return;
            }

            setProgress(Math.min(1, Math.max(0, -top / readable)));
        };

        const onScroll = () => {
            if (frame === 0) {
                frame = window.requestAnimationFrame(measure);
            }
        };

        measure();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);

        return () => {
            window.cancelAnimationFrame(frame);
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);
        };
    }, [article]);

    return progress;
}

type AdjacentPost = { slug: string; title: string } | null;

type PostSeries = {
    name: string;
    parts: {
        slug: string;
        title: string;
        position: number | null;
        current: boolean;
    }[];
};

/** Encadré d'une série : toutes ses parties publiées, puis précédente / suivante. */
function SeriesBox({ series }: { series: PostSeries }) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const index = series.parts.findIndex((part) => part.current);
    const previous = index > 0 ? series.parts[index - 1] : null;
    const next = series.parts[index + 1] ?? null;

    return (
        <nav className="pub-series" aria-label={t.blog.seriesLabel}>
            <p className="pub-series__eyebrow">
                {t.blog.seriesPart(index + 1, series.parts.length)}
            </p>
            <p className="pub-series__name">{series.name}</p>
            <ol>
                {series.parts.map((part) => (
                    <li key={part.slug}>
                        {part.current ? (
                            <span aria-current="page">{part.title}</span>
                        ) : (
                            <Link href={path(`blog/${part.slug}`)}>
                                {part.title}
                            </Link>
                        )}
                    </li>
                ))}
            </ol>
            {(previous || next) && (
                <div className="pub-series__pager">
                    {previous ? (
                        <Link href={path(`blog/${previous.slug}`)}>
                            {t.blog.seriesPrevious}
                        </Link>
                    ) : (
                        <span />
                    )}
                    {next && (
                        <Link href={path(`blog/${next.slug}`)}>
                            {t.blog.seriesNext}
                        </Link>
                    )}
                </div>
            )}
        </nav>
    );
}

export default function BlogShow({
    post,
    relatedPosts,
    series,
    adjacent,
    reactions,
    comments,
    preview = false,
}: {
    post: PublicPost;
    relatedPosts: PublicPost[];
    series: PostSeries | null;
    /** Articles publiés juste avant et juste après, par date. */
    adjacent: { previous: AdjacentPost; next: AdjacentPost };
    /** Null quand les réactions sont désactivées ou sur un aperçu. */
    reactions: ReactionSummary | null;
    /** Commentaires approuvés ; null quand ils sont désactivés ou sur un aperçu. */
    comments: PublicComment[] | null;
    /** Brouillon ou article programmé, ouvert depuis un lien d'aperçu signé. */
    preview?: boolean;
}) {
    const t = useTranslations();
    const articleRef = useRef<HTMLElement>(null);
    useCopyButtons(
        articleRef,
        { copy: t.blog.copyCode, copied: t.blog.codeCopied },
        post.body ?? '',
    );
    const progress = useReadingProgress(articleRef);
    const locale = useLocale();
    const path = useLocalizedPath();
    const { props } = usePage<{ siteUrl: string; profile: PublicProfile }>();
    const toc = post.toc ?? [];
    const active = useActiveHeading(toc.map((item) => item.id));
    const untranslated = post.content_locale !== locale;
    const description = post.excerpt ?? t.seo.blog;
    // Sans couverture, une image générée avec le titre de l'article.
    const shareImage =
        post.cover_url ??
        `${props.siteUrl.replace(/\/$/, '')}/${locale}/blog/${post.slug}/share.png`;

    return (
        <>
            <Seo
                title={post.title}
                description={description}
                image={shareImage}
                type="article"
                breadcrumbs={[
                    [t.nav.blog, `/${locale}/blog`],
                    [post.title, `/${locale}/blog/${post.slug}`],
                ]}
                jsonLd={{
                    '@type': 'BlogPosting',
                    headline: post.title,
                    description,
                    image: shareImage,
                    datePublished: post.published_at,
                    dateModified: post.updated_at,
                    inLanguage: post.content_locale,
                    timeRequired: `PT${post.reading_minutes}M`,
                    keywords: post.tags.map((tag) => tag.name).join(', '),
                    author: {
                        '@type': 'Person',
                        name: props.profile.name,
                        url: `${props.siteUrl.replace(/\/$/, '')}/${locale}/about`,
                    },
                    mainEntityOfPage: `${props.siteUrl.replace(/\/$/, '')}/${locale}/blog/${post.slug}`,
                }}
            >
                <meta
                    property="article:published_time"
                    content={post.published_at}
                />
                <meta
                    property="article:modified_time"
                    content={post.updated_at}
                />
                {preview && <meta name="robots" content="noindex, nofollow" />}
                {post.tags.map((tag) => (
                    <meta
                        key={tag.slug}
                        property="article:tag"
                        content={tag.name}
                    />
                ))}
            </Seo>

            <PublicShell overHero>
                <div
                    className="pub-post__progress"
                    role="progressbar"
                    aria-label={t.blog.readingProgress}
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={Math.round(progress * 100)}
                >
                    <span style={{ transform: `scaleX(${progress})` }} />
                </div>
                <div className="pub-project pub-post">
                    <PageHero
                        id="pub-post-title"
                        eyebrow={
                            post.tags.map((tag) => tag.name).join(' · ') ||
                            t.blog.title
                        }
                        title={post.title}
                        aside={
                            <dl className="pub-meta pub-reveal pub-reveal--late">
                                <div>
                                    <dt>{t.blog.publishedLabel}</dt>
                                    <dd>
                                        <time dateTime={post.published_at}>
                                            {formatPostDate(
                                                post.published_at,
                                                locale,
                                            )}
                                        </time>
                                    </dd>
                                </div>
                                <div>
                                    <dt>{t.blog.readingLabel}</dt>
                                    <dd>
                                        {t.blog.minutes(post.reading_minutes)}
                                    </dd>
                                </div>
                            </dl>
                        }
                        lead={
                            <>
                                {post.excerpt && <p>{post.excerpt}</p>}
                                <Link
                                    href={path('blog')}
                                    className="pub-page-hero__back"
                                >
                                    {t.blog.back}
                                </Link>
                            </>
                        }
                    />

                    {post.cover_url && (
                        <div className="pub-plate site-wrap">
                            <img src={post.cover_url} alt={post.title} />
                        </div>
                    )}

                    <section className="pub-post__main">
                        <div
                            className={`site-wrap pub-post__grid${toc.length > 1 ? '' : ' pub-post__grid--single'}`}
                        >
                            {toc.length > 1 && (
                                <nav
                                    className="pub-post__toc"
                                    aria-label={t.blog.toc}
                                >
                                    <p>{t.blog.toc}</p>
                                    <ol>
                                        {toc.map((item) => (
                                            <li
                                                key={item.id}
                                                className={
                                                    item.level === 3
                                                        ? 'is-sub'
                                                        : undefined
                                                }
                                            >
                                                <a
                                                    href={`#${item.id}`}
                                                    aria-current={
                                                        active === item.id
                                                            ? 'location'
                                                            : undefined
                                                    }
                                                >
                                                    {item.text}
                                                </a>
                                            </li>
                                        ))}
                                    </ol>
                                </nav>
                            )}

                            <div className="pub-post__article">
                                {preview && (
                                    <p
                                        className="pub-post__notice"
                                        role="status"
                                    >
                                        {t.blog.previewNotice}
                                    </p>
                                )}
                                {untranslated && t.blog.onlyFrench && (
                                    <p className="pub-post__notice">
                                        {t.blog.onlyFrench}
                                    </p>
                                )}
                                {series && <SeriesBox series={series} />}
                                <article
                                    ref={articleRef}
                                    className="post-content"
                                    lang={post.content_locale}
                                    dangerouslySetInnerHTML={{
                                        __html: post.body ?? '',
                                    }}
                                />
                                {reactions && (
                                    <PostReactions
                                        slug={post.slug}
                                        initial={reactions}
                                    />
                                )}
                                {comments && (
                                    <PostComments
                                        slug={post.slug}
                                        comments={comments}
                                    />
                                )}
                                {(adjacent.previous || adjacent.next) && (
                                    <nav
                                        className="pub-post__adjacent"
                                        aria-label={t.blog.adjacentLabel}
                                    >
                                        {adjacent.previous && (
                                            <Link
                                                href={path(
                                                    `blog/${adjacent.previous.slug}`,
                                                )}
                                                rel="prev"
                                            >
                                                <span>
                                                    {t.blog.previousPost}
                                                </span>
                                                {adjacent.previous.title}
                                            </Link>
                                        )}
                                        {adjacent.next && (
                                            <Link
                                                href={path(
                                                    `blog/${adjacent.next.slug}`,
                                                )}
                                                rel="next"
                                                className="pub-post__adjacent-next"
                                            >
                                                <span>{t.blog.nextPost}</span>
                                                {adjacent.next.title}
                                            </Link>
                                        )}
                                    </nav>
                                )}
                            </div>
                        </div>
                    </section>

                    <NewsletterSignup />

                    <ProjectCta title={t.blog.ctaTitle} text={t.blog.ctaText} />

                    {relatedPosts.length > 0 && (
                        <section
                            className="pub-projects pub-blog pub-blog--related"
                            aria-labelledby="pub-post-related"
                        >
                            <div className="site-wrap">
                                <h2
                                    id="pub-post-related"
                                    className="pub-blog__related-title"
                                >
                                    {t.blog.relatedTitle}
                                </h2>
                                <div className="pub-blog__grid">
                                    {relatedPosts.map((related) => (
                                        <PostCard
                                            key={related.id}
                                            post={related}
                                        />
                                    ))}
                                </div>
                            </div>
                        </section>
                    )}
                </div>
            </PublicShell>
        </>
    );
}
