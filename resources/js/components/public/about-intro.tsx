import PageHero from '@/components/public/page-hero';
import Polaroids from '@/components/public/polaroids';
import { useTranslations } from '@/lib/i18n';

/**
 * Bandeau de la page À propos : le titre, et deux photos en polaroïd qu'on peut
 * déplacer. Le texte de présentation suit dans `AboutStory`.
 */
export default function AboutIntro({ years }: { years: number }) {
    const t = useTranslations();

    return (
        <PageHero
            id="pub-about-title"
            eyebrow={t.about.title}
            title={t.about.heading}
            lead={
                years > 0 ? (
                    <p>
                        <strong>{years}</strong> {t.about.yearsHero}
                    </p>
                ) : undefined
            }
            aside={
                <Polaroids
                    photos={[
                        {
                            src: '/images/about/portrait-costume.jpg',
                            alt: t.about.portraitSuit,
                            width: 250,
                            height: 332,
                            rotate: 5,
                            x: 70,
                            y: -6,
                            focus: '50% 25%',
                        },
                        {
                            src: '/images/about/portrait-orange.jpg',
                            alt: t.about.portraitOrange,
                            width: 250,
                            height: 228,
                            rotate: -6,
                            x: -60,
                            y: 150,
                            focus: '50% 30%',
                        },
                    ]}
                />
            }
        />
    );
}
