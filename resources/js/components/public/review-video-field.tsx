import { useEffect, useRef, useState } from 'react';
import type { ChangeEvent } from 'react';
import PubDialog from '@/components/public/pub-dialog';
import VideoRecorder, {
    canRecordVideo,
} from '@/components/public/video-recorder';
import { useTranslations } from '@/lib/i18n';

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

function formatDuration(seconds: number): string {
    const rounded = Math.round(seconds);

    return `${Math.floor(rounded / 60)}:${String(rounded % 60).padStart(2, '0')}`;
}

function formatSize(bytes: number): string {
    return `${(bytes / (1024 * 1024)).toFixed(1).replace('.0', '')} Mo`;
}

type Selected = { file: File; url: string; duration: number | null };

function UploadIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
                d="M12 15V4m0 0-4 4m4-4 4 4M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function CameraIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect
                x="3"
                y="6"
                width="13"
                height="12"
                rx="3"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
            />
            <path
                d="m16 10.5 5-3v9l-5-3"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinejoin="round"
            />
        </svg>
    );
}

/**
 * Vidéo de l'avis : deux tuiles (importer un fichier ou filmer tout de suite),
 * puis une carte d'aperçu de ce qui partira. Le fichier vit dans un champ
 * `video` caché : l'envoi du formulaire ne change pas, filmée ou importée.
 */
export default function ReviewVideoField({ error }: { error?: string }) {
    const t = useTranslations();
    const inputRef = useRef<HTMLInputElement>(null);
    const [canRecord, setCanRecord] = useState(false);
    const [recording, setRecording] = useState(false);
    const [selected, setSelected] = useState<Selected | null>(null);
    const [localError, setLocalError] = useState<string | null>(null);

    // Lu après le montage : le rendu serveur ne connaît pas la caméra.
    useEffect(() => setCanRecord(canRecordVideo()), []);

    useEffect(
        () => () => {
            if (selected) {
                URL.revokeObjectURL(selected.url);
            }
        },
        [selected],
    );

    const clear = () => {
        if (inputRef.current) {
            inputRef.current.value = '';
        }

        setSelected(null);
    };

    // Refusée dans le navigateur avant tout envoi : inutile de téléverser 200 Mo
    // pour se voir répondre « trop lourd ».
    const accept = async (file: File): Promise<boolean> => {
        setLocalError(null);

        if (file.size > MAX_VIDEO_BYTES) {
            setLocalError(t.fab.videoTooHeavy);

            return false;
        }

        const duration = await videoDuration(file);

        if (duration !== null && duration > MAX_VIDEO_SECONDS + 1) {
            setLocalError(t.fab.videoTooLong);

            return false;
        }

        setSelected({ file, url: URL.createObjectURL(file), duration });

        return true;
    };

    const onFileChange = async (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.currentTarget.files?.[0];

        if (file && !(await accept(file))) {
            clear();
        }
    };

    const takeRecording = async (file: File) => {
        setRecording(false);

        if (!(await accept(file)) || !inputRef.current) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        inputRef.current.files = transfer.files;
    };

    const message = localError ?? error;

    return (
        <div className="pub-drawer__field pub-drawer__field--wide pub-vfield">
            <span>{t.fab.yourVideo}</span>

            <input
                ref={inputRef}
                id="review-video"
                type="file"
                name="video"
                accept="video/*"
                className="pub-vfield__input"
                onChange={onFileChange}
            />

            {selected ? (
                <div className="pub-vfield__selected">
                    <video
                        className="pub-vfield__thumb"
                        src={selected.url}
                        controls
                        playsInline
                        preload="metadata"
                    />
                    <div className="pub-vfield__meta">
                        <strong>{t.fab.videoReady}</strong>
                        <span>
                            {[
                                selected.duration !== null &&
                                    formatDuration(selected.duration),
                                formatSize(selected.file.size),
                            ]
                                .filter(Boolean)
                                .join(' · ')}
                        </span>
                        <button
                            type="button"
                            className="pub-vfield__remove"
                            onClick={clear}
                        >
                            {t.fab.videoRemove}
                        </button>
                    </div>
                </div>
            ) : (
                <div className="pub-vfield__choices">
                    <label htmlFor="review-video" className="pub-vfield__tile">
                        <UploadIcon />
                        <strong>{t.fab.videoUpload}</strong>
                        <span className="pub-vfield__hint">
                            {t.fab.videoNote}
                        </span>
                    </label>

                    {canRecord && (
                        <button
                            type="button"
                            className="pub-vfield__tile pub-vfield__tile--record"
                            onClick={() => setRecording(true)}
                        >
                            <CameraIcon />
                            <strong>{t.fab.recordOpen}</strong>
                            <span className="pub-vfield__hint">
                                {t.fab.recordHint}
                            </span>
                        </button>
                    )}
                </div>
            )}

            {message && <small role="alert">{message}</small>}

            {/* L'enregistrement a sa propre fenêtre, par-dessus le formulaire. */}
            <PubDialog
                open={recording}
                onOpenChange={setRecording}
                title={t.fab.recordTitle}
                description={t.fab.recordHint}
                className="pub-modal--recorder"
            >
                {recording && (
                    <VideoRecorder
                        onUse={takeRecording}
                        onClose={() => setRecording(false)}
                    />
                )}
            </PubDialog>
        </div>
    );
}
