import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useTranslations } from '@/lib/i18n';

type PublicCertification = {
    id: number;
    kind: 'certification' | 'course';
    name: string;
    issuer: string;
    issued_on: string;
    expires_on: string | null;
    expired: boolean;
    credential_id: string | null;
    credential_url: string | null;
    badge_url: string | null;
};

/** Page « Certifications » : les certifications, puis les formations courtes. */
export default function Certifications({
    certifications,
}: {
    certifications: PublicCertification[];
}) {
    const t = useTranslations();
    const locale = useLocale();
    const date = (value: string) =>
        new Intl.DateTimeFormat(locale, {
            month: 'long',
            year: 'numeric',
        }).format(new Date(`${value}T12:00:00`));

    const groups = [
        {
            key: 'certification',
            title: t.certifications.certifications,
            items: certifications.filter(
                (item) => item.kind === 'certification',
            ),
        },
        {
            key: 'course',
            title: t.certifications.courses,
            items: certifications.filter((item) => item.kind === 'course'),
        },
    ].filter((group) => group.items.length > 0);

    return (
        <>
            <Seo
                title={t.certifications.title}
                description={t.certifications.seo}
                breadcrumbs={[
                    [t.certifications.title, `/${locale}/certifications`],
                ]}
            />

            <PublicShell overHero>
                <PageHero
                    id="pub-certifications-title"
                    eyebrow={t.certifications.title}
                    title={t.certifications.heading}
                >
                    <p>{t.certifications.hook}</p>
                </PageHero>

                <div className="pub-uses site-wrap">
                    {groups.map((group) => (
                        <section
                            key={group.key}
                            className="pub-uses__group"
                            aria-labelledby={`pub-certifications-${group.key}`}
                        >
                            <h2
                                id={`pub-certifications-${group.key}`}
                                className="pub-uses__title"
                            >
                                {group.title}
                            </h2>
                            <ul className="pub-certs">
                                {group.items.map((item) => (
                                    <li
                                        key={item.id}
                                        className={
                                            item.expired
                                                ? 'is-expired'
                                                : undefined
                                        }
                                    >
                                        {item.badge_url && (
                                            <img
                                                src={item.badge_url}
                                                alt=""
                                                loading="lazy"
                                                className="pub-certs__badge"
                                            />
                                        )}
                                        <div>
                                            <h3>{item.name}</h3>
                                            <p className="pub-certs__issuer">
                                                {item.issuer}
                                            </p>
                                            <p className="pub-certs__meta">
                                                {[
                                                    `${item.kind === 'course' ? t.certifications.completed : t.certifications.issued} ${date(item.issued_on)}`,
                                                    item.expires_on
                                                        ? item.expired
                                                            ? t.certifications
                                                                  .expired
                                                            : `${t.certifications.expires} ${date(item.expires_on)}`
                                                        : null,
                                                    item.credential_id
                                                        ? `${t.certifications.credential} ${item.credential_id}`
                                                        : null,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </p>
                                            {item.credential_url && (
                                                <a
                                                    href={item.credential_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="pub-certs__verify"
                                                >
                                                    {t.certifications.verify}
                                                    <span aria-hidden="true">
                                                        {' '}
                                                        ↗
                                                    </span>
                                                </a>
                                            )}
                                        </div>
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
