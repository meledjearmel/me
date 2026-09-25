import { useEffect, useState } from 'react';
import type { PublicJobProfile } from '@/types';

/**
 * Deux animations liées :
 *  - le mot (Codes, Builds, Ships…) change toutes les `wordMs` ;
 *  - le titre du profil change quand tous ses mots sont passés, puis le
 *    profil suivant recommence avec son premier mot.
 */
export function useHeroSequence(
    jobProfiles: PublicJobProfile[],
    fallbackTitle: string,
    wordMs = 1700,
): { title: string; word: string } {
    const [profileIndex, setProfileIndex] = useState(0);
    const [wordIndex, setWordIndex] = useState(0);

    const profile = jobProfiles[profileIndex % Math.max(jobProfiles.length, 1)];
    const words = profile?.hero_words ?? [];
    const wordCount = words.length;
    const profileCount = jobProfiles.length;

    useEffect(() => {
        if (wordCount === 0 && profileCount < 2) {
            return;
        }

        const timer = window.setTimeout(() => {
            if (wordIndex < wordCount - 1) {
                setWordIndex(wordIndex + 1);

                return;
            }

            setProfileIndex((current) => (current + 1) % Math.max(profileCount, 1));
            setWordIndex(0);
        }, wordMs);

        return () => window.clearTimeout(timer);
    }, [wordIndex, profileIndex, wordCount, profileCount, wordMs]);

    return {
        title: profile?.hero_title ?? fallbackTitle,
        word: words[wordIndex] ?? '',
    };
}
