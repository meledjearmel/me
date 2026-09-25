import { Link } from '@inertiajs/react';
import { motion, useReducedMotion, useTransform } from 'framer-motion';
import { useRef } from 'react';
import type { CSSProperties, ReactNode } from 'react';
import ScrollText from '@/components/public/scroll-text';
import { TechIcon } from '@/components/public/tech-marquee';
import { useViewportProgress } from '@/hooks/use-viewport-progress';
import { accentFor, readableTextOn } from '@/lib/accent';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicProject, PublicTechnology } from '@/types';

/** Variables CSS d'un projet : sa couleur d'accent et la couleur de texte lisible dessus. */
export function accentStyle(accentColor: string | null, index = 0): CSSProperties {
    const accent = accentFor(accentColor, index);

    return {
        '--accent': accent,
        '--on-accent': readableTextOn(accent),
    } as CSSProperties;
}

/** Panneau de faits dans le bandeau : domaines, technologies, code, démo. */
export function ProjectMeta({ project }: { project: PublicProject }) {
    const t = useTranslations();

    return (
        <motion.dl
            className="pub-meta"
            initial={{ opacity: 0, y: 24 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.3, ease: [0.22, 1, 0.36, 1] }}
        >
            <div>
                <dt>{t.projects.domainsLabel}</dt>
                <dd>{project.domains.map((domain) => domain.label).join(' · ')}</dd>
            </div>

            {project.is_open_source && (
                <div>
                    <dt>{t.projects.typeLabel}</dt>
                    <dd>{t.projects.openSource}</dd>
                </div>
            )}

            {project.technologies.length > 0 && (
                <div>
                    <dt>{t.projects.techLabel}</dt>
                    <dd className="pub-meta__logos">
                        {project.technologies.slice(0, 8).map((technology) => (
                            <TechIcon
                                key={technology.id}
                                technology={technology}
                                className="pub-logo"
                            />
                        ))}
                    </dd>
                </div>
            )}

            {(project.repo_url || project.demo_url) && (
                <div>
                    <dt>{t.projects.linksLabel}</dt>
                    <dd className="pub-meta__links">
                        {project.repo_url && (
                            <a href={project.repo_url} target="_blank" rel="noreferrer">
                                {t.projects.code} ↗
                            </a>
                        )}
                        {project.demo_url && (
                            <a href={project.demo_url} target="_blank" rel="noreferrer">
                                {t.projects.demo} ↗
                            </a>
                        )}
                    </dd>
                </div>
            )}
        </motion.dl>
    );
}

/** Une section numérotée : numéro et intitulé collés à gauche, contenu à droite. */
function LedgerSection({
    number,
    label,
    children,
    tone = 'plain',
}: {
    number: string;
    label: string;
    children: ReactNode;
    tone?: 'plain' | 'accent';
}) {
    return (
        <section className={`pub-ledger pub-ledger--${tone}`} aria-label={label}>
            <div className="site-wrap pub-ledger__grid">
                <div className="pub-ledger__rail">
                    <span className="pub-ledger__num" aria-hidden="true">
                        {number}
                    </span>
                    <span className="pub-ledger__eye">{label}</span>
                </div>
                <div className="pub-ledger__body">{children}</div>
            </div>
        </section>
    );
}

/** Technologies regroupées par catégorie, avec leurs logos. */
function TechnologyRows({ technologies }: { technologies: PublicTechnology[] }) {
    const t = useTranslations();
    const categories = t.projects.techCategory as Record<string, string>;
    const groups = Object.entries(
        technologies.reduce<Record<string, PublicTechnology[]>>((carry, technology) => {
            (carry[technology.category] ??= []).push(technology);

            return carry;
        }, {}),
    );

    return (
        <div className="pub-ledger__rows">
            {groups.map(([category, items]) => (
                <div key={category} className="pub-ledger__row">
                    <p className="pub-ledger__key">{categories[category] ?? category}</p>
                    <ul className="pub-ledger__tools">
                        {items.map((technology) => (
                            <li key={technology.id}>
                                <TechIcon technology={technology} className="pub-logo" />
                                <span>{technology.name}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
        </div>
    );
}

/**
 * Le récit du projet en sections numérotées (comme une étude de cas) : contexte
 * et réalisation s'écrivent au défilement, le résultat passe sur un fond à la
 * couleur du projet, puis viennent les technologies.
 */
export function ProjectStory({ project }: { project: PublicProject }) {
    const t = useTranslations();
    let number = 0;
    const next = () => String(++number).padStart(2, '0');

    return (
        <>
            <LedgerSection number={next()} label={t.projects.context}>
                <ScrollText text={project.context} className="pub-ledger__text" />
            </LedgerSection>

            <LedgerSection number={next()} label={t.projects.realization}>
                <ScrollText text={project.realization} className="pub-ledger__text" />
            </LedgerSection>

            <LedgerSection number={next()} label={t.projects.result} tone="accent">
                <ScrollText
                    text={project.result}
                    className="pub-ledger__text pub-ledger__text--big"
                />
            </LedgerSection>

            {project.technologies.length > 0 && (
                <LedgerSection number={next()} label={t.projects.techLabel}>
                    <TechnologyRows technologies={project.technologies} />
                </LedgerSection>
            )}
        </>
    );
}

/** Captures du projet, si elles existent. */
export function ProjectGallery({ urls }: { urls: string[] }) {
    if (urls.length === 0) {
        return null;
    }

    return (
        <section className="pub-gallery" aria-hidden={false}>
            <div className="site-wrap pub-gallery__grid">
                {urls.map((url) => (
                    <img key={url} src={url} alt="" loading="lazy" />
                ))}
            </div>
        </section>
    );
}

/**
 * « À lire ensuite » : un grand titre sur fond sombre, une barre qui se remplit
 * pendant qu'on arrive en bas de la page, et un aperçu coloré du projet suivant.
 */
export function ReadNext({ project }: { project: PublicProject }) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const ref = useRef<HTMLElement>(null);
    const reduceMotion = useReducedMotion();
    const progress = useViewportProgress(ref, (_height, viewport) => [
        viewport,
        0.35 * viewport,
    ]);
    const fill = useTransform(progress, [0, 1], ['0%', '100%']);

    return (
        <section ref={ref} className="pub-readnext" style={accentStyle(project.accent_color)}>
            <div className="site-wrap pub-readnext__grid">
                <div className="pub-readnext__copy">
                    <p className="pub-readnext__kicker">{t.projects.readNext}</p>
                    <Link
                        href={path(`projects/${project.slug}`)}
                        className="pub-readnext__title"
                        data-cursor-label={t.projects.readNextCursor}
                    >
                        {project.title}
                    </Link>
                    <p className="pub-readnext__desc">{project.result}</p>
                    <div className="pub-readnext__bar" aria-hidden="true">
                        <motion.i style={reduceMotion ? { width: '100%' } : { width: fill }} />
                    </div>
                </div>

                <Link
                    href={path(`projects/${project.slug}`)}
                    className="pub-readnext__tile"
                    aria-label={`${t.projects.readNext} : ${project.title}`}
                    data-cursor-label={t.projects.readNextCursor}
                >
                    <span aria-hidden="true">→</span>
                </Link>
            </div>
        </section>
    );
}
