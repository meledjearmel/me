import {
    motion,
    useReducedMotion,
    useTransform,
    type MotionValue,
} from 'framer-motion';
import { Fragment, useRef } from 'react';
import { useViewportProgress } from '@/hooks/use-viewport-progress';

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
    word: string;
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

/**
 * Texte qui se révèle mot après mot pendant qu'on défile : la progression est
 * liée à la position dans la fenêtre, donc elle ne peut pas être « dépassée ».
 */
export default function ScrollText({
    text,
    className = '',
}: {
    text: string;
    className?: string;
}) {
    const ref = useRef<HTMLParagraphElement>(null);
    const reduceMotion = useReducedMotion();
    const scrollYProgress = useViewportProgress(ref, RANGE);
    const words = text.split(' ');

    if (reduceMotion) {
        return <p className={className}>{text}</p>;
    }

    return (
        <p ref={ref} className={className} aria-label={text}>
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
