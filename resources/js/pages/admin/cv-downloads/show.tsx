import ShowPage, { formatDate } from '@/components/admin/show-page';
import { Badge } from '@/components/ui/badge';
import { index as downloadsIndex } from '@/routes/admin/cv-downloads';
import { DEVICE_LABEL, originOf, placeOf } from '@/lib/cv-downloads';
import type { CvDownload } from '@/types';

export default function CvDownloadShow({
    download: row,
}: {
    download: CvDownload;
}) {
    return (
        <ShowPage
            title={`Téléchargement du ${formatDate(row.created_at)}`}
            description={placeOf(row)}
            badge={<Badge variant="outline">{originOf(row)}</Badge>}
            backHref={downloadsIndex()}
            backLabel="Téléchargements du CV"
            sections={[
                {
                    title: 'Visiteur',
                    items: [
                        {
                            label: 'Email',
                            value: row.email ? (
                                <a
                                    href={`mailto:${row.email}`}
                                    className="underline"
                                >
                                    {row.email}
                                </a>
                            ) : (
                                'Non renseigné'
                            ),
                        },
                        { label: 'Lieu', value: placeOf(row) },
                        {
                            label: 'Appareil',
                            value: row.device ? DEVICE_LABEL[row.device] : '—',
                        },
                        {
                            label: 'Téléchargé le',
                            value: formatDate(row.created_at),
                        },
                    ],
                },
                {
                    title: 'Provenance',
                    items: [
                        {
                            label: "Site d'origine",
                            value: row.referrer_host ?? 'Accès direct',
                        },
                        {
                            label: 'Campagne (source)',
                            value: row.utm_source ?? '—',
                        },
                        { label: 'Support', value: row.utm_medium ?? '—' },
                        { label: 'Campagne', value: row.utm_campaign ?? '—' },
                    ],
                },
                {
                    title: 'CV servi',
                    items: [
                        {
                            label: 'Profil métier',
                            value: row.job_profile?.label.fr ?? '—',
                        },
                        {
                            label: 'Langue',
                            value: row.locale === 'en' ? 'Anglais' : 'Français',
                        },
                        {
                            label: 'Source',
                            value:
                                row.source === 'uploaded'
                                    ? 'CV importé'
                                    : 'CV généré',
                        },
                    ],
                },
            ]}
        />
    );
}

CvDownloadShow.layout = {
    breadcrumbs: [
        { title: 'Téléchargements du CV', href: downloadsIndex() },
        { title: 'Détail', href: '' },
    ],
};
