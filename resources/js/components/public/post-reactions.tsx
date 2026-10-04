import { AnimatePresence, motion } from 'framer-motion';
import { useState } from 'react';
import type { ComponentType } from 'react';
import {
    BulbIcon,
    FlameIcon,
    HeartIcon,
    ThinkIcon,
    ThumbIcon,
} from '@/components/public/reaction-icons';
import { usePrefersReducedMotion } from '@/hooks/use-media-query';
import { playReactionSound } from '@/lib/reaction-sounds';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import { xsrfToken } from '@/lib/utils';

export type ReactionType = 'like' | 'love' | 'fire' | 'idea' | 'think';

export type ReactionSummary = {
    counts: Record<ReactionType, number>;
    mine: ReactionType[];
};

const ICONS: Record<ReactionType, ComponentType<ReactionIconProps>> = {
    like: ThumbIcon,
    love: HeartIcon,
    fire: FlameIcon,
    idea: BulbIcon,
    think: ThinkIcon,
};

type ReactionIconProps = {
    active: boolean;
    play: number;
    reduceMotion: boolean;
};

/** Un bouton de réaction : icône animée à l'ajout, compteur qui défile. */
function ReactionButton({
    type,
    label,
    count,
    active,
    onToggle,
}: {
    type: ReactionType;
    label: string;
    count: number;
    active: boolean;
    onToggle: () => void;
}) {
    const reduceMotion = usePrefersReducedMotion();
    const Icon = ICONS[type];
    // Augmente à chaque ajout de la réaction : rejoue l'animation de l'icône.
    const [play, setPlay] = useState(0);
    // Sens du défilement du compteur : vers le haut quand il augmente.
    const [previousCount, setPreviousCount] = useState(count);
    const [direction, setDirection] = useState(1);

    if (count !== previousCount) {
        setDirection(count > previousCount ? 1 : -1);
        setPreviousCount(count);
    }

    return (
        <motion.button
            type="button"
            className={`pub-reactions__button pub-reactions__button--${type}`}
            aria-pressed={active}
            aria-label={label}
            title={label}
            onClick={() => {
                if (!active) {
                    setPlay((current) => current + 1);
                    playReactionSound(type);
                }

                onToggle();
            }}
            whileTap={reduceMotion ? undefined : { scale: 0.92 }}
        >
            <Icon active={active} play={play} reduceMotion={reduceMotion} />
            <span className="pub-reactions__count">
                <AnimatePresence initial={false} mode="popLayout">
                    <motion.span
                        key={count}
                        initial={
                            reduceMotion
                                ? { opacity: 0 }
                                : { y: direction * 12, opacity: 0 }
                        }
                        animate={{ y: 0, opacity: 1 }}
                        exit={
                            reduceMotion
                                ? { opacity: 0 }
                                : { y: direction * -12, opacity: 0 }
                        }
                        transition={{ duration: 0.22, ease: 'easeOut' }}
                    >
                        {count}
                    </motion.span>
                </AnimatePresence>
            </span>
        </motion.button>
    );
}

/** Réactions anonymes sous l'article : un clic ajoute, un second retire. */
export default function PostReactions({
    slug,
    initial,
}: {
    slug: string;
    initial: ReactionSummary;
}) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const [summary, setSummary] = useState(initial);

    const toggle = async (type: ReactionType) => {
        const previous = summary;
        const active = summary.mine.includes(type);

        // Affiché tout de suite, corrigé par la réponse du serveur.
        setSummary({
            counts: {
                ...summary.counts,
                [type]: Math.max(0, summary.counts[type] + (active ? -1 : 1)),
            },
            mine: active
                ? summary.mine.filter((mine) => mine !== type)
                : [...summary.mine, type],
        });

        try {
            const response = await fetch(path(`blog/${slug}/reactions`), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ type }),
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            setSummary((await response.json()) as ReactionSummary);
        } catch {
            setSummary(previous);
        }
    };

    return (
        <div
            className="pub-reactions"
            role="group"
            aria-label={t.blog.reactionsLabel}
        >
            {(Object.keys(ICONS) as ReactionType[]).map((type) => (
                <ReactionButton
                    key={type}
                    type={type}
                    label={t.blog.reactions[type]}
                    count={summary.counts[type]}
                    active={summary.mine.includes(type)}
                    onToggle={() => void toggle(type)}
                />
            ))}
        </div>
    );
}
