import { Form } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { ChangeEvent } from 'react';
import TestimonialSubmissionController from '@/actions/App/Http/Controllers/TestimonialSubmissionController';
import PubDialog, { DialogDone } from '@/components/public/pub-dialog';
import VideoRecorder, {
    canRecordVideo,
} from '@/components/public/video-recorder';
import { useLocale, useTranslations } from '@/lib/i18n';

/** Mêmes limites que le serveur : 95 Mo (plafond Cloudflare) et 3 minutes. */
const MAX_VIDEO_BYTES = 95 * 1024 * 1024;
const MAX_VIDEO_SECONDS = 180;

/** Durée d'une vidéo locale, lue dans ses métadonnées (null si illisible). */
function videoDuration(file: File): Promise<number | null> {
    return new Promise((resolve) => {
        const video = document.createElement('video');
        const url = URL.createObjectURL(file);
        const done = (value: number | null) => {
            URL.revokeObjectURL(url);
            resolve(value);
        };

        video.preload = 'metadata';
        video.onloadedmetadata = () =>
            done(Number.isFinite(video.duration) ? video.duration : null);
        video.onerror = () => done(null);
        video.src = url;
    });
}

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
    const [videoError, setVideoError] = useState<string | null>(null);
    const videoInputRef = useRef<HTMLInputElement>(null);
    const [canRecord, setCanRecord] = useState(false);
    const [recorderOpen, setRecorderOpen] = useState(false);
    const [recordedName, setRecordedName] = useState<string | null>(null);

    // Lu après le montage : le rendu serveur ne connaît pas la caméra.
    useEffect(() => setCanRecord(canRecordVideo()), []);

    // La vidéo filmée rejoint le champ fichier : l'envoi reste le même.
    const attachRecording = (file: File) => {
        const input = videoInputRef.current;

        if (input) {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
        }

        setVideoError(null);
        setRecordedName(file.name);
        setRecorderOpen(false);
    };

    // Refusée dans le navigateur avant tout envoi : inutile de téléverser 200 Mo
    // pour se voir répondre « trop lourd ».
    const checkVideo = async (event: ChangeEvent<HTMLInputElement>) => {
        const input = event.currentTarget;
        const file = input.files?.[0];
        setVideoError(null);
        setRecordedName(null);

        if (!file) {
            return;
        }

        if (file.size > MAX_VIDEO_BYTES) {
            setVideoError(t.fab.videoTooHeavy);
            input.value = '';

            return;
        }

        const duration = await videoDuration(file);

        if (duration !== null && duration > MAX_VIDEO_SECONDS + 1) {
            setVideoError(t.fab.videoTooLong);
            input.value = '';
        }
    };

    const change = (next: boolean) => {
        onOpenChange(next);

        if (!next) {
            // Le formulaire réapparaît vide à la prochaine ouverture.
            window.setTimeout(() => {
                setSent(false);
                setVideoError(null);
                setRecorderOpen(false);
                setRecordedName(null);
            }, 300);
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

                            <div className="pub-drawer__fields">
                                <label className="pub-drawer__field">
                                    <span>{t.fab.yourName}</span>
                                    <input
                                        name="author_name"
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

                                <label className="pub-drawer__field pub-drawer__field--wide">
                                    <span>{t.fab.yourVideo}</span>
                                    <input
                                        ref={videoInputRef}
                                        type="file"
                                        name="video"
                                        accept="video/*"
                                        className="pub-drawer__file"
                                        onChange={checkVideo}
                                    />
                                    <em>
                                        {recordedName
                                            ? t.fab.recordReady
                                            : t.fab.videoNote}
                                    </em>
                                    {(videoError || errors.video) && (
                                        <small>
                                            {videoError ?? errors.video}
                                        </small>
                                    )}
                                </label>

                                {canRecord && (
                                    <div className="pub-drawer__field pub-drawer__field--wide">
                                        {recorderOpen ? (
                                            <VideoRecorder
                                                onUse={attachRecording}
                                                onClose={() =>
                                                    setRecorderOpen(false)
                                                }
                                            />
                                        ) : (
                                            <button
                                                type="button"
                                                className="pub-recorder__open"
                                                onClick={() =>
                                                    setRecorderOpen(true)
                                                }
                                            >
                                                <span aria-hidden="true">
                                                    ●
                                                </span>{' '}
                                                {t.fab.recordOpen}
                                            </button>
                                        )}
                                    </div>
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
