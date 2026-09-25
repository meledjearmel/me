import * as Dialog from '@radix-ui/react-dialog';
import { useEffect, useState } from 'react';
import type { CSSProperties } from 'react';
import { TechIcon } from '@/components/public/tech-marquee';
import { useTranslations } from '@/lib/i18n';
import type { PublicSkill } from '@/types';

/**
 * Fenêtre qui s'ouvre au clic sur une compétence : ce que c'est, en détail,
 * et les technologies concernées avec leurs logos. Radix gère le focus, Échap
 * et l'accessibilité. Elle est rendue dans la racine .pub pour hériter du thème.
 */
export default function SkillDialog({
    skill,
    onClose,
}: {
    skill: PublicSkill | null;
    onClose: () => void;
}) {
    const t = useTranslations();
    const [container, setContainer] = useState<HTMLElement | null>(null);

    useEffect(() => {
        setContainer(document.querySelector<HTMLElement>('.pub'));
    }, [skill]);

    // Garde le contenu à l'écran pendant l'animation de fermeture.
    const [shown, setShown] = useState<PublicSkill | null>(skill);

    useEffect(() => {
        if (skill) {
            setShown(skill);
        }
    }, [skill]);

    const current = skill ?? shown;
    const paragraphs = (current?.details ?? current?.description ?? '')
        .split(/\n\s*\n/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean);

    return (
        <Dialog.Root open={skill !== null} onOpenChange={(open) => !open && onClose()}>
            <Dialog.Portal container={container ?? undefined}>
                <Dialog.Overlay className="pub-modal__scrim" />

                <Dialog.Content
                    className="pub-modal"
                    style={{ '--domain': current?.domain.color } as CSSProperties}
                    aria-describedby={undefined}
                >
                    {current && (
                        <>
                            <Dialog.Close
                                className="pub-modal__close"
                                aria-label={t.skills.close}
                            />

                            <p className="pub-modal__domain">
                                <span aria-hidden="true" />
                                {current.domain.label}
                            </p>

                            <Dialog.Title className="pub-modal__title">
                                {current.name}
                            </Dialog.Title>

                            {current.description && current.details && (
                                <p className="pub-modal__lead">{current.description}</p>
                            )}

                            <div className="pub-modal__body">
                                {paragraphs.map((paragraph, index) => (
                                    <p key={index}>{paragraph}</p>
                                ))}
                            </div>

                            {current.technologies.length > 0 && (
                                <div className="pub-modal__tools">
                                    <p className="pub-modal__tools-label">
                                        {t.skills.tools}
                                    </p>
                                    <ul>
                                        {current.technologies.map((technology) => (
                                            <li key={technology.id}>
                                                <TechIcon
                                                    technology={technology}
                                                    className="pub-logo"
                                                />
                                                <span>{technology.name}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </>
                    )}
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
