import { openReview } from '@/lib/review';
import type { ReviewContext } from '@/lib/review';

/**
 * Invitation discrète à laisser un avis (« Vous avez travaillé avec moi ? Laissez
 * un avis → »), avec le projet ou l'expérience prérempli selon l'endroit.
 */
export default function ReviewInvite({
    text,
    cta,
    context,
    className = '',
}: {
    text: string;
    cta: string;
    context?: ReviewContext;
    className?: string;
}) {
    return (
        <p className={`pub-review-invite ${className}`.trim()}>
            <span>{text}</span>
            <button type="button" onClick={() => openReview(context)}>
                {cta} <span aria-hidden="true">→</span>
            </button>
        </p>
    );
}
