import * as Dialog from '@radix-ui/react-dialog';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslations } from '@/lib/i18n';

/**
 * Fenêtre modale du site public : même habillage que celle des compétences
 * (voile flouté, carte arrondie, croix qui pivote). Radix gère le focus, Échap
 * et l'accessibilité ; la fenêtre est rendue dans la racine .pub pour hériter
 * du thème clair ou sombre.
 */
export default function PubDialog({
    open,
    onOpenChange,
    title,
    description,
    children,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    children: ReactNode;
}) {
    const t = useTranslations();
    const [container, setContainer] = useState<HTMLElement | null>(null);

    useEffect(() => {
        setContainer(document.querySelector<HTMLElement>('.pub'));
    }, [open]);

    return (
        <Dialog.Root open={open} onOpenChange={onOpenChange}>
            <Dialog.Portal container={container ?? undefined}>
                <Dialog.Overlay className="pub-modal__scrim" />

                <Dialog.Content
                    className="pub-modal pub-modal--form"
                    aria-describedby={description ? 'pub-dialog-description' : undefined}
                >
                    <Dialog.Close className="pub-modal__close" aria-label={t.skills.close} />

                    <Dialog.Title className="pub-modal__title">{title}</Dialog.Title>

                    {description && (
                        <p id="pub-dialog-description" className="pub-modal__lead">
                            {description}
                        </p>
                    )}

                    {children}
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}

/** Message de réussite : le cercle et la coche se dessinent. */
export function DialogDone({
    title,
    text,
    onClose,
    closeLabel,
}: {
    title: string;
    text: string;
    onClose: () => void;
    closeLabel: string;
}) {
    return (
        <div className="pub-drawer__done pub-dialog__done" role="status">
            <svg className="pub-drawer__check" viewBox="0 0 64 64" aria-hidden="true">
                <circle cx="32" cy="32" r="28" />
                <path d="M19 33l9 9 17-19" />
            </svg>
            <h3>{title}</h3>
            <p>{text}</p>
            <button type="button" className="pub-contact__again" onClick={onClose}>
                {closeLabel}
            </button>
        </div>
    );
}
