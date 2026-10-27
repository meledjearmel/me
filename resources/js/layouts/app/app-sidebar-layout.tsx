import { useState } from 'react';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

/**
 * Gabarit de la console (référence Donezo, comme la console de Folio) : pleine page,
 * barre latérale et en-tête fixes en cartes, seul le contenu défile. Couleurs : la
 * palette choisie (quatre, reprises de l'app mobile), en clair ou en sombre.
 */
export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <div className="console h-dvh overflow-hidden bg-[var(--bg)] text-[var(--fg)]">
            <div className="flex h-full gap-3 p-3">
                <AppSidebar
                    open={menuOpen}
                    onClose={() => setMenuOpen(false)}
                />

                <div className="flex min-w-0 flex-1 flex-col gap-3">
                    <AppSidebarHeader
                        breadcrumbs={breadcrumbs}
                        onMenu={() => setMenuOpen(true)}
                    />
                    <main
                        scroll-region=""
                        className="flex min-h-0 flex-1 flex-col overflow-x-clip overflow-y-auto overscroll-contain rounded-[1.6rem] bg-[color-mix(in_oklab,var(--soft)_55%,var(--bg))]"
                    >
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}
