import { motion } from 'framer-motion';
import { useRef, type CSSProperties } from 'react';
import HeroScene from '@/components/public/hero-scene';
import Recorder from '@/components/public/recorder';
import RollingText from '@/components/public/rolling-text';
import { useHeroSequence } from '@/hooks/use-hero-sequence';
import { useScrollAway } from '@/hooks/use-scroll-away';
import { useTranslations } from '@/lib/i18n';
import type { PublicJobProfile, PublicProfile } from '@/types';

const reveal = (delay: number) => ({
    initial: { opacity: 0, y: 24 },
    animate: { opacity: 1, y: 0 },
    transition: { duration: 0.8, delay, ease: [0.22, 1, 0.36, 1] as const },
});

export default function Hero({
    profile,
    jobProfiles,
}: {
    profile: PublicProfile;
    jobProfiles: PublicJobProfile[];
}) {
    const t = useTranslations();
    const sectionRef = useRef<HTMLElement>(null);

    // En descendant, le contenu recule (voir .pub-hero__content dans public.css).
    useScrollAway(sectionRef);

    const { title, word } = useHeroSequence(jobProfiles, profile.headline);

    // Taille stable : calée sur le mot le plus long de tous les profils.
    const longestWord = Math.max(
        1,
        ...jobProfiles.flatMap((jobProfile) =>
            jobProfile.hero_words.map((heroWord) => heroWord.length),
        ),
    );

    return (
        <section ref={sectionRef} className="pub-hero">
            <HeroScene />

            <div className="pub-hero__content">
                <span className="pub-hero__vertical" aria-hidden="true">
                    {t.hero.vertical}
                </span>

                <motion.p className="pub-hero__eyebrow" {...reveal(0)}>
                    <span className="pub-hero__dot" />
                    {t.hero.hello} {profile.name}. {t.hero.a} —
                </motion.p>

                <h1 className="sr-only">
                    {profile.name} —{' '}
                    {jobProfiles
                        .map((jobProfile) => `${jobProfile.hero_title} ${jobProfile.hero_words.join(', ')}`)
                        .join(' · ')}
                </h1>

                <motion.div className="pub-hero__title" aria-hidden="true" {...reveal(0.1)}>
                    <div
                        className="pub-hero__lines"
                        style={{ '--pub-verb-chars': longestWord } as CSSProperties}
                    >
                        <RollingText
                            text={title}
                            className="pub-hero__line"
                            duration={0.7}
                        />

                        <div className="pub-hero__row">
                            <Recorder />
                            <RollingText
                                text={word}
                                className="pub-hero__line pub-hero__verb"
                            />
                        </div>
                    </div>
                </motion.div>
            </div>
        </section>
    );
}
