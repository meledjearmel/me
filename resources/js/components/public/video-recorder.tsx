import { useEffect, useRef, useState } from 'react';
import { useTranslations } from '@/lib/i18n';

/** Même plafond que le formulaire : 3 minutes. */
const MAX_SECONDS = 180;

/** ~2,5 Mbit/s : 3 minutes tiennent sous 60 Mo, loin des 95 Mo autorisés. */
const VIDEO_BITRATE = 2_500_000;

/**
 * Premier format que le navigateur sait enregistrer. WebM d'abord : le MP4
 * fragmenté de Chrome ne se relit pas depuis un blob (la relecture reste
 * bloquée en chargement). Safari, qui n'enregistre qu'en MP4, le relit bien.
 */
function pickMimeType(): string | undefined {
    return [
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=vp8,opus',
        'video/webm',
        'video/mp4',
    ].find((type) => MediaRecorder.isTypeSupported(type));
}

/**
 * Le WebM enregistré par Chrome n'inscrit pas sa durée (Infinity) : la barre de
 * lecture est alors inutilisable. Sauter très loin force le navigateur à la
 * calculer, puis on revient au début.
 */
export function resolveInfiniteDuration(video: HTMLVideoElement): void {
    if (video.duration !== Infinity) {
        return;
    }

    const rewind = () => {
        video.removeEventListener('timeupdate', rewind);
        video.currentTime = 0;
    };

    video.addEventListener('timeupdate', rewind);
    video.currentTime = Number.MAX_SAFE_INTEGER;
}

/** L'enregistrement n'est proposé que si le navigateur sait filmer. */
export function canRecordVideo(): boolean {
    return (
        typeof window !== 'undefined' &&
        typeof window.MediaRecorder !== 'undefined' &&
        !!navigator.mediaDevices?.getUserMedia
    );
}

