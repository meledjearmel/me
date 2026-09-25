import { AnimatePresence, motion } from 'framer-motion';
import { useState } from 'react';
import { useLocale, useTranslations } from '@/lib/i18n';
import type { PublicExperience } from '@/types';

/** « juin 2022 » / « June 2022 », d'après la langue du site. */
function useMonthYear(): (isoDate: string) => string {
    const locale = useLocale();
    const formatter = new Intl.DateTimeFormat(locale, {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    });

    return (isoDate) => formatter.format(new Date(`${isoDate}T00:00:00Z`));
}

/**
 * Parcours professionnel en accordéon (comme la référence) : une ligne par
 * poste avec l'entreprise, le rôle et les dates ; la première s'ouvre d'office.
 */
export default function ExperienceList({
    experiences,
}: {
    experiences: PublicExperience[];
}) {
    const t = useTranslations();
    const monthYear = useMonthYear();
    const [openId, setOpenId] = useState<number | null>(
        experiences[0]?.id ?? null,
    );

    return (
        <section className="pub-exp" aria-labelledby="pub-exp-title">
            <div className="site-wrap">
                <p className="pub-kicker">{t.about.kicker}</p>
                <h2 id="pub-exp-title" className="pub-exp__title">
                    {t.about.experience}
                </h2>

                <ul className="pub-exp__list">
                    {experiences.map((experience) => {
                        const isOpen = openId === experience.id;
                        const panelId = `pub-exp-panel-${experience.id}`;

                        return (
                            <li key={experience.id} className="pub-exp__item">
                                <button
                                    type="button"
                                    className="pub-exp__head"
                                    aria-expanded={isOpen}
                                    aria-controls={panelId}
                                    aria-label={`${experience.company}, ${experience.role} — ${t.about.expand}`}
                                    onClick={() =>
                                        setOpenId(isOpen ? null : experience.id)
                                    }
                                >
                                    <span className="pub-exp__who">
                                        <span className="pub-exp__company">
                                            {experience.company}
                                        </span>
                                        <span className="pub-exp__role">
                                            {experience.role}
                                        </span>
                                    </span>

                                    <span className="pub-exp__meta">
                                        <span className="pub-exp__date">
                                            {monthYear(experience.start_date)} –{' '}
                                            {experience.end_date
                                                ? monthYear(experience.end_date)
                                                : t.about.present}
                                        </span>
                                        <span
                                            className={`pub-exp__toggle${isOpen ? ' is-open' : ''}`}
                                            aria-hidden="true"
                                        />
                                    </span>
                                </button>

                                <AnimatePresence initial={false}>
                                    {isOpen && (
                                        <motion.div
                                            id={panelId}
                                            className="pub-exp__panel"
                                            initial={{ height: 0, opacity: 0 }}
                                            animate={{ height: 'auto', opacity: 1 }}
                                            exit={{ height: 0, opacity: 0 }}
                                            transition={{
                                                duration: 0.45,
                                                ease: [0.22, 1, 0.36, 1],
                                            }}
                                        >
                                            <div className="pub-exp__panel-inner">
                                                {experience.location && (
                                                    <p className="pub-exp__place">
                                                        {experience.location}
                                                    </p>
                                                )}

                                                {experience.description && (
                                                    <p>{experience.description}</p>
                                                )}

                                                {experience.highlights.length > 0 && (
                                                    <ul>
                                                        {experience.highlights.map(
                                                            (highlight, index) => (
                                                                <li key={index}>
                                                                    {highlight}
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </div>
                                        </motion.div>
                                    )}
                                </AnimatePresence>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </section>
    );
}
