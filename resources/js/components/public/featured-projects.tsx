import { Link } from '@inertiajs/react';
import { useRef } from 'react';
import ProjectCard from '@/components/public/project-card';
import TechMarquee from '@/components/public/tech-marquee';
import { usePinAtBottom } from '@/hooks/use-pin-at-bottom';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicProject, PublicTechnology } from '@/types';

/**
 * Projets phares : bande de technologies, puis cartes en quinconce
 * (large + étroite, puis étroite + large…). Empilées sur mobile.
 */
export default function FeaturedProjects({
    projects,
    technologies,
}: {
    projects: PublicProject[];
    technologies: PublicTechnology[];
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const ref = useRef<HTMLElement>(null);

    // Épinglée par le bas : les témoignages viennent glisser par-dessus.
    usePinAtBottom(ref);

    return (
        <section
            ref={ref}
            className="pub-featured"
            aria-labelledby="pub-featured-title"
        >
            <h2 className="pub-featured__lead">{t.home.stackTitle}</h2>
            <TechMarquee technologies={technologies} label={t.home.stackLabel} />

            <div className="site-wrap">
                <header className="pub-featured__head">
                    <div>
                        <p className="pub-kicker">{t.home.featuredKicker}</p>
                        <h2 id="pub-featured-title" className="pub-featured__title">
                            {t.home.featuredTitle}
                        </h2>
                    </div>

                    <Link href={path('projects')} className="pub-featured__all">
                        {t.home.allProjects} <span aria-hidden="true">→</span>
                    </Link>
                </header>

                <div className="pub-featured__grid">
                    {projects.map((project, index) => (
                        <ProjectCard
                            key={project.id}
                            project={project}
                            index={index}
                            wide={(index + Math.floor(index / 2)) % 2 === 0}
                            full={
                                index === projects.length - 1 && index % 2 === 0
                            }
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
