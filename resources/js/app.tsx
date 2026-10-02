import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import type { VisitOptions } from '@inertiajs/core';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { waveKeyframes } from '@/lib/page-wave';
import PublicLayout from '@/layouts/public-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Layouts de l'admin, de l'authentification et des réglages : chargés à la
 * demande avec la page qui en a besoin, pour qu'un visiteur du site public ne
 * télécharge pas toute l'interface d'administration.
 */
const loadedLayouts: {
    app?: typeof import('@/layouts/app-layout').default;
    auth?: typeof import('@/layouts/auth-layout').default;
    settings?: typeof import('@/layouts/settings/layout').default;
} = {};

async function loadLayoutFor(name: string): Promise<void> {
    if (name.startsWith('public/')) {
        return;
    }

    if (name.startsWith('auth/')) {
        loadedLayouts.auth ??= (await import('@/layouts/auth-layout')).default;

        return;
    }

    loadedLayouts.app ??= (await import('@/layouts/app-layout')).default;

    if (name.startsWith('settings/')) {
        loadedLayouts.settings ??= (
            await import('@/layouts/settings/layout')
        ).default;
    }
}

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
    // Même résolution que celle générée par @inertiajs/vite, plus le layout.
    resolve: async (name) => {
        const pages = import.meta.glob<{
            default: ResolvedComponent;
        }>('./pages/**/*.tsx');
        const [module] = await Promise.all([
            pages[`./pages/${name}.tsx`]?.(),
            loadLayoutFor(name),
        ]);

        if (!module) {
            throw new Error(`Page not found: ${name}`);
        }

        return module.default;
    },
    // Le layout a déjà été chargé par `resolve` : ce choix reste synchrone.
    layout: (name) => {
        switch (true) {
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('auth/'):
                return loadedLayouts.auth;
            case name.startsWith('settings/'):
                return [loadedLayouts.app, loadedLayouts.settings];
            default:
                return loadedLayouts.app;
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