function formatClock(seconds: number): string {
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

type Phase = 'preview' | 'recording' | 'review' | 'error';

/**
 * Filmer son avis depuis la page (webcam ou caméra frontale) : aperçu en
 * direct, arrêt automatique à 3 minutes, relecture, puis « Utiliser » remet
 * la vidéo au formulaire comme un fichier choisi.
 */
export default function VideoRecorder({
    onUse,
    onClose,
}: {
    onUse: (file: File) => void;
    onClose: () => void;
}) {
    const t = useTranslations();
    const liveRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const recorderRef = useRef<MediaRecorder | null>(null);
    const [phase, setPhase] = useState<Phase>('preview');
    const [elapsed, setElapsed] = useState(0);
    const [recording, setRecording] = useState<File | null>(null);
    const [recordingUrl, setRecordingUrl] = useState<string | null>(null);

    const stopStream = () => {
        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
    };

    const startCamera = async () => {
        setPhase('preview');

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', height: { ideal: 720 } },
                audio: true,
            });
            streamRef.current = stream;

            if (liveRef.current) {
                liveRef.current.srcObject = stream;
            }
        } catch {
            setPhase('error');
        }
    };

    useEffect(() => {
        void startCamera();

        return () => {
            if (recorderRef.current?.state === 'recording') {
                recorderRef.current.stop();
            }

            stopStream();
        };
    }, []);

    useEffect(
        () => () => {
            if (recordingUrl) {
                URL.revokeObjectURL(recordingUrl);
            }
        },
        [recordingUrl],
    );

    // Chronomètre pendant l'enregistrement.
    useEffect(() => {
        if (phase !== 'recording') {
            return;
        }

        const timer = window.setInterval(
            () => setElapsed((value) => value + 1),
            1000,
        );

        return () => window.clearInterval(timer);
    }, [phase]);

    // Arrêt automatique au plafond.
    useEffect(() => {
        if (phase === 'recording' && elapsed >= MAX_SECONDS) {
            recorderRef.current?.stop();
        }
    }, [phase, elapsed]);

    const start = () => {
        const stream = streamRef.current;

        if (!stream) {
            return;
        }

        const mimeType = pickMimeType();
        const recorder = new MediaRecorder(stream, {
            mimeType,
            videoBitsPerSecond: VIDEO_BITRATE,
        });
        const chunks: Blob[] = [];

        recorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                chunks.push(event.data);
            }
        };
        recorder.onstop = () => {
            const type = recorder.mimeType || mimeType || 'video/webm';
            const extension = type.startsWith('video/mp4') ? 'mp4' : 'webm';
            const file = new File(chunks, `avis.${extension}`, {
                type: type.split(';')[0],
            });

            stopStream();
            setRecording(file);
            setRecordingUrl(URL.createObjectURL(file));
            setPhase('review');
        };

        recorderRef.current = recorder;
        setElapsed(0);
        recorder.start(1000);
        setPhase('recording');
    };

    const retake = () => {
        setRecording(null);
        setRecordingUrl(null);
        void startCamera();
    };

    const close = () => {
        stopStream();
        onClose();
    };

    if (phase === 'error') {
        return (
            <div
                className="pub-vrec pub-vrec--error"
                role="alert"
                data-cursor="native"
            >
                <p>{t.fab.cameraDenied}</p>
                <button
                    type="button"
                    className="pub-vrec__ghost"
                    onClick={close}
                >
                    {t.skills.close}
                </button>
            </div>
        );
    }

    const isReview = phase === 'review' && recordingUrl;

    return (
        <div className="pub-vrec" data-phase={phase} data-cursor="native">
            <div className="pub-vrec__stage">
                {/* Deux éléments distincts (key) : sinon React réutilise celui de
                    l'aperçu, dont le flux caméra (srcObject) masque la relecture. */}
                {isReview ? (
                    <video
                        key="review"
                        className="pub-vrec__screen"
                        src={recordingUrl}
                        controls
                        playsInline
                        onLoadedMetadata={(event) =>
                            resolveInfiniteDuration(event.currentTarget)
                        }
                    />
                ) : (
                    <video
                        key="live"
                        ref={liveRef}
                        className="pub-vrec__screen pub-vrec__screen--live"
                        autoPlay
                        muted
                        playsInline
                    />
                )}

                {!isReview && (
                    <>
                        <p className="pub-vrec__clock" aria-live="polite">
                            {phase === 'recording' && (
                                <span
                                    className="pub-vrec__dot"
                                    aria-hidden="true"
                                />
                            )}
                            {formatClock(elapsed)} / {formatClock(MAX_SECONDS)}
                        </p>

                        <button
                            type="button"
                            className="pub-vrec__shutter"
                            aria-label={
                                phase === 'recording'
                                    ? t.fab.recordStop
                                    : t.fab.recordStart
                            }
                            onClick={
                                phase === 'recording'
                                    ? () => recorderRef.current?.stop()
                                    : start
                            }
                        >
                            <span aria-hidden="true" />
                        </button>

                        <span
                            className="pub-vrec__progress"
                            style={{
                                transform: `scaleX(${elapsed / MAX_SECONDS})`,
                            }}
                            aria-hidden="true"
                        />
                    </>
                )}
            </div>

            <div className="pub-vrec__actions">
                {isReview && recording ? (
                    <>
                        <button
                            type="button"
                            className="pub-vrec__primary"
                            onClick={() => onUse(recording)}
                        >
                            {t.fab.recordUse}
                        </button>
                        <button
                            type="button"
                            className="pub-vrec__ghost"
                            onClick={retake}
                        >
                            {t.fab.recordRetake}
                        </button>
                    </>
                ) : (
                    <p className="pub-vrec__hint">
                        {phase === 'recording'
                            ? t.fab.recordStop
                            : t.fab.recordStart}
                    </p>
                )}
                {phase !== 'recording' && (
                    <button
                        type="button"
                        className="pub-vrec__ghost"
                        onClick={close}
                    >
                        {t.fab.recordCancel}
                    </button>
                )}
            </div>
        </div>
    );
}
