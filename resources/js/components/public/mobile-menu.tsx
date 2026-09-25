import { Link } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useEffect } from 'react';

type MenuLink = { href: string; label: string; active?: boolean };

/**
 * Menu plein écran pour les petites largeurs : liens numérotés en grand.
 * Il se ferme avec Échap ou au choix d'un lien, et bloque le défilement
 * de la page tant qu'il est ouvert.
 */
export default function MobileMenu({
    open,
    links,
    location,
    onClose,
}: {
    open: boolean;
    links: MenuLink[];
    location: string | null;
    onClose: () => void;
}) {
    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open, onClose]);

    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    id="pub-menu"
                    className="pub-menu"
                    role="dialog"
                    aria-modal="true"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.3 }}
                >
                    <nav>
                        <ul className="pub-menu__links">
                            {links.map((link, index) => (
                                <motion.li
                                    key={link.href}
                                    initial={{ opacity: 0, y: 24 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: 0.08 * index + 0.1,
                                        ease: [0.22, 1, 0.36, 1],
                                    }}
                                >
                                    <Link
                                        href={link.href}
                                        className="pub-menu__link"
                                        aria-current={
                                            link.active ? 'page' : undefined
                                        }
                                        onClick={onClose}
                                    >
                                        <span className="pub-menu__num">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <span className="pub-menu__word">
                                            {link.label}
                                        </span>
                                    </Link>
                                </motion.li>
                            ))}
                        </ul>
                    </nav>

                    {location && <p className="pub-menu__location">{location}</p>}
                </motion.div>
            )}
        </AnimatePresence>
    );
}
