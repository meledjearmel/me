import { Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { useCongratulations } from '@/hooks/use-congratulations';
import { burstConfetti } from '@/lib/confetti';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';

const CITY_TIME_ZONE = 'Africa/Abidjan';

/** Décalage d'Abidjan par rapport à UTC, en minutes (pas d'heure d'été). */
const CITY_UTC_OFFSET = 0;

/** Horaires où l'on peut compter sur une réponse rapide, en heure d'Abidjan. */
const WORK_HOURS = { start: 8, end: 18 };

type LocalTime = {
    time: string;
    isWorkingHours: boolean;
    /** Écart avec le visiteur en minutes : positif quand Abidjan est en avance. */
    visitorOffset: number;
};

/** Heure locale d'Abidjan, mise à jour chaque seconde (après l'hydratation). */
function useLocalTime(): LocalTime | null {
    const locale = useLocale();
    const [time, setTime] = useState<LocalTime | null>(null);

    useEffect(() => {
        const formatter = new Intl.DateTimeFormat(locale, {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZone: CITY_TIME_ZONE,
        });
        const parts = new Intl.DateTimeFormat('en-US', {
            weekday: 'short',
            hour: 'numeric',
            hour12: false,
            timeZone: CITY_TIME_ZONE,
        });
        const tick = () => {
            const now = new Date();
            const cityParts = parts.formatToParts(now);
            const hour = Number(
                cityParts.find((part) => part.type === 'hour')?.value,
            );
            const weekday = cityParts.find(
                (part) => part.type === 'weekday',
            )?.value;

            setTime({
                time: formatter.format(now),
                isWorkingHours:
                    weekday !== 'Sat' &&
                    weekday !== 'Sun' &&
                    hour >= WORK_HOURS.start &&
                    hour < WORK_HOURS.end,
                visitorOffset: CITY_UTC_OFFSET + now.getTimezoneOffset(),
            });
        };

        tick();

        const interval = window.setInterval(tick, 1000);

        return () => window.clearInterval(interval);
    }, [locale]);

    return time;
}

/** « 2 h de moins que vous », avec les demi-heures quand le fuseau en a. */
function formatOffset(
    minutes: number,
    t: ReturnType<typeof useTranslations>,
    locale: string,
): string {
    if (minutes === 0) {
        return t.about.offsetSame;
    }

    const hours = (Math.abs(minutes) / 60).toLocaleString(locale, {
        maximumFractionDigits: 1,
    });

    return minutes > 0
        ? t.about.offsetAhead(hours)
        : t.about.offsetBehind(hours);
}

const card = (delay: number) => ({
    initial: { opacity: 0, y: 32 },
    whileInView: { opacity: 1, y: 0 },
    viewport: { once: true, amount: 0.25 },
    transition: { duration: 0.6, delay, ease: [0.22, 1, 0.36, 1] as const },
});

/**
 * « En bref » : une grille de cartes (heure locale, distinction,
 * langues, lien vers les projets). Les informations viennent de ton CV.
 */
export default function AboutFacts({
    congratulations,
}: {
    congratulations: number;
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const time = useLocalTime();
    const locale = useLocale();
    const congrats = useCongratulations(
        congratulations,
        path('congratulations'),
    );
    const languages = [
        { name: t.about.langFrench, note: t.about.langFrenchNote, score: 5 },
        {
            name: t.about.langEnglishWritten,
            note: t.about.langEnglishWrittenNote,
            score: 4,
        },
        {
            name: t.about.langEnglishSpoken,
            note: t.about.langEnglishSpokenNote,
            score: 2,
        },
    ];

    return (
        <section className="pub-facts" aria-labelledby="pub-facts-title">
            <div className="site-wrap">
                <p className="pub-kicker">{t.about.factsKicker}</p>
                <h2 id="pub-facts-title" className="sr-only">
                    {t.about.factsKicker}
                </h2>

                <div className="pub-facts__grid">
                    <motion.article
                        className="pub-fact pub-fact--sand"
                        {...card(0)}
                    >
                        <p className="pub-fact__label">
                            {t.about.timeLabel} · {t.about.timeCity}
                        </p>
                        <p className="pub-fact__clock" aria-live="off">
                            {time?.time ?? '--:--:--'}
                        </p>
                        {time && (
                            <div className="pub-fact__status">
                                <p
                                    className={`pub-fact__presence${time.isWorkingHours ? ' is-online' : ''}`}
                                >
                                    {time.isWorkingHours
                                        ? t.about.statusOnline
                                        : t.about.statusOffline}
                                </p>
                                <p className="pub-fact__offset">
                                    {formatOffset(time.visitorOffset, t, locale)}
                                </p>
                            </div>
                        )}
                    </motion.article>

                    <motion.button
                        type="button"
                        className="pub-fact pub-fact--coral pub-fact--action"
                        data-cursor-label={t.about.congrats}
                        onClick={(event) => {
                            const box =
                                event.currentTarget.getBoundingClientRect();
                            // Au clavier, le clic n'a pas de position : on part du centre de la carte.
                            const x = event.clientX || box.left + box.width / 2;
                            const y = event.clientY || box.top + box.height / 2;

                            burstConfetti(x, y);
                            congrats.add();
                        }}
                        {...card(0.08)}
                    >
                        <span className="pub-fact__label">
                            {t.about.distinctionLabel}
                        </span>
                        <span className="pub-fact__medium">
                            {t.about.distinction}
                        </span>
                        <span className="pub-fact__count" aria-live="polite">
                            {congrats.total === 0 ? (
                                t.about.congratsFirst
                            ) : (
                                <>
                                    <strong>
                                        {congrats.total.toLocaleString(locale)}
                                    </strong>{' '}
                                    {t.about.congratsReceived(congrats.total)}
                                </>
                            )}
                        </span>
                    </motion.button>

                    <motion.article
                        className="pub-fact pub-fact--green"
                        {...card(0.16)}
                    >
                        <p className="pub-fact__label">
                            {t.about.languagesLabel}
                        </p>
                        <ul className="pub-langs">
                            {languages.map((language) => (
                                <li
                                    key={language.name}
                                    className="pub-langs__row"
                                >
                                    <span className="pub-langs__text">
                                        <span className="pub-langs__name">
                                            {language.name}
                                        </span>
                                        <span className="pub-langs__note">
                                            {language.note}
                                        </span>
                                    </span>
                                    <span
                                        className="pub-langs__dots"
                                        role="img"
                                        aria-label={`${language.score} / 5`}
                                    >
                                        {[1, 2, 3, 4, 5].map((dot) => (
                                            <span
                                                key={dot}
                                                className={
                                                    dot <= language.score
                                                        ? 'is-on'
                                                        : ''
                                                }
                                            />
                                        ))}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </motion.article>

                    <motion.div
                        className="pub-fact pub-fact--full pub-fact--ink"
                        {...card(0.24)}
                    >
                        <Link
                            href={path('projects')}
                            className="pub-fact__cta"
                            data-cursor-label={t.home.cursorView}
                        >
                            <span>{t.about.projectsCta}</span>
                            <span aria-hidden="true">→</span>
                        </Link>
                    </motion.div>
                </div>
            </div>
        </section>
    );
}
