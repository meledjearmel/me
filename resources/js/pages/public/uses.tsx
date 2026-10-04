import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useTranslations } from '@/lib/i18n';

type UsesCategoryKey = 'hardware' | 'development' | 'apps' | 'services';

type UsesCategory = {
    key: UsesCategoryKey;
    items: {
        id: number;
        name: string;
        description: string | null;
        url: string | null;
    }[];
};

/** Page « Uses » : le matériel et les outils du quotidien, par rubrique. */
export default function Uses({ categories }: { categories: UsesCategory[] }) {
    const t = useTranslations();
    const locale = useLocale();

    return (
        <>
            <Seo
                title={t.uses.title}
                description={t.uses.seo}
                breadcrumbs={[[t.uses.title, `/${locale}/uses`]]}
            />

            <PublicShell overHero>
                <PageHero
                    id="pub-uses-title"
                    eyebrow={t.uses.title}
                    title={t.uses.heading}
                >
                    <p>{t.uses.hook}</p>
                </PageHero>

                <div className="pub-uses site-wrap">
                    {categories.map((category) => (
                        <section
                            key={category.key}
                            className="pub-uses__group"
                            aria-labelledby={`pub-uses-${category.key}`}
                        >
                            <h2
                                id={`pub-uses-${category.key}`}
                                className="pub-uses__title"
                            >
                                {t.uses.categories[category.key]}
                            </h2>
                            <ul className="pub-uses__list">
                                {category.items.map((item) => (
                                    <li key={item.id}>
                                        <h3>
                                            {item.url ? (
                                                <a
                                                    href={item.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    {item.name}
                                                    <span aria-hidden="true">
                                                        {' '}
                                                        ↗
                                                    </span>
                                                </a>
                                            ) : (
                                                item.name
                                            )}
                                        </h3>
                                        {item.description && (
                                            <p>{item.description}</p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))}
                </div>
            </PublicShell>
        </>
    );
}
