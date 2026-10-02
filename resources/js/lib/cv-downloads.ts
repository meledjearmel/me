import type { CvDownload } from '@/types';

export const DEVICE_LABEL = {
    desktop: 'Ordinateur',
    mobile: 'Mobile',
    tablet: 'Tablette',
} as const;

/** « Abidjan, Côte d'Ivoire », ou un tiret si le lieu est inconnu. */
export const placeOf = (row: CvDownload): string =>
    [row.city, row.country].filter(Boolean).join(', ') || '—';

/** La campagne si elle est connue, sinon le site d'origine, sinon un accès direct. */
export const originOf = (row: CvDownload): string =>
    row.utm_source ?? row.referrer_host ?? 'direct';
