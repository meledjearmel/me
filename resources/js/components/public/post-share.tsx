import {
    Check,
    Facebook,
    Link as LinkIcon,
    Linkedin,
    Mail,
    MessageCircle,
    Share2,
    Twitter,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ComponentType } from 'react';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import { xsrfToken } from '@/lib/utils';

type Network = 'linkedin' | 'x' | 'whatsapp' | 'facebook' | 'email';

export type ShareChannel = Network | 'copy' | 'native';

const ICONS: Record<Network, ComponentType<{ className?: string }>> = {
    linkedin: Linkedin,
    x: Twitter,
    whatsapp: MessageCircle,
    facebook: Facebook,
    email: Mail,
};

/** Adresse de l'article marquée de sa source, pour la retrouver dans les statistiques. */
export function withUtm(url: string, source: string): string {
    const tracked = new URL(url);
    tracked.searchParams.set('utm_source', source);
    tracked.searchParams.set('utm_medium', 'social');
    tracked.searchParams.set('utm_campaign', 'blog_share');

    return tracked.toString();
}

/** Lien de partage d'un réseau : une simple URL, sans script tiers. */
export function shareHref(
    network: Network,
    url: string,
    title: string,
): string {
    const link = encodeURIComponent(withUtm(url, network));
    const text = encodeURIComponent(title);

    switch (network) {
        case 'linkedin':
            return `https://www.linkedin.com/sharing/share-offsite/?url=${link}`;
        case 'x':
            return `https://x.com/intent/post?url=${link}&text=${text}`;
        case 'whatsapp':
            return `https://wa.me/?text=${text}%20${link}`;
        case 'facebook':
            return `https://www.facebook.com/sharer/sharer.php?u=${link}`;
        case 'email':
            return `mailto:?subject=${text}&body=${link}`;
    }
}

/**
 * Partage d'un article : feuille de partage du système quand elle existe
 * (mobile), liens directs vers les réseaux et copie du lien.
 */
export default function PostShare({
    slug,
    url,
    title,
    count,
    onShared,
    compact = false,
}: {
    slug: string;
    url: string;
    title: string;
    /** Total des partages, partagé entre les deux barres de la page. */
    count: number;
    onShared: (total: number) => void;
    compact?: boolean;
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const [canShareNatively, setCanShareNatively] = useState(false);
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        setCanShareNatively(typeof navigator.share === 'function');
    }, []);

    /** Compte le partage côté serveur ; keepalive survit à l'ouverture d'un autre onglet. */
    const record = (channel: ShareChannel) => {
        void fetch(path(`blog/${slug}/shares`), {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify({ network: channel }),
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((data: { total: number } | null) => {
                if (data) {
                    onShared(data.total);
                }
            })
            .catch(() => undefined);
    };

    const copy = () => {
        void navigator.clipboard?.writeText(withUtm(url, 'copy')).then(() => {
            record('copy');
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1800);
        });
    };

    const shareNatively = () => {
        void navigator
            .share({ title, url: withUtm(url, 'native') })
            .then(() => record('native'))
            .catch(() => undefined);
    };

    return (
        <div
            className={`pub-share${compact ? ' pub-share--compact' : ''}`}
            role="group"
            aria-label={t.blog.shareLabel}
        >
            <p className="pub-share__title">
                {t.blog.shareTitle}
                {count > 0 && (
                    <span className="pub-share__count">
                        {' · '}
                        {t.blog.sharesCount(count)}
                    </span>
                )}
            </p>
            <div className="pub-share__buttons">
                {canShareNatively && !compact && (
                    <button
                        type="button"
                        className="pub-share__button pub-share__button--native"
                        onClick={shareNatively}
                    >
                        <Share2 className="pub-share__icon" />
                        <span>{t.blog.shareNative}</span>
                    </button>
                )}
                {(Object.keys(ICONS) as Network[]).map((network) => {
                    const Icon = ICONS[network];
                    const label = t.blog.shareOn(t.blog.shareNetworks[network]);

                    return (
                        <a
                            key={network}
                            className="pub-share__button"
                            href={shareHref(network, url, title)}
                            target={network === 'email' ? undefined : '_blank'}
                            rel="noopener noreferrer"
                            onClick={() => record(network)}
                            aria-label={label}
                            data-tooltip={label}
                        >
                            <Icon className="pub-share__icon" />
                        </a>
                    );
                })}
                <button
                    type="button"
                    className="pub-share__button"
                    onClick={copy}
                    aria-label={copied ? t.blog.linkCopied : t.blog.copyLink}
                    data-tooltip={copied ? t.blog.linkCopied : t.blog.copyLink}
                >
                    {copied ? (
                        <Check className="pub-share__icon" />
                    ) : (
                        <LinkIcon className="pub-share__icon" />
                    )}
                </button>
                <span className="sr-only" aria-live="polite">
                    {copied ? t.blog.linkCopied : ''}
                </span>
            </div>
        </div>
    );
}
