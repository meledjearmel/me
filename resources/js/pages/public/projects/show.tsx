import { Link, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import { plainMentions } from '@/components/public/mention-text';
import PageHero from '@/components/public/page-hero';
import PostMentionCard from '@/components/public/post-mention-card';
import {
    accentStyle,
    ProjectCta,
    ProjectGallery,
    ProjectMeta,
    ProjectStory,
    ReadNext,
} from '@/components/public/project-parts';
import Seo from '@/components/public/seo';
import PublicShell from '@/components/public/public-shell';
import ReviewInvite from '@/components/public/review-invite';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type {
    PublicProfile,
    PublicProject,
    PublicTestimonial,
} from '@/types';

/** Première phrase d'un texte : sert de chapô dans le bandeau. */
const firstSentence = (text: string) => {
    const match = text.match(/^.+?[.!?](?=\s|$)/);

    return match ? match[0] : text;
};

export default function ProjectShow({
    project,
    nextProject,
    testimonial,
}: {
    project: PublicProject;
    nextProject: PublicProject | null;
    testimonial: PublicTestimonial | null;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { props } = usePage<{ siteUrl: string; profile: PublicProfile }>();
    const path = useLocalizedPath();
    const storyRef = useRef<HTMLDivElement>(null);
    const summary = plainMentions(project.result, project.mentions);
    const otherRelated = project.related_projects.filter(
        (related) => related.id !== nextProject?.id,
    );

    return (
        <>
            <Seo
                title={project.title}
                description={summary}
                image={project.cover_url}
                type="article"
                breadcrumbs={[
                    [t.projects.title, `/${locale}/projects`],
                    [project.title, `/${locale}/projects/${project.slug}`],
                ]}
                jsonLd={[
                    {
                        '@type': 'CreativeWork',
                        name: project.title,
                        description: summary,
                        image: project.cover_url ?? undefined,
                        inLanguage: locale,
                        author: { '@type': 'Person', name: props.profile.name },
                        keywords: project.technologies
                            .map((technology) => technology.name)
                            .join(', '),
                        codeRepository: project.repo_url ?? undefined,
                    },
                ]}
            />

            <PublicShell overHero>
                <div
                    className="pub-project"
                    style={accentStyle(project.accent_color)}
                >
                    <PageHero
                        id="pub-project-title"
                        eyebrow={project.domains
                            .map((domain) => domain.label)
                            .join(' · ')}
                        title={project.title}
                        aside={<ProjectMeta project={project} />}
                        lead={
                            <>
                                <p>
                                    {project.tagline ??
                                        firstSentence(
                                            plainMentions(
                                                project.context,
                                                project.mentions,
                                            ),
                                        )}
                                </p>
                                <Link
                                    href={path('projects')}
                                    className="pub-page-hero__back"
                                >
                                    {t.projects.back}
                                </Link>
                            </>
                        }
                    />

                    {project.cover_url && (
                        <div className="pub-plate site-wrap">
                            <img src={project.cover_url} alt={project.title} />
                        </div>
                    )}

                    <div ref={storyRef}>
                        <ProjectStory
                            project={project}
                            testimonial={testimonial}
                        />
                    </div>
                    <PostMentionCard
                        article={storyRef}
                        mentions={project.mentions}
                    />
                    <ProjectGallery urls={project.gallery_urls} />

                    {otherRelated.length > 0 && (
                        <section
                            className="pub-related"
                            aria-label={t.projects.related}
                        >
                            <div className="site-wrap">
                                <p className="pub-related__label">
                                    {t.projects.related}
                                </p>
                                <ul>
                                    {otherRelated.map((related) => (
                                        <li key={related.id}>
                                            <Link
                                                href={path(
                                                    `projects/${related.slug}`,
                                                )}
                                            >
                                                {related.title}
                                                <span aria-hidden="true">
                                                    →
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </section>
                    )}

                    <ProjectCta />

                    <ReviewInvite
                        className="pub-review-invite--block site-wrap"
                        text={t.fab.reviewProject}
                        cta={t.fab.reviewProjectCta}
                        context={{
                            label: project.title,
                            projectId: project.id,
                        }}
                    />

                    {nextProject && <ReadNext project={nextProject} />}
                </div>
            </PublicShell>
        </>
    );
}
