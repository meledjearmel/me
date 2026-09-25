import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import LanguageSwitch from '@/components/public/language-switch';
import MobileMenu from '@/components/public/mobile-menu';
import MusicButton from '@/components/public/music-button';
import ThemeToggle from '@/components/public/theme-toggle';
import { useContactDrawer } from '@/lib/contact-drawer';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicProfile } from '@/types';

/**
 * Suit le scroll : la pastille se réduit quand on descend et revient en
 * remontant. Le thème est géré en CSS (classe dark) pour rester identique
 * entre le rendu serveur et le client.
 */
function useNavState(overHero: boolean) {
    const [compact, setCompact] = useState(false);
    const [pastHero, setPastHero] = useState(false);

    useEffect(() => {
        let lastY = window.scrollY;

        const onScroll = () => {
            const y = window.scrollY;

            if (y > 50 && y > lastY) {
                setCompact(true);
            } else if (y < lastY) {
                setCompact(false);
            }

            setPastHero(y > window.innerHeight - 90);
            lastY = y;
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const onDark = overHero && !pastHero;

    return { compact, onDark };
}

export default function SiteHeader({ overHero = false }: { overHero?: boolean }) {
    const t = useTranslations();
    const path = useLocalizedPath();
    const { props, url } = usePage<{ profile: PublicProfile }>();
    const { compact, onDark } = useNavState(overHero);
    const { profile } = props;
    const [menuOpen, setMenuOpen] = useState(false);
    const { open: openContact } = useContactDrawer();

    // Le menu plein écran n'existe qu'en petite largeur : on le ferme si la
    // fenêtre s'élargit.
    useEffect(() => {
        const desktop = window.matchMedia('(min-width: 708px)');
        const closeOnDesktop = () => desktop.matches && setMenuOpen(false);

        desktop.addEventListener('change', closeOnDesktop);

        return () => desktop.removeEventListener('change', closeOnDesktop);
    }, []);

    // Page courante : l'adresse égale le lien, ou en est une sous-page (/projects/xyz).
    const currentPath = url.split(/[?#]/)[0].replace(/\/$/, '');
    const isActive = (href: string) =>
        currentPath === href || currentPath.startsWith(`${href}/`);

    const links: { href: string; label: string }[] = [
        { href: path('about'), label: t.nav.about },
        { href: path('skills'), label: t.nav.skills },
        { href: path('projects'), label: t.nav.projects },
    ];

    const menuLinks = [...links, { href: path('contact'), label: t.nav.contact }];

    const initials = profile.name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('');

    return (
        <header
            className={`pub-nav ${onDark || menuOpen ? 'is-on-dark' : 'is-on-light'}${menuOpen ? ' is-menu-open' : ''}`}
        >
            <div className="pub-nav__side pub-nav__location">
                {profile.location && (
                    <>
                        <svg
                            className="pub-nav__pin"
                            viewBox="0 0 14 20"
                            aria-hidden="true"
                        >
                            <path
                                fill="currentColor"
                                d="M7 0C3.1 0 0 3.1 0 7c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7Zm0 9.5A2.5 2.5 0 1 1 7 4.500a2.500 2.500 0 0 1 0 5Z"
                            />
                        </svg>
                        <span>{profile.location}</span>
                    </>
                )}
            </div>

            <div className={`pub-pill${compact ? ' is-compact' : ''}`}>
                <Link
                    href={path()}
                    className="pub-avatar"
                    aria-label={profile.name}
                >
                    {profile.photo_url ? (
                        <img src={profile.photo_url} alt="" />
                    ) : (
                        <span>{initials}</span>
                    )}
                </Link>

                <span className="pub-avail" aria-hidden={!compact}>
                    <span className="pub-avail__text">
                        {t.hero.availableForWork}
                    </span>
                    <span className="pub-avail__dot" />
                </span>

                <nav className="pub-pill__links" aria-label="Navigation">
                    {links.map((link) => (
                        <Link
                            key={link.href}
                            href={link.href}
                            className="pub-link"
                            aria-current={isActive(link.href) ? 'page' : undefined}
                        >
                            {link.label}
                        </Link>
                    ))}
                    <Link
                        href={path('contact')}
                        className="pub-cta"
                        aria-current={
                            isActive(path('contact')) ? 'page' : undefined
                        }
                        onClick={(event) => {
                            // Avec JavaScript, le tiroir s'ouvre ; sans, la page contact reste accessible.
                            event.preventDefault();
                            openContact();
                        }}
                    >
                        {t.nav.contact}
                    </Link>
                </nav>

                <button
                    type="button"
                    className="pub-burger"
                    aria-label={menuOpen ? t.nav.closeMenu : t.nav.openMenu}
                    aria-expanded={menuOpen}
                    aria-controls="pub-menu"
                    onClick={() => setMenuOpen((current) => !current)}
                >
                    <span />
                    <span />
                </button>
            </div>

            <div className="pub-nav__side pub-nav__tools">
                <LanguageSwitch />
                <MusicButton />
                <ThemeToggle />
            </div>

            <MobileMenu
                open={menuOpen}
                links={menuLinks.map((link) => ({
                    ...link,
                    active: isActive(link.href),
                }))}
                location={profile.location}
                onClose={() => setMenuOpen(false)}
            />
        </header>
    );
}
