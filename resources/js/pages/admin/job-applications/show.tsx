import { Check, Copy, ExternalLink } from 'lucide-react';
import ShowPage, { formatDate } from '@/components/admin/show-page';
import AiDraftBadge from '@/components/admin/ai-draft-badge';
import JobApplicationCv from '@/components/admin/job-application-cv';
import { JOB_APPLICATION_STATUSES } from '@/components/admin/job-application-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import {
    edit,
    index as applicationsIndex,
} from '@/routes/admin/job-applications';
import type { JobApplication, JobApplicationEvent } from '@/types';

type ShownApplication = JobApplication & {
    url: string;
    is_open: boolean;
    job_profile: string | null;
    projects: { id: number; title: string; published: boolean }[];
};

const dateTime = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('fr-FR', {
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
          })
        : null;

/** Fiche d'une page de candidature : son lien, son contenu et qui l'a consultée. */
export default function JobApplicationShow({
    application,
    events,
}: {
    application: ShownApplication;
    events: JobApplicationEvent[];
}) {
    const [copied, copy] = useClipboard();

    return (
        <ShowPage
            title={application.company}
            description={application.position}
            badge={
                <>
                    <Badge
                        variant={application.is_open ? 'default' : 'secondary'}
                    >
                        {application.is_open
                            ? 'En ligne'
                            : (JOB_APPLICATION_STATUSES.find(
                                  (status) =>
                                      status.value === application.status,
                              )?.label ?? application.status)}
                    </Badge>
                    <AiDraftBadge
                        draftedBy={application.drafted_by}
                        status={
                            application.status === 'draft'
                                ? 'draft'
                                : 'published'
                        }
                    />
                </>
            }
            backHref={applicationsIndex()}
            backLabel="Candidatures"
            editHref={edit(application.id)}
            actions={
                <>
                    <Button
                        variant="outline"
                        onClick={() => void copy(application.url)}
                    >
                        {copied === application.url ? <Check /> : <Copy />}
                        {copied === application.url
                            ? 'Lien copié'
                            : 'Copier le lien'}
                    </Button>
                    {application.is_open && (
                        <Button variant="outline" asChild>
                            <a
                                href={application.url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink /> Ouvrir
                            </a>
                        </Button>
                    )}
                </>
            }
            sections={[
                {
                    title: 'Suivi',
                    description:
                        'Sans les robots ni vos propres visites quand vous êtes connecté.',
                    items: [
                        {
                            label: 'Consultations',
                            value: String(application.views_count),
                        },
                        {
                            label: 'CV téléchargés',
                            value: String(application.cv_downloads_count),
                        },
                        {
                            label: 'Première visite',
                            value:
                                dateTime(application.first_viewed_at) ??
                                'Pas encore',
                        },
                        {
                            label: 'Dernière visite',
                            value: dateTime(application.last_viewed_at),
                        },
                        { label: 'Lien', value: application.url, wide: true },
                        {
                            label: 'Valable jusqu’au',
                            value: formatDate(application.expires_on),
                        },
                    ],
                },
                {
                    title: 'Page',
                    items: [
                        { label: 'Recruteur', value: application.contact_name },
                        {
                            label: 'Langue',
                            value:
                                application.locale === 'fr'
                                    ? 'Français'
                                    : 'Anglais',
                        },
                        {
                            label: 'CV proposé',
                            value: application.job_profile ?? 'Aucun',
                        },
                        {
                            label: 'Projets',
                            value: application.projects.length
                                ? application.projects
                                      .map(
                                          (project) =>
                                              project.title +
                                              (project.published
                                                  ? ''
                                                  : ' (non publié, masqué)'),
                                      )
                                      .join(' · ')
                                : null,
                            wide: true,
                        },
                        {
                            label: 'Mot d’accompagnement',
                            value: application.intro,
                            wide: true,
                        },
                        {
                            label: 'Offre',
                            value: application.job_posting_url,
                            wide: true,
                        },
                        {
                            label: 'Notes privées',
                            value: application.notes,
                            wide: true,
                        },
                    ],
                },
                {
                    title: 'CV',
                    description:
                        'Le CV que l’entreprise télécharge depuis la page.',
                    content: (
                        <JobApplicationCv
                            applicationId={application.id}
                            tailoring={application.cv_tailoring}
                            tailoredAt={application.cv_tailored_at}
                            hasProfile={application.job_profile !== null}
                            hasPosting={!!application.job_posting_text}
                        />
                    ),
                },
                {
                    title: 'Historique',
                    description: '100 derniers événements.',
                    content: events.length ? (
                        <ul className="divide-y text-sm">
                            {events.map((event) => (
                                <li
                                    key={event.id}
                                    className="flex flex-wrap gap-x-3 py-2"
                                >
                                    <span className="w-36 text-muted-foreground">
                                        {dateTime(event.created_at)}
                                    </span>
                                    <span className="font-medium">
                                        {event.type === 'view'
                                            ? 'Consultation'
                                            : 'CV téléchargé'}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {[
                                            event.city,
                                            event.country,
                                            event.device,
                                            event.referrer_host,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Personne n'a encore ouvert la page.
                        </p>
                    ),
                },
            ]}
        />
    );
}

JobApplicationShow.layout = {
    breadcrumbs: [
        { title: 'Candidatures', href: applicationsIndex() },
        { title: 'Détail', href: '' },
    ],
};
