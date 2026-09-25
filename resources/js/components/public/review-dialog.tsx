import { Form } from '@inertiajs/react';
import { useState } from 'react';
import TestimonialSubmissionController from '@/actions/App/Http/Controllers/TestimonialSubmissionController';
import PubDialog, { DialogDone } from '@/components/public/pub-dialog';
import { useLocale, useTranslations } from '@/lib/i18n';

/**
 * « Laisser un avis » : le visiteur écrit son avis, il part « en attente » et
 * n'est publié qu'après relecture dans l'admin.
 */
export default function ReviewDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const [sent, setSent] = useState(false);

    const change = (next: boolean) => {
        onOpenChange(next);

        if (!next) {
            // Le formulaire réapparaît vide à la prochaine ouverture.
            window.setTimeout(() => setSent(false), 300);
        }
    };

    return (
        <PubDialog
            open={open}
            onOpenChange={change}
            title={t.fab.reviewTitle}
            description={t.fab.reviewIntro}
        >
            {sent ? (
                <DialogDone
                    title={t.fab.reviewDoneTitle}
                    text={t.fab.reviewDone}
                    onClose={() => change(false)}
                    closeLabel={t.skills.close}
                />
            ) : (
                <Form
                    {...TestimonialSubmissionController.store.form(locale)}
                    className="pub-dialog__form"
                    resetOnSuccess
                    onSuccess={() => setSent(true)}
                >
                    {({ processing, errors }) => (
                        <>
                            {/* Piège anti-spam : jamais rempli par un humain. */}
                            <input type="text" name="website" tabIndex={-1} autoComplete="off" hidden aria-hidden="true" />

                            <div className="pub-drawer__fields">
                                <label className="pub-drawer__field">
                                    <span>{t.fab.yourName}</span>
                                    <input name="author_name" autoComplete="name" required />
                                    {errors.author_name && <small>{errors.author_name}</small>}
                                </label>

                                <label className="pub-drawer__field">
                                    <span>{t.fab.yourEmail}</span>
                                    <input type="email" name="author_email" autoComplete="email" required />
                                    <em>{t.fab.emailNote}</em>
                                    {errors.author_email && <small>{errors.author_email}</small>}
                                </label>

                                <label className="pub-drawer__field pub-drawer__field--wide">
                                    <span>{t.fab.yourRole}</span>
                                    <input name="author_role" />
                                    {errors.author_role && <small>{errors.author_role}</small>}
                                </label>

                                <label className="pub-drawer__field pub-drawer__field--wide">
                                    <span>{t.fab.yourReview}</span>
                                    <textarea name="content" rows={4} required minLength={20} />
                                    {errors.content && <small>{errors.content}</small>}
                                </label>
                            </div>

                            <button type="submit" className="pub-drawer__send" disabled={processing}>
                                {processing ? t.contact.sending : t.fab.reviewSend}
                                <span aria-hidden="true">→</span>
                            </button>
                        </>
                    )}
                </Form>
            )}
        </PubDialog>
    );
}
