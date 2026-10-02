import { Link } from '@inertiajs/react';
import type { CSSProperties } from 'react';
import DomainDock from '@/components/public/domain-dock';
import Seo from '@/components/public/seo';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import SkillDomains, { domainAnchor } from '@/components/public/skill-domains';
import TechMarquee from '@/components/public/tech-marquee';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicDomain, PublicSkill, PublicTechnology } from '@/types';

export default function Skills({
    domains,
    skills,
    technologies,
}: {
    domains: PublicDomain[];
    skills: PublicSkill[];
    technologies: PublicTechnology[];
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const filledDomains = domains.filter((domain) =>
        skills.some((skill) => skill.domain.id === domain.id),
    );

    return (
        <>
            <Seo title={t.skills.title} description={t.skills.hook} />

            <PublicShell overHero>
                <PageHero
                    id="pub-skills-title"
                    eyebrow={t.skills.title}
                    title={t.skills.heading}
                    aside={
                        <nav
                            className="pub-jump pub-reveal pub-reveal--late"
                            aria-label={t.skills.jump}
                        >
                            <p className="pub-jump__label">{t.skills.jump}</p>
                            <ol>
                                {filledDomains.map((domain, index) => (
                                    <li key={domain.id}>
                                        <a
                                            href={`#${domainAnchor(domain)}`}
                                            style={
                                                {
                                                    '--domain': domain.color,
                                                } as CSSProperties
                                            }
                                        >
                                            <span className="pub-jump__num">
                                                {String(index + 1).padStart(
                                                    2,
                                                    '0',
                                                )}
                                            </span>
                                            <span className="pub-jump__name">
                                                {domain.label}
                                            </span>
                                            <span aria-hidden="true">↓</span>
                                        </a>
                                    </li>
                                ))}
                            </ol>
                        </nav>
                    }
                >
                    <p>{t.skills.hook}</p>
                </PageHero>

                <SkillDomains
                    domains={domains}
                    skills={skills}
                    countLabel={t.skills.countLabel}
                />
                <DomainDock domains={filledDomains} />

                <section className="pub-toolbox" aria-label={t.home.stackTitle}>
                    <h2 className="pub-featured__lead">{t.home.stackTitle}</h2>
                    <TechMarquee
                        technologies={technologies}
                        label={t.home.stackLabel}
                    />
                </section>

                <section className="pub-facts">
                    <div className="site-wrap">
                        <div className="pub-facts__grid">
                            <div className="pub-fact pub-fact--full pub-fact--ink">
                                <Link
                                    href={path('projects')}
                                    className="pub-fact__cta"
                                    data-cursor-label={t.home.cursorView}
                                >
                                    <span>{t.skills.cta}</span>
                                    <span aria-hidden="true">→</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
