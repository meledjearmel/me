import type { PublicPostMention } from '@/types';

/** Jeton d'une mention dans un texte brut (voir PostMentions::TOKEN côté serveur). */
const TOKEN =
    /@\[([^\]\n]{1,120})\]\((project|post|technology|experience):(\d+)\)/gu;

export type MentionSegment = string | { label: string; key: string };

export type MentionCards = Record<string, PublicPostMention>;

/** Découpe un texte en morceaux de texte et en mentions `@[libellé](type:id)`. */
export function splitMentions(text: string): MentionSegment[] {
    const segments: MentionSegment[] = [];
    let last = 0;

    for (const match of text.matchAll(TOKEN)) {
        if (match.index > last) {
            segments.push(text.slice(last, match.index));
        }

        segments.push({ label: match[1], key: `${match[2]}:${match[3]}` });
        last = match.index + match[0].length;
    }

    if (last < text.length) {
        segments.push(text.slice(last));
    }

    return segments;
}

/** Le texte sans jetons : chaque mention redevient son nom. */
export function plainMentions(text: string, mentions: MentionCards = {}): string {
    return splitMentions(text)
        .map((segment) =>
            typeof segment === 'string'
                ? segment
                : (mentions[segment.key]?.title ?? segment.label),
        )
        .join('');
}

/**
 * Une mention : lien vers l'élément à son nom actuel, repéré par `data-mention` pour la carte
 * de survol (PostMentionCard). Un élément retiré du site reste son libellé en texte.
 */
export function MentionLink({
    segment,
    mentions,
}: {
    segment: Exclude<MentionSegment, string>;
    mentions: MentionCards;
}) {
    const card = mentions[segment.key];

    if (!card) {
        return <>{segment.label}</>;
    }

    return (
        <a href={card.url} className="post-mention" data-mention={segment.key}>
            {card.title}
        </a>
    );
}

/** Texte brut dont les mentions deviennent des liens. */
export default function MentionText({
    text,
    mentions = {},
}: {
    text: string;
    mentions?: MentionCards;
}) {
    return (
        <>
            {splitMentions(text).map((segment, index) =>
                typeof segment === 'string' ? (
                    segment
                ) : (
                    <MentionLink
                        key={index}
                        segment={segment}
                        mentions={mentions}
                    />
                ),
            )}
        </>
    );
}
