import { useRef } from 'react';
import MentionText from '@/components/public/mention-text';
import type { MentionCards } from '@/components/public/mention-text';
import PageHero from '@/components/public/page-hero';
import PostMentionCard from '@/components/public/post-mention-card';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useTranslations } from '@/lib/i18n';

type Block = { type: 'paragraph'; text: string } | { type: 'list'; items: string[] };

/**
 * Découpe le texte saisi dans l'admin : une ligne vide sépare deux blocs ; un bloc
 * dont toutes les lignes commencent par « - » devient une liste.
 */
function toBlocks(text: string): Block[] {
    return text
        .split(/\n\s*\n/)
        .map((chunk) => chunk.trim())
        .filter(Boolean)
        .map((chunk): Block => {
            const lines = chunk.split('\n').map((line) => line.trim());

            return lines.every((line) => line.startsWith('- '))
                ? { type: 'list', items: lines.map((line) => line.slice(2)) }
                : { type: 'paragraph', text: lines.join(' ') };
        });
}

/** Page « Now » : ce sur quoi je travaille en ce moment. */
export default function Now({
    text,
    mentions,
    contentLocale,
    updatedAt,
}: {
    text: string;
    /** Cartes des mentions `@[…](type:id)` du texte, par « type:id ». */
    mentions: MentionCards;
    contentLocale: 'fr' | 'en';
    updatedAt: string | null;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const sectionRef = useRef<HTMLElement>(null);
    const updated = updatedAt
        ? new Intl.DateTimeFormat(locale, {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          }).format(new Date(updatedAt))
        : null;

    return (
        <>
            <Seo
                title={t.now.title}
                description={t.now.seo}
                breadcrumbs={[[t.now.title, `/${locale}/now`]]}
            />

            <PublicShell overHero>
                <PageHero
                    id="pub-now-title"
                    eyebrow={t.now.title}
                    title={t.now.heading}
                >
                    {updated && <p>{t.now.updated(updated)}</p>}
                </PageHero>

                <section
                    ref={sectionRef}
                    className="pub-now site-wrap"
                    lang={contentLocale}
                >
                    <PostMentionCard article={sectionRef} mentions={mentions} />
                    {contentLocale !== locale && t.now.onlyFrench && (
                        <p className="pub-post__notice">{t.now.onlyFrench}</p>
                    )}
                    {toBlocks(text).map((block, index) =>
                        block.type === 'list' ? (
                            <ul key={index}>
                                {block.items.map((item) => (
                                    <li key={item}>
                                        <MentionText
                                            text={item}
                                            mentions={mentions}
                                        />
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p key={index}>
                                <MentionText
                                    text={block.text}
                                    mentions={mentions}
                                />
                            </p>
                        ),
                    )}
                </section>
            </PublicShell>
        </>
    );
}
