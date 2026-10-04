/** Événement qui ouvre la fenêtre « Laisser un avis » depuis n'importe où dans le site. */
export const OPEN_REVIEW_EVENT = 'pub:open-review';

/** Paramètre d'URL qui ouvre la fenêtre d'avis dès l'arrivée (lien à envoyer : ?avis=1). */
export const REVIEW_QUERY_PARAM = 'avis';

/** Le projet, l'expérience ou la formation dont parle l'avis, prérempli selon l'endroit où on clique. */
export type ReviewContext = {
    label: string;
    projectId?: number;
    experienceId?: number;
    educationId?: number;
};

export function openReview(context?: ReviewContext): void {
    window.dispatchEvent(
        new CustomEvent<ReviewContext | undefined>(OPEN_REVIEW_EVENT, {
            detail: context,
        }),
    );
}

/** Lien d'invitation (?invitation=…) : ce que le formulaire préremplit, ou un lien qui n'est plus valable. */
export type ReviewInvitation =
    | { valid: false }
    | {
          valid: true;
          token: string;
          name: string | null;
          email: string | null;
          subject: string | null;
      };
