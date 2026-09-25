import { Link } from '@inertiajs/react';
import { motion, useReducedMotion, useTransform } from 'framer-motion';
import { useRef, type CSSProperties } from 'react';
import { useViewportProgress } from '@/hooks/use-viewport-progress';
import { accentFor, readableTextOn } from '@/lib/accent';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicProject } from '@/types';

/** Haut de la carte : bas de la fenêtre à 0 %, 55 % de la hauteur à 100 %. */
const CARD_RANGE = (_height: number, viewport: number): [number, number] => [
    viewport,
    0.55 * viewport,
];

/**
 * Carte d'un projet phare : fond à la couleur d'accent du projet, numéro,
 * titre, résultat et domaines. Une couverture, si elle existe, passe en fond.
 */
export default function ProjectCard({
    project,
    index,
    wide,
    full = false,
    bare = false,
    compact = false,
}: {
    project: PublicProject;
    index: number;
    wide?: boolean;
    /** Carte seule dans sa rangée : elle prend toute la largeur. */
    full?: boolean;
    /** Sans classe de largeur : la grille est gérée par l'élément parent. */
    bare?: boolean;
    /** Carte plus basse, pour les listes longues. */
    compact?: boolean;
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const ref = useRef<HTMLElement>(null);
    const reduceMotion = useReducedMotion();

    // Comme la référence : la carte grandit (0,6 → 1) et se révèle (0,35 → 1)
    // à mesure qu'elle entre dans la fenêtre, au lieu d'un simple fondu.
    const scrollYProgress = useViewportProgress(ref, CARD_RANGE);
    const scale = useTransform(scrollYProgress, [0, 1], [0.6, 1]);
    const opacity = useTransform(scrollYProgress, [0, 1], [0.35, 1]);
    const accent = accentFor(project.accent_color, index);
    const style = {
        '--accent': accent,
        '--on-accent': readableTextOn(accent),
    } as CSSProperties;

    return (
        <motion.article
            className={`pub-card ${bare ? '' : full ? 'pub-card--full' : wide ? 'pub-card--wide' : 'pub-card--narrow'}${compact ? ' pub-card--compact' : ''}${project.cover_url ? ' pub-card--cover' : ''}`}
            ref={ref}
            style={reduceMotion ? style : { ...style, scale, opacity }}
        >
            <Link
                href={path(`projects/${project.slug}`)}
                className="pub-card__link"
                data-cursor-label={t.home.cursorView}
            >
                {project.cover_url && (
                    <img
                        src={project.cover_url}
                        alt=""
                        className="pub-card__cover"
                        loading="lazy"
                    />
                )}

                {project.is_open_source && (
                    <span className="pub-card__oss">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="6" cy="6" r="2.4" />
                            <circle cx="6" cy="18" r="2.4" />
                            <circle cx="18" cy="9" r="2.4" />
                            <path d="M6 8.4v7.2M18 11.4c0 3.6-6 2.4-12 4.2" />
                        </svg>
                        {t.projects.openSource}
                    </span>
                )}

                <span className="pub-card__num">
                    {String(index + 1).padStart(2, '0')}
                </span>

                <div className="pub-card__body">
                    <h3 className="pub-card__title">{project.title}</h3>
                    <p className="pub-card__result">{project.result}</p>

                    <div className="pub-card__foot">
                        <ul className="pub-card__tags">
                            {project.domains.slice(0, 3).map((domain) => (
                                <li key={domain.id}>{domain.label}</li>
                            ))}
                        </ul>
                        <span className="pub-card__cta">
                            {t.home.viewProject}
                            <span aria-hidden="true">→</span>
                        </span>
                    </div>
                </div>
            </Link>
        </motion.article>
    );
}
