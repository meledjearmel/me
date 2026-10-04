import { useLocale, useTranslations } from '@/lib/i18n';

export type GitHubActivityData = {
    username: string;
    profile_url: string;
    public_repos: number;
    followers: number;
    pushes_last_30_days: number;
    repositories: {
        name: string;
        description: string | null;
        language: string | null;
        stars: number;
        url: string;
        pushed_at: string;
    }[];
    synced_at: string;
};

/**
 * Mon activité GitHub (synchronisée chaque heure par `github:sync`) : trois
 * compteurs, puis mes dépôts les plus étoilés et les plus récents.
 */
export default function GitHubActivity({
    github,
}: {
    github: GitHubActivityData | null;
}) {
    const t = useTranslations();
    const locale = useLocale();

    if (!github || github.repositories.length === 0) {
        return null;
    }

    const date = (iso: string) =>
        new Intl.DateTimeFormat(locale, {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }).format(new Date(iso));

    return (
        <section className="pub-exp pub-github" aria-labelledby="pub-github-title">
            <div className="site-wrap">
                <h2 id="pub-github-title" className="pub-exp__title">
                    {t.about.github.title}
                </h2>

                <dl className="pub-github__stats">
                    {[
                        [t.about.github.repos, github.public_repos],
                        [t.about.github.followers, github.followers],
                        [t.about.github.pushes, github.pushes_last_30_days],
                    ].map(([label, value]) => (
                        <div key={label}>
                            <dt>{label}</dt>
                            <dd>{value}</dd>
                        </div>
                    ))}
                </dl>

                <ul className="pub-github__repos">
                    {github.repositories.map((repository) => (
                        <li key={repository.url}>
                            <a
                                href={repository.url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <strong>{repository.name}</strong>
                                {repository.description && (
                                    <p>{repository.description}</p>
                                )}
                                <span className="pub-github__meta">
                                    {[
                                        repository.language,
                                        repository.stars > 0
                                            ? `★ ${t.about.github.stars(repository.stars)}`
                                            : null,
                                        `${t.about.github.updated} ${date(repository.pushed_at)}`,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>

                <a
                    href={github.profile_url}
                    className="pub-github__profile"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    {t.about.github.profile}
                    <span aria-hidden="true"> ↗</span>
                </a>
            </div>
        </section>
    );
}
