import { Link } from "@inertiajs/react";
import PageHero from "@/components/public/page-hero";
import PostCard from "@/components/public/post-card";
import PublicShell from "@/components/public/public-shell";
import Seo from "@/components/public/seo";
import { useLocale, useLocalizedPath, useTranslations } from "@/lib/i18n";
import type { PublicPost } from "@/types";

type PaginatedPosts = {
    data: PublicPost[];
    links: { prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number };
};

export default function BlogIndex({
    posts,
    tags,
    activeTag,
    total,
}: {
    posts: PaginatedPosts;
    tags: { slug: string; name: string; count: number }[];
    activeTag: string | null;
    total: number;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();
    const { current_page: page, last_page: lastPage } = posts.meta;
    // La première carte de la première page, sans filtre, s'affiche en grand.
    const leadFirst = page === 1 && activeTag === null;

    return (
        <>
            <Seo
                title={t.blog.title}
                description={t.seo.blog}
                breadcrumbs={[[t.nav.blog, `/${locale}/blog`]]}
            >
                <link
                    rel="alternate"
                    type="application/rss+xml"
                    title={t.blog.title}
                    href={`/${locale}/blog/feed`}
                />
            </Seo>

            <PublicShell overHero>
                <PageHero
                    id="pub-blog-title"
                    eyebrow={t.blog.title}
                    title={t.blog.heading}
                    aside={
                        <dl className="pub-stats pub-reveal pub-reveal--late">
                            <div>
                                <dt>{t.blog.countLabel}</dt>
                                <dd>{total}</dd>
                            </div>
                            <div>
                                <dt>{t.blog.topicsLabel}</dt>
                                <dd>{tags.length}</dd>
                            </div>
                        </dl>
                    }
                >
                    <p>{t.blog.hook}</p>
                </PageHero>

                <section
                    className="pub-projects pub-blog"
                    aria-label={t.blog.title}
                >
                    <div className="site-wrap">
                        {tags.length > 0 && (
                            <nav
                                className="pub-filters"
                                aria-label={t.blog.filterLabel}
                            >
                                <Link
                                    href={path("blog")}
                                    className="pub-filter"
                                    aria-current={
                                        activeTag === null ? "page" : undefined
                                    }
                                    preserveScroll
                                >
                                    {t.blog.all}
                                    <span>{total}</span>
                                </Link>
                                {tags.map((tag) => (
                                    <Link
                                        key={tag.slug}
                                        href={`${path("blog")}?tag=${tag.slug}`}
                                        className="pub-filter"
                                        aria-current={
                                            activeTag === tag.slug
                                                ? "page"
                                                : undefined
                                        }
                                        preserveScroll
                                    >
                                        {tag.name}
                                        <span>{tag.count}</span>
                                    </Link>
                                ))}
                            </nav>
                        )}

                        {posts.data.length === 0 ? (
                            <p className="pub-blog__empty">
                                {activeTag ? t.blog.emptyTag : t.blog.empty}
                            </p>
                        ) : (
                            <>
                                <h2 className="sr-only">{t.nav.blog}</h2>
                                <div className="pub-blog__grid">
                                    {posts.data.map((post, index) => (
                                        <PostCard
                                            key={post.id}
                                            post={post}
                                            large={leadFirst && index === 0}
                                        />
                                    ))}
                                </div>
                            </>
                        )}

                        {lastPage > 1 && (
                            <nav
                                className="pub-blog__pager"
                                aria-label={t.blog.pageOf(page, lastPage)}
                            >
                                {posts.links.prev ? (
                                    <Link href={posts.links.prev}>
                                        {t.blog.previous}
                                    </Link>
                                ) : (
                                    <span />
                                )}
                                <span>{t.blog.pageOf(page, lastPage)}</span>
                                {posts.links.next ? (
                                    <Link href={posts.links.next}>
                                        {t.blog.next}
                                    </Link>
                                ) : (
                                    <span />
                                )}
                            </nav>
                        )}
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
