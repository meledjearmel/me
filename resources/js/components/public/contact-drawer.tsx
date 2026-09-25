import { Form, usePage } from '@inertiajs/react';
import * as Dialog from '@radix-ui/react-dialog';
import { useEffect, useState } from 'react';
import ContactController from '@/actions/App/Http/Controllers/ContactController';
import { useContactDrawer } from '@/lib/contact-drawer';
import { useLocale, useTranslations } from '@/lib/i18n';
import type { PublicProfile } from '@/types';

/**
 * Tiroir de contact : il glisse depuis la droite, envoie le message au
 * formulaire de contact existant (mêmes règles, même piège anti-spam), puis
 * affiche un message de réussite. Radix gère le focus, Échap et l'accessibilité.
 */
export default function ContactDrawer() {
    const t = useTranslations();
    const locale = useLocale();
    const { isOpen, setOpen } = useContactDrawer();
    const { props } = usePage<{ profile: PublicProfile }>();
    const { profile } = props;
    const [container, setContainer] = useState<HTMLElement | null>(null);
    const [sent, setSent] = useState(false);

    // Le tiroir est rendu dans la racine .pub pour hériter des couleurs du thème.
    useEffect(() => {
        setContainer(document.querySelector<HTMLElement>('.pub'));
    }, [isOpen]);

    // Le formulaire réapparaît à la prochaine ouverture.
    useEffect(() => {
        if (isOpen) {
            setSent(false);
        }
    }, [isOpen]);

    const socials = [
        { label: 'GitHub', href: profile.social_links?.github },
        { label: 'LinkedIn', href: profile.social_links?.linkedin },
    ].filter((social): social is { label: string; href: string } =>
        Boolean(social.href),
    );

    return (
        <Dialog.Root open={isOpen} onOpenChange={setOpen}>
            <Dialog.Portal container={container ?? undefined}>
                <Dialog.Overlay className="pub-drawer__scrim" />

                <Dialog.Content
                    className="pub-drawer"
                    aria-describedby="pub-drawer-intro"
                >
                    <Dialog.Close
                        className="pub-drawer__close"
                        aria-label={t.contactDrawer.close}
                    />

                    <div className="pub-drawer__body">
                        <p className="pub-drawer__eyebrow">
                            <span className="pub-drawer__dot" aria-hidden="true" />
                            {t.contactDrawer.eyebrow}
                        </p>

                        <Dialog.Title className="pub-drawer__title">
                            {t.contactDrawer.title}
                        </Dialog.Title>

                        <p id="pub-drawer-intro" className="pub-drawer__intro">
                            {t.contactDrawer.intro}
                        </p>

                        {sent ? (
                            <div className="pub-drawer__done" role="status">
                                <svg
                                    className="pub-drawer__check"
                                    viewBox="0 0 64 64"
                                    aria-hidden="true"
                                >
                                    <circle cx="32" cy="32" r="28" />
                                    <path d="M19 33l9 9 17-19" />
                                </svg>
                                <h3>{t.contactDrawer.doneTitle}</h3>
                                <p>{t.contact.success}</p>
                            </div>
                        ) : (
                            <Form
                                {...ContactController.store.form(locale)}
                                className="pub-drawer__form"
                                resetOnSuccess
                                onSuccess={() => setSent(true)}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        {/* Piège anti-spam : jamais rempli par un humain. */}
                                        <input
                                            type="text"
                                            name="website"
                                            tabIndex={-1}
                                            autoComplete="off"
                                            hidden
                                            aria-hidden="true"
                                        />

                                        <div className="pub-drawer__fields">
                                            <label className="pub-drawer__field">
                                                <span>{t.contact.name}</span>
                                                <input
                                                    name="name"
                                                    autoComplete="name"
                                                    required
                                                />
                                                {errors.name && (
                                                    <small>{errors.name}</small>
                                                )}
                                            </label>

                                            <label className="pub-drawer__field">
                                                <span>{t.contact.email}</span>
                                                <input
                                                    type="email"
                                                    name="email"
                                                    autoComplete="email"
                                                    required
                                                />
                                                {errors.email && (
                                                    <small>{errors.email}</small>
                                                )}
                                            </label>

                                            <label className="pub-drawer__field">
                                                <span>{t.contact.message}</span>
                                                <textarea
                                                    name="message"
                                                    rows={3}
                                                    required
                                                />
                                                {errors.message && (
                                                    <small>{errors.message}</small>
                                                )}
                                            </label>
                                        </div>

                                        <button
                                            type="submit"
                                            className="pub-drawer__send"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? t.contact.sending
                                                : t.contact.submit}
                                            <span aria-hidden="true">→</span>
                                        </button>
                                    </>
                                )}
                            </Form>
                        )}
                    </div>

                    <div className="pub-drawer__alt">
                        <p className="pub-drawer__alt-label">
                            {t.contactDrawer.altLabel}
                        </p>
                        <a
                            className="pub-drawer__mail"
                            href={`mailto:${profile.email}`}
                        >
                            {profile.email}
                        </a>
                        {socials.length > 0 && (
                            <nav
                                className="pub-drawer__socials"
                                aria-label={t.contactDrawer.socials}
                            >
                                {socials.map((social) => (
                                    <a
                                        key={social.label}
                                        href={social.href}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        {social.label} ↗
                                    </a>
                                ))}
                            </nav>
                        )}
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
