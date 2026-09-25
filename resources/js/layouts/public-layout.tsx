import { useEffect, type ReactNode } from 'react';
import ContactDrawer from '@/components/public/contact-drawer';
import CustomCursor from '@/components/public/custom-cursor';
import { AudioProvider } from '@/lib/audio';
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
                {children}
                <ContactDrawer />
                <CustomCursor />
            </ContactDrawerProvider>
        </AudioProvider>
    );
}
