import type { ReactNode } from 'react';
import type { PublicPostMention } from '@/types';

const TOKEN =
    /(\*\*[^*]+\*\*|https?:\/\/[^\s)]*[^\s).,;:!?]|[\w.+-]+@[\w-]+\.[\w.-]*\w)/g;
const LIST_ITEM = /^\s*(?:[-*•]|\d+[.)])\s+(.*)$/;

type Mentions = Record<string, PublicPostMention>;

function inline(text: string, mentions: Mentions): ReactNode[] {
    return text.split(TOKEN).map((part, index) => {
        const key = part in mentions ? part : part.replace(/\/$/, '');
        const mention = mentions[key];

        // Page de projet ou d'article du site : son nom, avec la carte au survol.
        if (mention) {
            return (
                <a
                    key={index}
                    href={mention.url}
                    className="post-mention"
                    data-mention={key}
                >
                    {mention.title}
                </a>
            );
        }

        if (part.length > 4 && part.startsWith('**') && part.endsWith('**')) {
            return <strong key={index}>{part.slice(2, -2)}</strong>;
        }

        if (/^https?:\/\//.test(part)) {
            return (
                <a
                    key={index}
                    href={part}
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    {part}
                </a>
            );
        }

        if (/^[\w.+-]+@[\w-]+\.[\w.-]*\w$/.test(part)) {
            return (
                <a key={index} href={`mailto:${part}`}>
                    {part}
                </a>
            );
        }

        return part;
    });
}

/**
 * Affiche la réponse de l'assistant : gras, listes et liens (adresses web et
 * email), rien d'autre. Le texte est toujours rendu comme texte par React,
 * jamais comme HTML, donc une réponse ne peut pas injecter de balises.
 */
export default function ChatRichText({
    text,
    mentions = {},
}: {
    text: string;
    mentions?: Mentions;
}) {
    const blocks: ReactNode[] = [];
    let items: string[] = [];

    const flushList = () => {
        if (items.length > 0) {
            blocks.push(
                <ul key={`list-${blocks.length}`}>
                    {items.map((item, index) => (
                        <li key={index}>{inline(item, mentions)}</li>
                    ))}
                </ul>,
            );
            items = [];
        }
    };

    for (const line of text.split('\n')) {
        const item = LIST_ITEM.exec(line);

        if (item) {
            items.push(item[1]);

            continue;
        }

        flushList();

        if (line.trim() !== '') {
            blocks.push(
                <p key={`p-${blocks.length}`}>{inline(line.trim(), mentions)}</p>,
            );
        }
    }

    flushList();

    return <>{blocks}</>;
}
