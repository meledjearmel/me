import { useEffect, useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { useLocale, useTranslations } from '@/lib/i18n';

export type GitHubCalendarDay = {
    date: string;
    count: number;
    /** De 0 (rien) à 4 (le quart le plus actif de l'année). */
    level: number;
    /** `name` est `null` pour un dépôt privé. */
    repositories: { name: string | null; count: number }[];
};

type Period = 'week' | 'month' | 'year';

const PERIOD_DAYS: Record<Period, number> = { week: 7, month: 30, year: 371 };

/** Déplacement au clavier selon la disposition : jours en ligne, en semaines ou en colonnes. */
const KEY_STEPS: Record<Period, Partial<Record<string, number>>> = {
    week: { ArrowLeft: -1, ArrowRight: 1 },
    month: { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 },
    year: { ArrowUp: -1, ArrowDown: 1, ArrowLeft: -7, ArrowRight: 7 },
};

/** Repos montrés dans l'infobulle avant « et N autres ». */
const TOOLTIP_REPOSITORIES = 4;

/** Jour de la semaine, lundi = 0 (dates GitHub en `AAAA-MM-JJ`, lues en UTC). */
const weekday = (date: string) =>
    (new Date(`${date}T00:00:00Z`).getUTCDay() + 6) % 7;

/**
 * Mon calendrier de contributions GitHub : la semaine en barres (par défaut), le mois
 * en calendrier, l'année en grille façon GitHub. L'infobulle d'un jour liste les dépôts
 * travaillés (un dépôt privé reste anonyme).
 */
export default function GitHubCalendar({
    days,
}: {
    days: GitHubCalendarDay[];
}) {
    const t = useTranslations();
    const locale = useLocale();
    const labels = t.about.github.calendar;
    const [period, setPeriod] = useState<Period>('week');
    const [focused, setFocused] = useState<number | null>(null);
    const [tooltip, setTooltip] = useState<{
        index: number;
        left: number;
        top: number;
    } | null>(null);
    const bodyRef = useRef<HTMLDivElement>(null);
    const scrollRef = useRef<HTMLDivElement>(null);
    const cellRefs = useRef<(HTMLButtonElement | null)[]>([]);

    const shown = useMemo(
        () => days.slice(-PERIOD_DAYS[period]),
        [days, period],
    );
    const total = shown.reduce((sum, day) => sum + day.count, 0);
    const max = Math.max(1, ...shown.map((day) => day.count));
    const offset =
        period === 'week' || shown.length === 0 ? 0 : weekday(shown[0].date);
    const current = focused ?? shown.length - 1;

    // L'année s'ouvre sur les semaines les plus récentes quand la grille défile (mobile).
    useEffect(() => {
        setFocused(null);
        setTooltip(null);

        if (scrollRef.current) {
            scrollRef.current.scrollLeft = scrollRef.current.scrollWidth;
        }
    }, [period]);

    const format = (date: string, options: Intl.DateTimeFormatOptions) =>
        new Intl.DateTimeFormat(locale, { timeZone: 'UTC', ...options }).format(
            new Date(`${date}T00:00:00Z`),
        );

    const repositoryName = (name: string | null) =>
        name ?? labels.privateRepository;

    const describe = (day: GitHubCalendarDay) =>
        [
            format(day.date, {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }),
            day.count > 0 ? labels.total(day.count) : labels.nothing,
            ...day.repositories.map(
                (repository) =>
                    `${repositoryName(repository.name)} (${repository.count})`,
            ),
        ].join(', ');

    const showTooltip = (index: number) => {
        const cell = cellRefs.current[index];
        const body = bodyRef.current;

        if (!cell || !body) {
            return;
        }

        const cellBox = cell.getBoundingClientRect();
        const bodyBox = body.getBoundingClientRect();

        setTooltip({
            index,
            left: Math.min(
                Math.max(cellBox.left + cellBox.width / 2 - bodyBox.left, 120),
                bodyBox.width - 120,
            ),
            top: cellBox.top - bodyBox.top,
        });
    };

    const move = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
        const step = KEY_STEPS[period][event.key];
        const target =
            event.key === 'Home'
                ? 0
                : event.key === 'End'
                  ? shown.length - 1
                  : step !== undefined
                    ? index + step
                    : null;

        if (target === null || target < 0 || target >= shown.length) {
            return;
        }

        event.preventDefault();
        setFocused(target);
        cellRefs.current[target]?.focus();
    };

    const active = tooltip ? shown[tooltip.index] : null;

    return (
        <div className={`pub-github-cal pub-github-cal--${period}`}>
            <div className="pub-github-cal__head">
                <h3 className="pub-github-cal__title">{labels.title}</h3>
                <div
                    className="pub-github-cal__periods"
                    role="group"
                    aria-label={labels.periodLabel}
                >
                    {(['week', 'month', 'year'] as const).map((option) => (
                        <button
                            key={option}
                            type="button"
                            aria-pressed={period === option}
                            onClick={() => setPeriod(option)}
                        >
                            {labels.periods[option]}
                        </button>
                    ))}
                </div>
            </div>

            <p className="pub-github-cal__total">
                <strong>{labels.total(total)}</strong> {labels.since[period]}
            </p>

            <div
                className="pub-github-cal__body"
                ref={bodyRef}
                onMouseLeave={() => setTooltip(null)}
            >
                <div
                    className="pub-github-cal__scroll"
                    ref={period === 'year' ? scrollRef : undefined}
                >
                    <div className="pub-github-cal__grid">
                        {Array.from({ length: offset }, (_, index) => (
                            <span
                                key={`gap-${index}`}
                                className="pub-github-cal__gap"
                                aria-hidden="true"
                            />
                        ))}
                        {shown.map((day, index) => (
                            <button
                                key={day.date}
                                ref={(element) => {
                                    cellRefs.current[index] = element;
                                }}
                                type="button"
                                className="pub-github-cal__day"
                                data-level={day.level}
                                tabIndex={index === current ? 0 : -1}
                                aria-label={describe(day)}
                                onMouseEnter={() => showTooltip(index)}
                                onFocus={() => {
                                    setFocused(index);
                                    showTooltip(index);
                                }}
                                onBlur={() => setTooltip(null)}
                                onKeyDown={(event) => move(event, index)}
                            >
                                {period === 'week' && (
                                    <>
                                        <span
                                            className="pub-github-cal__count"
                                            aria-hidden="true"
                                        >
                                            {day.count}
                                        </span>
                                        <span
                                            className="pub-github-cal__bar"
                                            style={{
                                                height: `${Math.max(4, (day.count / max) * 100)}%`,
                                            }}
                                        />
                                        <span
                                            className="pub-github-cal__label"
                                            aria-hidden="true"
                                        >
                                            {format(day.date, {
                                                weekday: 'short',
                                            })}
                                        </span>
                                    </>
                                )}
                                {period === 'month' && (
                                    <span aria-hidden="true">
                                        {format(day.date, { day: 'numeric' })}
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>
                </div>

                {active && tooltip && (
                    <div
                        className="pub-github-cal__tooltip"
                        style={{ left: tooltip.left, top: tooltip.top }}
                        aria-hidden="true"
                    >
                        <strong>
                            {format(active.date, {
                                weekday: 'long',
                                day: 'numeric',
                                month: 'long',
                            })}
                        </strong>
                        <span>
                            {active.count > 0
                                ? labels.total(active.count)
                                : labels.nothing}
                        </span>
                        {active.repositories.length > 0 && (
                            <ul>
                                {active.repositories
                                    .slice(0, TOOLTIP_REPOSITORIES)
                                    .map((repository, index) => (
                                        <li
                                            key={
                                                repository.name ??
                                                `private-${index}`
                                            }
                                        >
                                            <span>
                                                {repositoryName(
                                                    repository.name,
                                                )}
                                            </span>
                                            <span>{repository.count}</span>
                                        </li>
                                    ))}
                                {active.repositories.length >
                                    TOOLTIP_REPOSITORIES && (
                                    <li>
                                        {labels.others(
                                            active.repositories.length -
                                                TOOLTIP_REPOSITORIES,
                                        )}
                                    </li>
                                )}
                            </ul>
                        )}
                    </div>
                )}
            </div>

            {period !== 'week' && (
                <div className="pub-github-cal__legend" aria-hidden="true">
                    {labels.less}
                    {[0, 1, 2, 3, 4].map((level) => (
                        <span
                            key={level}
                            className="pub-github-cal__day"
                            data-level={level}
                        />
                    ))}
                    {labels.more}
                </div>
            )}
        </div>
    );
}
