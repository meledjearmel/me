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

/**
 * Encart « Sur GitHub » d'un projet lié à un dépôt : nom, langage, étoiles, dernière
 * activité et une courbe en barres des contributions de l'année.
 */
export default function ProjectGitHub({
    github,
}: {
    github: ProjectGitHubData;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const labels = t.projects.github;
    const max = Math.max(1, ...(github.weeks ?? []));
    const name = github.name ?? labels.privateRepository;

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
                            github.pushed_at
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

                {github.weeks && github.total > 0 && (
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
