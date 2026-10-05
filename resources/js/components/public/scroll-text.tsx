import {
    motion,
    useTransform,
    type MotionValue,
} from 'framer-motion';
import { Fragment, useRef } from 'react';
import type { ReactNode } from 'react';
import MentionText, {
    MentionLink,
    plainMentions,
    splitMentions,
} from '@/components/public/mention-text';
import type { MentionCards } from '@/components/public/mention-text';
import { useViewportProgress } from '@/hooks/use-viewport-progress';
import { usePrefersReducedMotion } from '@/hooks/use-media-query';

/** Opacité de départ : le texte reste lisible même avant d'avoir défilé. */
const MIN_OPACITY = 0.3;

/** Haut du paragraphe à 0 % (bas de la fenêtre à 90 %) et à 100 % (fin à 55 %). */
const RANGE = (height: number, viewport: number): [number, number] => [
    0.9 * viewport,
    0.55 * viewport - height,
];

function Word({
    word,
    progress,
    range,
}: {
    word: ReactNode;
    progress: MotionValue<number>;
    range: [number, number];
}) {
    const opacity = useTransform(progress, range, [MIN_OPACITY, 1]);
    const y = useTransform(progress, range, ['0.35em', '0em']);

    return (
        <motion.span className="pub-scrolltext__word" style={{ opacity, y }}>
            {word}
        </motion.span>
    );
}

/** Mots du texte ; une mention `@[…](type:id)` forme un seul mot, rendu en lien. */
function wordsOf(text: string, mentions: MentionCards): ReactNode[] {
    return splitMentions(text).flatMap((segment): ReactNode[] =>
        typeof segment === 'string'
            ? segment.split(' ').filter((word) => word !== '')
            : [<MentionLink segment={segment} mentions={mentions} />],
    );
}

/**
 * Texte qui se révèle mot après mot pendant qu'on défile : la progression est
 * liée à la position dans la fenêtre, donc elle ne peut pas être « dépassée ».
 * Les mentions du texte deviennent des liens (voir MentionText).
 */
export default function ScrollText({
    text,
    mentions = {},
    className = '',
}: {
    text: string;
    mentions?: MentionCards;
    className?: string;
}) {
    const ref = useRef<HTMLParagraphElement>(null);
    const reduceMotion = usePrefersReducedMotion();
    const scrollYProgress = useViewportProgress(ref, RANGE);
    const words = wordsOf(text, mentions);

    if (reduceMotion) {
        return (
            <p className={className}>
                <MentionText text={text} mentions={mentions} />
            </p>
        );
    }

    return (
        <p
            ref={ref}
            className={className}
            aria-label={plainMentions(text, mentions)}
        >
            {words.map((word, index) => {
                const start = (index / words.length) * 0.85;
                const end = start + 0.15 + 0.05;

                return (
                    <Fragment key={index}>
                        <Word
                            word={word}
                            progress={scrollYProgress}
                            range={[start, Math.min(end, 1)]}
                        />{' '}
                    </Fragment>
                );
            })}
        </p>
    );
}
