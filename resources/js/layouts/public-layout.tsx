import { useEffect, type ReactNode } from 'react';
import CelebrationBubble from '@/components/public/celebration-bubble';
import ContactDrawer from '@/components/public/contact-drawer';
import CustomCursor from '@/components/public/custom-cursor';
import { AudioProvider } from '@/lib/audio';
import { CelebrationProvider } from '@/lib/celebration';
import { ContactDrawerProvider } from '@/lib/contact-drawer';

/**
 * Layout persistant des pages publiques : la musique et le curseur survivent
 * aux changements de page, et la barre de défilement fine n'est appliquée
 * qu'ici (pas à l'admin).
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    useEffect(() => {
        document.documentElement.classList.add('pub-scroll');

        return () => document.documentElement.classList.remove('pub-scroll');
    }, []);

    return (
        <AudioProvider>
            <ContactDrawerProvider>
                <CelebrationProvider>
                    {children}
                    <CelebrationBubble />
                    <ContactDrawer />
                    <CustomCursor />
                </CelebrationProvider>
            </ContactDrawerProvider>
        </AudioProvider>
    );
}
