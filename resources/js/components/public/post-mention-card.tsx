import { useEffect, useRef, useState } from 'react';
import type { RefObject } from 'react';
import { createPortal } from 'react-dom';
import { useTranslations } from '@/lib/i18n';
import type { PublicPostMention } from '@/types';

/** Délais d'ouverture et de fermeture : un passage rapide de la souris n'ouvre rien. */
const OPEN_DELAY_MS = 250;
const CLOSE_DELAY_MS = 150;

const CARD_WIDTH = 288;

/**
 * Carte affichée au survol (ou au focus clavier) d'une mention dans un article : les liens
 * `a[data-mention]` produits par le serveur. Au toucher, pas de survol : le lien s'ouvre.
 */
export default function PostMentionCard({
    article,
    mentions,
}: {
    article: RefObject<HTMLElement | null>;
    mentions: Record<string, PublicPostMention>;
}) {
    const t = useTranslations();
    const [open, setOpen] = useState<{
        mention: PublicPostMention;
        rect: DOMRect;
    } | null>(null);
    const timer = useRef(0);

    useEffect(() => {
        const element = article.current;

        if (!element) {
            return;
        }

        const later = (action: () => void, delay: number) => {
            window.clearTimeout(timer.current);
            timer.current = window.setTimeout(action, delay);
        };

        const show = (event: Event) => {
            const link = (event.target as HTMLElement).closest<HTMLElement>(
                'a[data-mention]',
            );
            const mention = mentions[link?.dataset.mention ?? ''];

            if (!link || !mention) {
                return;
            }

            later(
                () =>
                    setOpen({ mention, rect: link.getBoundingClientRect() }),
                event.type === 'focusin' ? 0 : OPEN_DELAY_MS,
            );
        };

        const hide = (event: Event) => {
            if (
                (event.target as HTMLElement).closest('a[data-mention]')
            ) {
                later(() => setOpen(null), CLOSE_DELAY_MS);
            }
        };

        const close = () => setOpen(null);

        element.addEventListener('pointerover', show);
        element.addEventListener('pointerout', hide);
        element.addEventListener('focusin', show);
        element.addEventListener('focusout', hide);
        window.addEventListener('scroll', close, { passive: true });

        return () => {
            window.clearTimeout(timer.current);
            element.removeEventListener('pointerover', show);
            element.removeEventListener('pointerout', hide);
            element.removeEventListener('focusin', show);
            element.removeEventListener('focusout', hide);
            window.removeEventListener('scroll', close);
        };
    }, [article, mentions]);

    if (!open) {
        return null;
    }

    const { mention, rect } = open;
    const left = Math.min(
        Math.max(16, rect.left),
        window.innerWidth - CARD_WIDTH - 16,
    );
    const below = rect.bottom + 8 + 220 < window.innerHeight;

    return createPortal(
        <a
            href={mention.url}
            className="post-mention-card"
            style={{
                left,
                width: CARD_WIDTH,
                ...(below
                    ? { top: rect.bottom + 8 }
                    : { bottom: window.innerHeight - rect.top + 8 }),
            }}
            onPointerEnter={() => window.clearTimeout(timer.current)}
            onPointerLeave={() => {
                timer.current = window.setTimeout(
                    () => setOpen(null),
                    CLOSE_DELAY_MS,
                );
            }}
            tabIndex={-1}
            aria-hidden
        >
            {mention.image && (
                <img src={mention.image} alt="" loading="lazy" />
            )}
            <span className="post-mention-card__body">
                <span className="post-mention-card__kind">
                    {t.blog.mentionKinds[mention.kind]}
                </span>
                <span className="post-mention-card__title">
                    {mention.title}
                </span>
                {mention.description && (
                    <span className="post-mention-card__description">
                        {mention.description}
                    </span>
                )}
            </span>
        </a>,
        document.body,
    );
}
