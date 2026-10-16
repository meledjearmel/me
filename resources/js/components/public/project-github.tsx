import { useLocale, useTranslations } from '@/lib/i18n';

export type ProjectGitHubData = {
    /** `null` pour un dépôt privé : il n'est jamais nommé. */
    name: string | null;
    url: string | null;
    language: string | null;
    stars: number;
    pushed_at: string | null;
    private: boolean;
    total: number;
    /** Contributions semaine par semaine sur l'année, de la plus ancienne à la plus récente. */
    weeks: number[] | null;
};

/** Au-delà de six mois sans contribution, le dépôt est considéré comme en sommeil. */
const DORMANT_AFTER_MS = 183 * 24 * 60 * 60 * 1000;

/** En deçà, la courbe se résume à une ou deux barres isolées : la période parle mieux. */
const MIN_ACTIVE_WEEKS = 3;

/**
 * Encart « Sur GitHub » d'un projet lié à un dépôt : nom, langage, étoiles, dernière
 * activité et une courbe en barres des contributions de l'année. Un dépôt en
 * sommeil n'affiche pas de courbe vide : seulement sa période d'activité.
 */
export default function ProjectGitHub({
    github,
    startedOn = null,
}: {
    github: ProjectGitHubData;
    /** Début du projet (AAAA-MM), pour borner la période d'un dépôt en sommeil. */
    startedOn?: string | null;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const labels = t.projects.github;
    const max = Math.max(1, ...(github.weeks ?? []));
    const name = github.name ?? labels.privateRepository;
    const activeWeeks = (github.weeks ?? []).filter(
        (count) => count > 0,
    ).length;
    const dormant =
        activeWeeks < MIN_ACTIVE_WEEKS ||
        (github.pushed_at !== null &&
            Date.now() - new Date(github.pushed_at).getTime() >
                DORMANT_AFTER_MS);
    const month = (date: Date) =>
        new Intl.DateTimeFormat(locale, {
            month: 'long',
            year: 'numeric',
        }).format(date);
    const lastMonth = github.pushed_at
        ? month(new Date(github.pushed_at))
        : null;
    // Le 15 du mois : aucun fuseau ne fait basculer la date sur le mois voisin.
    const firstMonth = startedOn
        ? month(new Date(`${startedOn}-15T12:00:00`))
        : null;
    const period =
        firstMonth && lastMonth && firstMonth !== lastMonth
            ? `${firstMonth} – ${lastMonth}`
            : (lastMonth ?? firstMonth);

    return (
        <section
            className="pub-project-github site-wrap"
            aria-labelledby="pub-project-github-title"
        >
            <div className="pub-project-github__card">
                <div className="pub-project-github__head">
                    <h2 id="pub-project-github-title">{labels.title}</h2>
                    {github.url ? (
                        <a
                            href={github.url}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {name}
                            <span aria-hidden="true"> ↗</span>
                        </a>
                    ) : (
                        <strong>{name}</strong>
                    )}
                    <p>
                        {[
                            github.language,
                            !github.private && github.stars > 0
                                ? `★ ${t.about.github.stars(github.stars)}`
                                : null,
                            !dormant && github.pushed_at
                                ? `${labels.lastActivity} ${new Intl.DateTimeFormat(
                                      locale,
                                      {
                                          day: 'numeric',
                                          month: 'long',
                                          year: 'numeric',
                                      },
                                  ).format(new Date(github.pushed_at))}`
                                : null,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </div>

                {dormant && period && (
                    <p className="pub-project-github__period">
                        <span>{labels.period}</span>
                        <strong>{period}</strong>
                    </p>
                )}

                {!dormant && github.weeks && github.total > 0 && (
                    <figure className="pub-project-github__weeks">
                        <div
                            role="img"
                            aria-label={`${labels.weeksLabel} : ${labels.contributions(github.total)}`}
                        >
                            {github.weeks.map((count, index) => (
                                <span
                                    key={index}
                                    data-empty={count === 0 || undefined}
                                    style={{
                                        height: `${Math.max(6, (count / max) * 100)}%`,
                                    }}
                                />
                            ))}
                        </div>
                        <figcaption>
                            {labels.contributions(github.total)}
                        </figcaption>
                    </figure>
                )}
            </div>
        </section>
    );
}
