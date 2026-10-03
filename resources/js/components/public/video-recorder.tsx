import { useEffect, useRef, useState } from 'react';
import { useTranslations } from '@/lib/i18n';

/** Même plafond que le formulaire : 3 minutes. */
const MAX_SECONDS = 180;

/** ~2,5 Mbit/s : 3 minutes tiennent sous 60 Mo, loin des 95 Mo autorisés. */
const VIDEO_BITRATE = 2_500_000;

/** Premier format que le navigateur sait enregistrer (Safari : MP4, les autres : WebM). */
function pickMimeType(): string | undefined {
    return [
        'video/mp4',
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=vp8,opus',
        'video/webm',
    ].find((type) => MediaRecorder.isTypeSupported(type));
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
            <div className="pub-recorder" role="alert">
                <p className="pub-recorder__message">{t.fab.cameraDenied}</p>
                <button
                    type="button"
                    className="pub-contact__again"
                    onClick={close}
                >
                    {t.skills.close}
                </button>
            </div>
        );
    }

    return (
        <div className="pub-recorder">
            {phase === 'review' && recordingUrl ? (
                <video
                    className="pub-recorder__screen"
                    src={recordingUrl}
                    controls
                    playsInline
                />
            ) : (
                <video
                    ref={liveRef}
                    className="pub-recorder__screen pub-recorder__screen--live"
                    autoPlay
                    muted
                    playsInline
                />
            )}

            {phase === 'recording' && (
                <p className="pub-recorder__clock" aria-live="polite">
                    <span className="pub-recorder__dot" aria-hidden="true" />
                    {formatClock(elapsed)} / {formatClock(MAX_SECONDS)}
                </p>
            )}

            <div className="pub-recorder__actions">
                {phase === 'preview' && (
                    <button
                        type="button"
                        className="pub-recorder__main"
                        onClick={start}
                    >
                        {t.fab.recordStart}
                    </button>
                )}
                {phase === 'recording' && (
                    <button
                        type="button"
                        className="pub-recorder__main"
                        onClick={() => recorderRef.current?.stop()}
                    >
                        {t.fab.recordStop}
                    </button>
                )}
                {phase === 'review' && recording && (
                    <>
                        <button
                            type="button"
                            className="pub-recorder__main"
                            onClick={() => onUse(recording)}
                        >
                            {t.fab.recordUse}
                        </button>
                        <button
                            type="button"
                            className="pub-contact__again"
                            onClick={retake}
                        >
                            {t.fab.recordRetake}
                        </button>
                    </>
                )}
                {phase !== 'recording' && (
                    <button
                        type="button"
                        className="pub-contact__again"
                        onClick={close}
                    >
                        {t.fab.recordCancel}
                    </button>
                )}
            </div>
        </div>
    );
}
