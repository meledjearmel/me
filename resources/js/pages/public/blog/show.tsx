import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import PageHero from '@/components/public/page-hero';
import PostCard, { formatPostDate } from '@/components/public/post-card';
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

export default function BlogShow({
    post,
    relatedPosts,
}: {
    post: PublicPost;
    relatedPosts: PublicPost[];
}) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();
    const { props } = usePage<{ siteUrl: string; profile: PublicProfile }>();
    const toc = post.toc ?? [];
    const active = useActiveHeading(toc.map((item) => item.id));
    const untranslated = post.content_locale !== locale;
    const description = post.excerpt ?? t.seo.blog;

    return (
        <>
            <Seo
                title={post.title}
                description={description}
                image={post.cover_url}
                type="article"
                breadcrumbs={[
                    [t.nav.blog, `/${locale}/blog`],
                    [post.title, `/${locale}/blog/${post.slug}`],
                ]}
                jsonLd={{
                    '@type': 'BlogPosting',
                    headline: post.title,
                    description,
                    image: post.cover_url ?? undefined,
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
                {post.tags.map((tag) => (
                    <meta
                        key={tag.slug}
                        property="article:tag"
                        content={tag.name}
                    />
                ))}
            </Seo>

            <PublicShell overHero>
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
                                {untranslated && t.blog.onlyFrench && (
                                    <p className="pub-post__notice">
                                        {t.blog.onlyFrench}
                                    </p>
                                )}
                                <article
                                    className="post-content"
                                    lang={post.content_locale}
                                    dangerouslySetInnerHTML={{
                                        __html: post.body ?? '',
                                    }}
                                />
                            </div>
                        </div>
                    </section>

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
