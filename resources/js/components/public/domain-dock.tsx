import { AnimatePresence, motion } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import { domainAnchor } from '@/components/public/skill-domains';
import { useTranslations } from '@/lib/i18n';
import type { PublicDomain } from '@/types';

/** Ligne de lecture : un domaine devient « en cours » quand son haut la passe. */
const READING_LINE = 0.4;

/**
 * Barre flottante des domaines, en bas au centre de la page des compétences :
 * elle apparaît une fois le bandeau passé, met en avant le domaine en cours
 * de lecture avec une jauge d'avancée, et permet de sauter d'un domaine à
 * l'autre. Elle s'efface en arrivant après le dernier domaine.
 */
export default function DomainDock({ domains }: { domains: PublicDomain[] }) {
    const t = useTranslations();
    const dock = useRef<HTMLElement>(null);
    const [visible, setVisible] = useState(false);
    const [activeId, setActiveId] = useState<number | null>(null);

    useEffect(() => {
        let frame = 0;

        const measure = () => {
            frame = 0;

            const line = window.innerHeight * READING_LINE;
            const sections = domains
                .map((domain) => ({
                    domain,
                    element: document.getElementById(domainAnchor(domain)),
                }))
                .filter(
                    (
                        entry,
                    ): entry is {
                        domain: PublicDomain;
                        element: HTMLElement;
                    } => entry.element !== null,
                );

            if (sections.length === 0) {
                return;
            }

            const first = sections[0].element.getBoundingClientRect();
            const last =
                sections[sections.length - 1].element.getBoundingClientRect();
            const current =
                [...sections]
                    .reverse()
                    .find(
                        ({ element }) =>
                            element.getBoundingClientRect().top <= line,
                    ) ?? sections[0];
            const box = current.element.getBoundingClientRect();
            const progress = Math.min(
                1,
                Math.max(0, (line - box.top) / box.height),
            );

            setVisible(first.top <= line && last.bottom > line);
            setActiveId(current.domain.id);
            // La jauge suit le défilement sans réafficher le composant.
            dock.current?.style.setProperty(
                '--dock-progress',
                String(progress),
            );
        };

        const schedule = () => {
            frame ||= window.requestAnimationFrame(measure);
        };

        measure();
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);

        return () => {
            window.cancelAnimationFrame(frame);
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
        };
    }, [domains]);

    return (
        <AnimatePresence>
            {visible && (
                <motion.nav
                    ref={dock}
                    className="pub-dock"
                    aria-label={t.skills.jump}
                    initial={{ opacity: 0, y: 24, x: '-50%' }}
                    animate={{ opacity: 1, y: 0, x: '-50%' }}
                    exit={{ opacity: 0, y: 24, x: '-50%' }}
                    transition={{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
                >
                    <ol>
                        {domains.map((domain) => {
                            const isActive = domain.id === activeId;

                            return (
                                <li key={domain.id}>
                                    <a
                                        href={`#${domainAnchor(domain)}`}
                                        className={`pub-dock__item${isActive ? ' is-active' : ''}`}
                                        aria-current={
                                            isActive ? 'location' : undefined
                                        }
                                        style={
                                            {
                                                '--domain': domain.color,
                                            } as CSSProperties
                                        }
                                    >
                                        <span
                                            className="pub-dock__dot"
                                            aria-hidden="true"
                                        />
                                        {domain.label}
                                        {isActive && (
                                            <span
                                                className="pub-dock__progress"
                                                aria-hidden="true"
                                            />
                                        )}
                                    </a>
                                </li>
                            );
                        })}
                    </ol>
                </motion.nav>
            )}
        </AnimatePresence>
    );
}
