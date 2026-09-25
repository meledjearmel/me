import { memo, useCallback, useMemo, useState } from 'react';
import type { CSSProperties } from 'react';
import { useTranslations } from '@/lib/i18n';
import type { PublicTechnology } from '@/types';

/**
 * Logo seul, sans pastille. Si la technologie a une variante par thème, les
 * deux images sont rendues et le CSS (classe dark) affiche la bonne.
 */
/**
 * Taille visuelle par logo, comme la référence (hauteurs réglées logo par logo
 * pour que chacun paraisse de la même taille) : 1 = hauteur de base. Les logos
 * larges avec du texte (MySQL) ont besoin d'être plus hauts pour rester lisibles,
 * les très larges (Laravel, Pest) ou massifs (Inertia, Alpine.js) plus bas.
 */
const LOGO_SCALE: Record<string, number> = {
    mysql: 1.55,
    laravel: 0.8,
    pest: 0.62,
    inertia: 0.72,
    alpinejs: 0.72,
    phpstan: 1.05,
    expo: 0.9,
};

export function TechIcon({
    technology,
    className = 'pub-marquee__icon',
}: {
    technology: PublicTechnology;
    className?: string;
}) {
    const { icon_light_url: light, icon_dark_url: dark, name } = technology;

    // Technologie sans logo (ex. une simple bibliothèque) : rien à afficher.
    if (!light && !dark) {
        return null;
    }

    if (light === dark) {
        return (
            <img
                src={light ?? undefined}
                alt={name}
                className={className}
                loading="eager"
                decoding="async"
            />
        );
    }

    return (
        <>
            <img
                src={light ?? undefined}
                alt={name}
                className={`${className} ${className}--light`}
                loading="eager"
                decoding="async"
            />
            <img
                src={dark ?? undefined}
                alt=""
                className={`${className} ${className}--dark`}
                loading="eager"
                decoding="async"
            />
        </>
    );
}

type TechTip = { name: string; description: string; x: number; y: number };

type ShowTip = (element: HTMLElement, technology: PublicTechnology) => void;

const Row = memo(function Row({
    technologies,
    reverse = false,
    className = '',
    onShow,
    onHide,
}: {
    technologies: PublicTechnology[];
    reverse?: boolean;
    className?: string;
    onShow: ShowTip;
    onHide: () => void;
}) {
    // Deux copies identiques : la piste défile de -50 % puis boucle sans saut.
    return (
        <div className={`pub-marquee__row ${className}`}>
            <ul
                className={`pub-marquee__track${reverse ? ' is-reverse' : ''}`}
            >
                {[0, 1].flatMap((copy) =>
                    technologies.map((technology) => (
                        <li
                            key={`${copy}-${technology.id}`}
                            className="pub-marquee__item"
                            style={
                                {
                                    '--pub-logo-scale':
                                        LOGO_SCALE[technology.icon ?? ''] ?? 1,
                                } as CSSProperties
                            }
                            aria-hidden={copy === 1}
                            onMouseEnter={(event) =>
                                onShow(event.currentTarget, technology)
                            }
                            onMouseLeave={onHide}
                        >
                            <TechIcon technology={technology} />
                        </li>
                    )),
                )}
            </ul>
        </div>
    );
});

/**
 * Bande défilante des technologies. Sur mobile, une seconde rangée défile en
 * sens inverse (la première seule suffit sur grand écran).
 */
export default function TechMarquee({
    technologies,
    label,
}: {
    technologies: PublicTechnology[];
    label: string;
}) {
    const t = useTranslations();
    const [tip, setTip] = useState<TechTip | null>(null);

    const descriptions: Record<string, string> = t.home.techDescriptions;

    const hideTip = useCallback(() => setTip(null), []);

    const showTip: ShowTip = useCallback((element, technology) => {
        const description = descriptions[technology.icon ?? ''];

        if (!description) {
            return;
        }

        const rect = element.getBoundingClientRect();

        setTip({
            name: technology.name,
            description,
            x: rect.left + rect.width / 2,
            y: rect.top,
        });
    }, [descriptions]);

    // Logos seuls : une techno sans logo n'apparaît pas dans la bande.
    // Listes mémorisées : sans elles, chaque infobulle re-rendrait les pistes.
    const withLogo = useMemo(
        () =>
            technologies.filter(
                (technology) =>
                    technology.icon_light_url || technology.icon_dark_url,
            ),
        [technologies],
    );
    const reversed = useMemo(() => [...withLogo].reverse(), [withLogo]);

    if (withLogo.length === 0) {
        return null;
    }

    return (
        <div className="pub-marquee" role="group" aria-label={label}>
            <Row
                technologies={withLogo}
                onShow={showTip}
                onHide={hideTip}
            />
            <Row
                technologies={reversed}
                reverse
                className="pub-marquee__row--mobile"
                onShow={showTip}
                onHide={hideTip}
            />
            {tip && (
                <div
                    className="pub-marquee__tip"
                    role="tooltip"
                    style={{ left: tip.x, top: tip.y }}
                >
                    <strong>{tip.name}</strong>
                    <span>{tip.description}</span>
                </div>
            )}
        </div>
    );
}
