import { useLayoutEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import PubDialog from '@/components/public/pub-dialog';
import { useTranslations } from '@/lib/i18n';
import type { PublicTestimonial } from '@/types';

export function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

/** 42 → « 0:42 ». */
export function formatDuration(seconds: number): string {
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

/** Vrai quand le texte dépasse le nombre de lignes affichées (mesuré, pas estimé). */
function useIsClamped<T extends HTMLElement>(dependency: unknown) {
    const ref = useRef<T>(null);
    const [clamped, setClamped] = useState(false);

    useLayoutEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        const measure = () =>
            setClamped(element.scrollHeight > element.clientHeight + 1);
        const observer = new ResizeObserver(measure);

        measure();
        observer.observe(element);

        return () => observer.disconnect();
    }, [dependency]);

    return [ref, clamped] as const;
}

function PlayIcon() {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
                fill="currentColor"
                d="M8 5.5v13a1 1 0 0 0 1.5.86l10.6-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5Z"
            />
        </svg>
    );
}

/**
 * Le cœur d'une carte d'avis : l'aperçu de la vidéo s'il y en a une, puis
 * l'accroche (en grand) ou le texte coupé à `lines` lignes. Au-delà, un lien
 * ouvre l'avis complet ; avec `expandInline`, le texte se déplie sur place.
 */
export function TestimonialExcerpt({
    testimonial,
    lines,
    quoteClassName,
    expandInline = false,
    onOpen,
}: {
    testimonial: PublicTestimonial;
    lines: number;
    quoteClassName: string;
    expandInline?: boolean;
    onOpen: () => void;
}) {
    const t = useTranslations();
    const [expanded, setExpanded] = useState(false);
    const [quoteRef, clamped] = useIsClamped<HTMLQuoteElement>(testimonial.id);
    const { video, highlight } = testimonial;
    const hasMore = highlight !== null || clamped;

    return (
        <>
            {video && (
                <button
                    type="button"
                    className="pub-testi-video"
                    onClick={onOpen}
                    aria-label={`${t.testimonials.watchVideo} — ${testimonial.author_name}`}
                >
                    {video.poster_url ? (
                        <img src={video.poster_url} alt="" loading="lazy" />
                    ) : (
                        <video
                            src={`${video.url}#t=1`}
                            preload="metadata"
                            muted
                            playsInline
                            tabIndex={-1}
                        />
                    )}
                    <span className="pub-testi-video__play">
                        <PlayIcon />
                    </span>
                    <span className="pub-testi-video__badge">
                        {t.testimonials.videoBadge}
                        {video.duration !== null &&
                            ` · ${formatDuration(video.duration)}`}
                    </span>
                </button>
            )}

            <blockquote
                ref={quoteRef}
                className={`${quoteClassName} pub-testi-quote${highlight ? ' pub-testi-quote--highlight' : ''}`}
                data-clamped={clamped && !expanded}
                style={
                    expanded
                        ? undefined
                        : ({ '--lines': lines } as CSSProperties)
                }
            >
                {highlight ?? testimonial.content}
            </blockquote>

            {expandInline && !highlight && (clamped || expanded) ? (
                <button
                    type="button"
                    className="pub-testi-more"
                    aria-expanded={expanded}
                    onClick={() => setExpanded((value) => !value)}
                >
                    {expanded
                        ? t.testimonials.readLess
                        : t.testimonials.readMore}
                </button>
            ) : (
                hasMore && (
                    <button
                        type="button"
                        className="pub-testi-more"
                        onClick={onOpen}
                    >
                        {t.testimonials.readFull}{' '}
                        <span aria-hidden="true">→</span>
                    </button>
                )
            )}
        </>
    );
}

/**
 * L'avis complet en fenêtre modale : la vidéo (qui démarre seule), le texte
 * entier avec ses paragraphes, puis la transcription repliée.
 */
export function TestimonialReader({
    testimonial,
    open,
    onOpenChange,
}: {
    testimonial: PublicTestimonial | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const t = useTranslations();

    if (!testimonial) {
        return null;
    }

    const { video } = testimonial;

    return (
        <PubDialog
            open={open}
            onOpenChange={onOpenChange}
            title={testimonial.author_name}
            description={testimonial.author_role ?? undefined}
            className="pub-modal--review"
        >
            {video && (
                <video
                    className="pub-review-reader__video"
                    src={video.url}
                    poster={video.poster_url ?? undefined}
                    style={
                        video.width && video.height
                            ? {
                                  aspectRatio: `${video.width} / ${video.height}`,
                              }
                            : undefined
                    }
                    controls
                    autoPlay
                    playsInline
                    preload="metadata"
                />
            )}

            <blockquote className="pub-review-reader__text">
                {testimonial.content}
            </blockquote>

            {testimonial.video_transcript && (
                <details className="pub-review-reader__transcript">
                    <summary>{t.testimonials.transcript}</summary>
                    <p>{testimonial.video_transcript}</p>
                </details>
            )}
        </PubDialog>
    );
}

/** Un seul lecteur par liste : l'avis ouvert reste affiché pendant la fermeture animée. */
export function useTestimonialReader() {
    const [testimonial, setTestimonial] = useState<PublicTestimonial | null>(
        null,
    );
    const [open, setOpen] = useState(false);

    return {
        open: (next: PublicTestimonial) => {
            setTestimonial(next);
            setOpen(true);
        },
        reader: (
            <TestimonialReader
                testimonial={testimonial}
                open={open}
                onOpenChange={setOpen}
            />
        ),
    };
}

/** 102 → « PT1M42S » (durée ISO 8601 attendue par schema.org). */
function isoDuration(seconds: number): string {
    return `PT${Math.floor(seconds / 60)}M${seconds % 60}S`;
}

/**
 * Données structurées VideoObject des avis vidéo, pour les résultats vidéo de
 * Google. Seules les vidéos traitées (avec aperçu) sont éligibles : Google exige
 * une miniature.
 */
export function testimonialVideosJsonLd(
    testimonials: PublicTestimonial[],
    siteUrl: string,
    label: string,
): Record<string, unknown>[] {
    const absolute = (value: string) =>
        /^https?:\/\//.test(value)
            ? value
            : `${siteUrl.replace(/\/$/, '')}${value.startsWith('/') ? '' : '/'}${value}`;

    return testimonials.flatMap(({ video, ...testimonial }) => {
        if (!video?.poster_url || !video.uploaded_at) {
            return [];
        }

        return [
            {
                '@type': 'VideoObject',
                name: `${label} — ${testimonial.author_name}`,
                description: (
                    testimonial.highlight ?? testimonial.content
                ).slice(0, 300),
                thumbnailUrl: absolute(video.poster_url),
                contentUrl: absolute(video.url),
                uploadDate: video.uploaded_at,
                duration:
                    video.duration !== null
                        ? isoDuration(video.duration)
                        : undefined,
                transcript: testimonial.video_transcript ?? undefined,
                creator: {
                    '@type': 'Person',
                    name: testimonial.author_name,
                },
            },
        ];
    });
}
