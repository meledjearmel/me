import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import TestimonialSubmissionController from '@/actions/App/Http/Controllers/TestimonialSubmissionController';
import PubDialog, { DialogDone } from '@/components/public/pub-dialog';
import ReviewVideoField from '@/components/public/review-video-field';
import { useLocale, useTranslations } from '@/lib/i18n';
import type { ReviewContext, ReviewInvitation } from '@/lib/review';

/**
 * « Laisser un avis » : le visiteur écrit son avis, avec une vidéo s'il le
 * souhaite ; il part « en attente » et n'est publié qu'après relecture dans l'admin.
 * Ouvert depuis un lien d'invitation, il est prérempli et rattaché à ce que j'ai choisi.
 */
export default function ReviewDialog({
    open,
    onOpenChange,
    context,
    invitation,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    context?: ReviewContext;
    invitation?: ReviewInvitation;
}) {
    const t = useTranslations();
    const locale = useLocale();
    const [sent, setSent] = useState(false);
    // Réglable dans l'admin (Réglages du site).
    const videoEnabled =
        usePage<{ testimonialVideoEnabled?: boolean }>().props
            .testimonialVideoEnabled !== false;
    // Une fois l'avis envoyé, le lien ne sert plus : les ouvertures suivantes sont ordinaires.
    const [invitationUsed, setInvitationUsed] = useState(false);
    const invite = invitation?.valid && !invitationUsed ? invitation : null;
    const subject = invite ? invite.subject : context?.label;
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
                    onSuccess={() => {
                        setSent(true);
                        setInvitationUsed(true);
                    }}
                >
                    {({ processing, progress, errors }) => (
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

                            {invitation && !invitation.valid && (
                                <p className="pub-review-context">
                                    {t.fab.reviewInvitationInvalid}
                                </p>
                            )}
                            {subject && (
                                <p className="pub-review-context">
                                    {t.fab.reviewAbout} <strong>{subject}</strong>
                                </p>
                            )}
                            {invite ? (
                                <input
                                    type="hidden"
                                    name="invitation"
                                    value={invite.token}
                                />
                            ) : (
                                <>
                                    {context?.projectId && (
                                        <input
                                            type="hidden"
                                            name="project_id"
                                            value={context.projectId}
                                        />
                                    )}
                                    {context?.educationId && (
                                        <input
                                            type="hidden"
                                            name="education_id"
                                            value={context.educationId}
                                        />
                                    )}
                                    {context?.experienceId && (
                                        <input
                                            type="hidden"
                                            name="experience_id"
                                            value={context.experienceId}
                                        />
                                    )}
                                </>
                            )}

                            <div className="pub-drawer__fields">
                                <label className="pub-drawer__field">
                                    <span>{t.fab.yourName}</span>
                                    <input
                                        name="author_name"
                                        defaultValue={invite?.name ?? undefined}
                                        autoComplete="name"
                                        required
                                    />
                                    {errors.author_name && (
                                        <small>{errors.author_name}</small>
                                    )}
                                </label>

                                <label className="pub-drawer__field">
                                    <span>{t.fab.yourEmail}</span>
                                    <input
                                        type="email"
                                        name="author_email"
                                        defaultValue={invite?.email ?? undefined}
                                        autoComplete="email"
                                        required
                                    />
                                    <em>{t.fab.emailNote}</em>
                                    {errors.author_email && (
                                        <small>{errors.author_email}</small>
                                    )}
                                </label>

                                <label className="pub-drawer__field pub-drawer__field--wide">
                                    <span>{t.fab.yourRole}</span>
                                    <input name="author_role" />
                                    {errors.author_role && (
                                        <small>{errors.author_role}</small>
                                    )}
                                </label>

                                <label className="pub-drawer__field pub-drawer__field--wide">
                                    <span>{t.fab.yourReview}</span>
                                    <textarea
                                        name="content"
                                        rows={4}
                                        required
                                        minLength={20}
                                    />
                                    {errors.content && (
                                        <small>{errors.content}</small>
                                    )}
                                </label>

                                {videoEnabled && (
                                    <ReviewVideoField error={errors.video} />
                                )}
                            </div>

                            <button
                                type="submit"
                                className="pub-drawer__send"
                                disabled={processing}
                            >
                                {processing
                                    ? progress?.percentage != null
                                        ? t.fab.uploading(progress.percentage)
                                        : t.contact.sending
                                    : t.fab.reviewSend}
                                <span aria-hidden="true">→</span>
                            </button>
                        </>
                    )}
                </Form>
            )}
        </PubDialog>
    );
}
