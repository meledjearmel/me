import { motion } from 'framer-motion';
import { useTranslations } from '@/lib/i18n';
import type { PublicEducation } from '@/types';

/**
 * Formation : mêmes lignes que le parcours professionnel, sans accordéon
 * (l'année de fin suffit, le détail tient sur une ligne).
 */
export default function EducationList({
    educations,
}: {
    educations: PublicEducation[];
}) {
    const t = useTranslations();

    if (educations.length === 0) {
        return null;
    }

    return (
        <section
            className="pub-exp pub-exp--education"
            aria-labelledby="pub-edu-title"
        >
            <div className="site-wrap">
                <h2 id="pub-edu-title" className="pub-exp__title">
                    {t.about.education}
                </h2>

                <ul className="pub-exp__list">
                    {educations.map((education, index) => (
                        <motion.li
                            key={education.id}
                            className="pub-exp__item pub-exp__head pub-exp__head--static"
                            initial={{ opacity: 0, y: 24 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, amount: 0.4 }}
                            transition={{
                                duration: 0.55,
                                delay: index * 0.06,
                                ease: [0.22, 1, 0.36, 1],
                            }}
                        >
                            <span className="pub-exp__who">
                                <span className="pub-exp__company">
                                    {education.degree}
                                </span>
                                <span className="pub-exp__role">
                                    {education.institution}
                                </span>
                            </span>
                            <span className="pub-exp__date">
                                {education.end_date?.slice(0, 4)}
                            </span>
                        </motion.li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
