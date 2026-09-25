import { createInertiaApp } from '@inertiajs/react';
import type { VisitOptions } from '@inertiajs/core';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { waveKeyframes } from '@/lib/page-wave';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/** Pages publiques : /fr/... et /en/... */
const PUBLIC_PATH = /^\/(fr|en)(\/|$)/;

/**
 * Transition entre deux pages publiques (View Transitions) : l'ancienne page
 * reste en place, la nouvelle se dévoile derrière une vague qui monte du bas
 * de l'écran (voir lib/page-wave.ts et public.css).
 * Pas de transition pour l'admin, les formulaires, la même page, ni si le
 * visiteur préfère réduire les animations.
 */
function publicPageTransition(
    href: string,
    options: VisitOptions,
): VisitOptions {
    if (typeof window === 'undefined') {
        return {};
    }

    const target = new URL(href, window.location.origin);
    const isNavigation = !options.method || options.method === 'get';
    const isPublic =
        PUBLIC_PATH.test(target.pathname) &&
        PUBLIC_PATH.test(window.location.pathname);

    if (
        !isNavigation ||
        !isPublic ||
        target.pathname === window.location.pathname ||
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
        return {};
    }

    return {
        viewTransition: (transition) => {
            const root = document.documentElement;

            root.classList.add('pub-page-transition');
            void transition.ready
                .then(() =>
                    root.animate(
                        { clipPath: waveKeyframes() },
                        {
                            duration: 1300,
                            easing: 'cubic-bezier(0.65, 0, 0.35, 1)',
                            pseudoElement: '::view-transition-new(root)',
                        },
                    ),
                )
                .catch(() => undefined);
            void transition.finished.finally(() =>
                root.classList.remove('pub-page-transition'),
            );
        },
    };
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    defaults: {
        visitOptions: publicPageTransition,
    },
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
