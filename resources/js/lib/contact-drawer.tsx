import { createContext, useContext, useState, type ReactNode } from 'react';

type ContactDrawerState = {
    isOpen: boolean;
    open: () => void;
    setOpen: (open: boolean) => void;
};

const ContactDrawerContext = createContext<ContactDrawerState | null>(null);

/**
 * État du tiroir de contact, partagé entre le pied de page (son grand titre) et
 * la navigation : les deux l'ouvrent, un seul tiroir existe dans la page.
 */
export function ContactDrawerProvider({ children }: { children: ReactNode }) {
    const [isOpen, setOpen] = useState(false);

    return (
        <ContactDrawerContext.Provider
            value={{
                isOpen,
                open: () => {
                    // Sur la page Contact, le formulaire est déjà là : on y va au lieu d'ouvrir le tiroir.
                    const form = document.getElementById('pub-contact-form');

                    if (form) {
                        form.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });
                        form.querySelector<HTMLElement>(
                            'input:not([hidden])',
                        )?.focus({ preventScroll: true });

                        return;
                    }

                    setOpen(true);
                },
                setOpen,
            }}
        >
            {children}
        </ContactDrawerContext.Provider>
    );
}

export function useContactDrawer(): ContactDrawerState {
    const context = useContext(ContactDrawerContext);

    if (!context) {
        throw new Error(
            'useContactDrawer doit être utilisé dans ContactDrawerProvider.',
        );
    }

    return context;
}
