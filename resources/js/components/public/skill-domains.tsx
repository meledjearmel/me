import { motion } from 'framer-motion';
import type { CSSProperties, PointerEvent } from 'react';
import { useRef, useState } from 'react';
import SkillDialog from '@/components/public/skill-dialog';
import { TechIcon } from '@/components/public/tech-marquee';
import { useTranslations } from '@/lib/i18n';
import type { PublicDomain, PublicSkill } from '@/types';

const ease = [0.22, 1, 0.36, 1] as const;

/** Lien d'ancre vers la section d'un domaine. */
export const domainAnchor = (domain: PublicDomain) => `domain-${domain.key}`;

const TILT_X = 9;
const TILT_Y = 12;

/**
 * Éclaire les cartes comme une torche : chaque carte reçoit la position de la
 * souris (--mx, --my) et une lumière (--glow) que le CSS transforme en halo
 * teinté par la couleur du domaine. La carte survolée s'incline vers le
 * pointeur (--rx, --ry). Tout passe par des variables CSS : pas de rendu React
 * à chaque mouvement.
 */
function useTorch() {
    const list = useRef<HTMLUListElement>(null);

    const move = (event: PointerEvent<HTMLUListElement>) => {
        if (event.pointerType !== 'mouse' || !list.current) {
            return;
        }

        const tilt = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        list.current
            .querySelectorAll<HTMLElement>('.pub-skill__card')
            .forEach((card) => {
                const box = card.getBoundingClientRect();
                const x = event.clientX - box.left;
                const y = event.clientY - box.top;
                const inside = x >= 0 && y >= 0 && x <= box.width && y <= box.height;

                card.style.setProperty('--mx', `${x}px`);
                card.style.setProperty('--my', `${y}px`);
                card.style.setProperty('--glow', '1');
                card.style.setProperty(
                    '--rx',
                    inside && tilt ? `${-(y / box.height - 0.5) * TILT_X}deg` : '0deg',
                );
                card.style.setProperty(
                    '--ry',
                    inside && tilt ? `${(x / box.width - 0.5) * TILT_Y}deg` : '0deg',
                );
            });
    };

    const leave = () => {
        list.current
            ?.querySelectorAll<HTMLElement>('.pub-skill__card')
            .forEach((card) => {
                card.style.setProperty('--glow', '0');
                card.style.setProperty('--rx', '0deg');
                card.style.setProperty('--ry', '0deg');
            });
    };

    return { list, move, leave };
}

/** Liste des compétences d'un domaine, éclairée par la souris. */
function SkillList({
    skills,
    onOpen,
}: {
    skills: PublicSkill[];
    onOpen: (skill: PublicSkill) => void;
}) {
    const torch = useTorch();
    const t = useTranslations();

    return (
        <ul
            ref={torch.list}
            className="pub-domain__list"
            onPointerMove={torch.move}
            onPointerLeave={torch.leave}
        >
            {skills.map((skill, index) => (
                <motion.li
                    key={skill.id}
                    className="pub-skill"
                    initial={{ opacity: 0, y: 32 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, amount: 0.3 }}
                    transition={{
                        duration: 0.6,
                        delay: (index % 2) * 0.08,
                        ease,
                    }}
                >
                    <button
                        type="button"
                        className="pub-skill__card"
                        aria-haspopup="dialog"
                        data-cursor-label={t.skills.open}
                        onClick={() => onOpen(skill)}
                    >
                        <span className="pub-skill__name">{skill.name}</span>
                        {skill.description && (
                            <span className="pub-skill__text">{skill.description}</span>
                        )}

                        <span className="pub-skill__foot">
                            <span className="pub-skill__logos">
                                {skill.technologies.slice(0, 5).map((technology) => (
                                    <TechIcon
                                        key={technology.id}
                                        technology={technology}
                                        className="pub-logo"
                                    />
                                ))}
                            </span>
                            <span className="pub-skill__more" aria-hidden="true">
                                +
                            </span>
                        </span>
                    </button>
                </motion.li>
            ))}
        </ul>
    );
}

/**
 * Une section par domaine : le titre reste collé à gauche pendant qu'on
 * parcourt les compétences, présentées en cartes qui apparaissent une à une.
 * La couleur du domaine (définie en base) sert de fil conducteur.
 */
export default function SkillDomains({
    domains,
    skills,
    countLabel,
}: {
    domains: PublicDomain[];
    skills: PublicSkill[];
    countLabel: string;
}) {
    const [active, setActive] = useState<PublicSkill | null>(null);
    const groups = domains
        .map((domain) => ({
            domain,
            skills: skills.filter((skill) => skill.domain.id === domain.id),
        }))
        .filter((group) => group.skills.length > 0);

    return (
        <div className="pub-domains">
            {groups.map(({ domain, skills: domainSkills }, groupIndex) => (
                <section
                    key={domain.id}
                    id={domainAnchor(domain)}
                    className="pub-domain"
                    style={{ '--domain': domain.color } as CSSProperties}
                    aria-labelledby={`${domainAnchor(domain)}-title`}
                >
                    <div className="site-wrap pub-domain__grid">
                        <header className="pub-domain__head">
                            <span className="pub-domain__num" aria-hidden="true">
                                {String(groupIndex + 1).padStart(2, '0')}
                            </span>
                            <h2
                                id={`${domainAnchor(domain)}-title`}
                                className="pub-domain__title"
                            >
                                {domain.label}
                            </h2>
                            <p className="pub-domain__count">
                                {domainSkills.length} {countLabel}
                            </p>
                        </header>

                        <SkillList skills={domainSkills} onOpen={setActive} />
                    </div>
                </section>
            ))}

            <SkillDialog skill={active} onClose={() => setActive(null)} />
        </div>
    );
}
