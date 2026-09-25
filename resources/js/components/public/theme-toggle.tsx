import type { MouseEvent } from 'react';
import { flushSync } from 'react-dom';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslations } from '@/lib/i18n';

export default function ThemeToggle() {
    const t = useTranslations();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';

    /**
     * Le nouveau thème s'étend en cercle depuis l'endroit cliqué (View Transitions +
     * clip-path). Au clavier, le clic n'a pas de position : on part du bouton.
     */
    const toggle = (event: MouseEvent<HTMLButtonElement>) => {
        const next = isDark ? 'light' : 'dark';
        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (!document.startViewTransition || reduceMotion) {
            updateAppearance(next);

            return;
        }

        const box = event.currentTarget.getBoundingClientRect();
        const x = event.clientX || box.left + box.width / 2;
        const y = event.clientY || box.top + box.height / 2;
        const radius = Math.hypot(
            Math.max(x, window.innerWidth - x),
            Math.max(y, window.innerHeight - y),
        );
        const root = document.documentElement;

        root.classList.add('pub-theming');

        const transition = document.startViewTransition(() => {
            flushSync(() => updateAppearance(next));
        });

        void transition.ready
            .then(() =>
                root.animate(
                    {
                        clipPath: [
                            `circle(0px at ${x}px ${y}px)`,
                            `circle(${radius}px at ${x}px ${y}px)`,
                        ],
                    },
                    {
                        duration: 1500,
                        easing: 'cubic-bezier(0.65, 0, 0.35, 1)',
                        pseudoElement: '::view-transition-new(root)',
                    },
                ),
            )
            .catch(() => undefined);

        void transition.finished.finally(() =>
            root.classList.remove('pub-theming'),
        );
    };

    return (
        <button
            type="button"
            className="pub-round pub-theme"
            aria-label={t.hero.themeToggle}
            onClick={toggle}
        >
            <svg
                className="pub-theme__icon pub-theme__sun"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <circle cx="12" cy="12" r="4" fill="currentColor" />
                <g fill="currentColor">
                    <circle cx="12" cy="3.6" r="1.15" />
                    <circle cx="12" cy="20.4" r="1.15" />
                    <circle cx="3.6" cy="12" r="1.15" />
                    <circle cx="20.4" cy="12" r="1.15" />
                    <circle cx="6.1" cy="6.1" r="1.15" />
                    <circle cx="17.9" cy="6.1" r="1.15" />
                    <circle cx="6.1" cy="17.9" r="1.15" />
                    <circle cx="17.9" cy="17.9" r="1.15" />
                </g>
            </svg>
            <svg
                className="pub-theme__icon pub-theme__moon"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    d="M20 14.2A8 8 0 1 1 10.3 4.2a6.4 6.4 0 0 0 9.7 10z"
                    fill="currentColor"
                />
            </svg>
        </button>
    );
}
