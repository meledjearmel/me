import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useLocale } from '@/lib/i18n';
import type { PublicProfile } from '@/types';

type JsonLd = Record<string, unknown>;

type SeoProps = {
    title: string;
    description: string;
    image?: string | null;
    type?: 'website' | 'article' | 'profile';
    jsonLd?: JsonLd | JsonLd[];
    children?: ReactNode;
};

const DEFAULT_IMAGE = '/images/share/og-default.jpg';
const OG_LOCALES = { fr: 'fr_FR', en: 'en_US' } as const;

/**
 * Balises SEO d'une page publique : canonical et hreflang absolus,
 * Open Graph, Twitter Card et données structurées JSON-LD.
 */
export default function Seo({
    title,
    description,
    image,
    type = 'website',
    jsonLd,
    children,
}: SeoProps) {
    const { props, url } = usePage<{
        siteUrl: string;
        profile: PublicProfile;
    }>();
    const locale = useLocale();
    const other = locale === 'fr' ? 'en' : 'fr';

    const siteUrl = props.siteUrl.replace(/\/$/, '');
    const path = url.split(/[?#]/)[0].replace(/\/$/, '') || `/${locale}`;
    const alternatePath = path.replace(
        new RegExp(`^/${locale}(?=/|$)`),
        `/${other}`,
    );
    const absolute = (value: string) =>
        /^https?:\/\//.test(value)
            ? value
            : `${siteUrl}${value.startsWith('/') ? '' : '/'}${value}`;

    const canonical = absolute(path);
    const imageUrl = absolute(image ?? DEFAULT_IMAGE);
    const fullTitle = `${title} - ${props.profile.name}`;
    const blocks = jsonLd ? (Array.isArray(jsonLd) ? jsonLd : [jsonLd]) : [];

    return (
        <Head title={title}>
            <meta name="description" content={description} />
            <link rel="canonical" href={canonical} />
            <link rel="alternate" hrefLang={locale} href={canonical} />
            <link
                rel="alternate"
                hrefLang={other}
                href={absolute(alternatePath)}
            />
            <link rel="alternate" hrefLang="x-default" href={`${siteUrl}/`} />

            <meta property="og:type" content={type} />
            <meta property="og:site_name" content={props.profile.name} />
            <meta property="og:title" content={fullTitle} />
            <meta property="og:description" content={description} />
            <meta property="og:url" content={canonical} />
            <meta property="og:locale" content={OG_LOCALES[locale]} />
            <meta
                property="og:locale:alternate"
                content={OG_LOCALES[other]}
            />
            <meta name="twitter:title" content={fullTitle} />
            <meta name="twitter:description" content={description} />
            <meta
                name="twitter:card"
                content="summary_large_image"
            />
            <meta property="og:image" content={imageUrl} />
            <meta name="twitter:image" content={imageUrl} />

            {blocks.map((block, index) => (
                <script key={index} type="application/ld+json">
                    {JSON.stringify({ '@context': 'https://schema.org', ...block })}
                </script>
            ))}
            {children}
        </Head>
    );
}
