import { AnimatePresence, motion } from 'framer-motion';
import Letters from '@/components/public/letters';

/**
 * Affiche un texte qui « roule » verticalement vers le haut quand il change,
 * comme une machine à sous : l'ancien sort par le haut, le nouveau monte du bas.
 */
export default function RollingText({
    text,
    className = '',
    duration = 0.56,
}: {
    text: string;
    className?: string;
    duration?: number;
}) {
    return (
        <span className={`pub-roll ${className}`}>
            <AnimatePresence initial={false} mode="popLayout">
                <motion.span
                    key={text}
                    className="pub-roll__word"
                    initial={{ y: '100%' }}
                    animate={{ y: '0%' }}
                    exit={{ y: '-100%' }}
                    transition={{ duration, ease: [0.22, 1.15, 0.36, 1] }}
                >
                    <Letters text={text} />
                </motion.span>
            </AnimatePresence>
        </span>
    );
}
