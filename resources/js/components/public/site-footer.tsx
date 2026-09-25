import { motion, useReducedMotion, useTransform } from 'framer-motion';
import { useRef } from 'react';
import Skyline from '@/components/public/skyline';
import Letters from '@/components/public/letters';
import { useViewportProgress } from '@/hooks/use-viewport-progress';
import { useContactDrawer } from '@/lib/contact-drawer';
import { useTranslations } from '@/lib/i18n';
import type { PublicProfile } from '@/types';

/** Titre : son centre passe du bas de la fenêtre (0 %) au milieu (100 %). */
const HEADLINE_RANGE = (height: number, viewport: number): [number, number] => [
    viewport - height / 2,
    0.5 * viewport - height / 2,
];

export default function SiteFooter({
    name,
    email,
    socialLinks,
}: {
    name: string;
    email: PublicProfile['email'];
    socialLinks: PublicProfile['social_links'];
}) {
    const t = useTranslations();
    const { open } = useContactDrawer();
    const reduceMotion = useReducedMotion();
    const headlineRef = useRef<HTMLDivElement>(null);
    const progress = useViewportProgress(headlineRef, HEADLINE_RANGE);

    // Le titre grossit à mesure qu'il monte (comme la référence), sans jamais
    // disparaître complètement.
    const scale = useTransform(progress, [0, 1], [0.35, 1]);
    const opacity = useTransform(progress, [0, 1], [0.25, 1]);

    const links = [
        { label: 'Email', href: `mailto:${email}` },
        { label: 'GitHub', href: socialLinks?.github },
        { label: 'LinkedIn', href: socialLinks?.linkedin },
    ].filter((link): link is { label: string; href: string } =>
        Boolean(link.href),
    );

    return (
        <footer className="pub-footer">
            <Skyline className="pub-footer__crest" />

            <div className="pub-footer__inner">
                <p className="pub-footer__sub">{t.footer.sub}</p>

                <div ref={headlineRef} className="pub-footer__headline-wrap">
                    <motion.h2
                        className="pub-footer__headline"
                        style={reduceMotion ? undefined : { scale, opacity }}
                    >
                        <button
                            type="button"
                            className="pub-footer__headline-btn"
                            onClick={open}
                            data-cursor-label={t.footer.cursorContact}
                        >
                            <Letters text={t.footer.headline} />
                        </button>
                    </motion.h2>
                </div>
            </div>

            <div className="pub-footer__hills" aria-hidden="true">
                <svg viewBox="0 0 1440 320" preserveAspectRatio="none">
                    <path
                        fill="var(--pub-hill-a)"
                        d="M0 150 C 240 70 440 70 660 140 S 1120 210 1440 110 V320 H0Z"
                    />
                    <path
                        fill="var(--pub-hill-b)"
                        d="M0 220 C 300 140 540 200 780 225 S 1180 250 1440 190 V320 H0Z"
                    />
                    <path
                        fill="var(--pub-hill-c)"
                        d="M0 280 C 260 240 560 300 860 270 S 1240 250 1440 280 V320 H0Z"
                    />
                </svg>
            </div>

            <div className="pub-footer__bar">
                <p>
                    © {new Date().getFullYear()} {name}. {t.footer.rights}{' '}
                    {t.footer.inspiredBy}{' '}
                    <a
                        href="https://zainabkabira.com/"
                        target="_blank"
                        rel="noreferrer"
                    >
                        Zainab Kabira
                    </a>
                    .
                </p>

                <nav aria-label={t.footer.linksLabel}>
                    {links.map((link) => (
                        <a
                            key={link.label}
                            href={link.href}
                            {...(link.href.startsWith('http')
                                ? { target: '_blank', rel: 'noreferrer' }
                                : {})}
                        >
                            {link.label}
                        </a>
                    ))}
                </nav>
            </div>
        </footer>
    );
}
