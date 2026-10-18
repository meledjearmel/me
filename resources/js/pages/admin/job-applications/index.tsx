import { Head, Link } from '@inertiajs/react';
import StatusMenu from '@/components/admin/status-menu';
import { Eye, Plus, SquarePen } from 'lucide-react';
import JobApplicationController from '@/actions/App/Http/Controllers/Admin/JobApplicationController';
import ActionTooltip from '@/components/admin/action-tooltip';
import AiDraftBadge from '@/components/admin/ai-draft-badge';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import { JOB_APPLICATION_STATUSES } from '@/components/admin/job-application-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/job-applications';
import type { JobApplication, ListFilters, Paginated } from '@/types';

const STATUS_LABELS: Record<JobApplication['status'], string> = {
    draft: 'Brouillon',
    active: 'En ligne',
    archived: 'Archivée',
};

const lastSeen = (value: string | null) =>
    value
        ? new Date(value).toLocaleDateString('fr-FR', {
              day: 'numeric',
              month: 'short',
          })
        : 'Jamais';

export default function JobApplicationsIndex({
    applications,
    filters,
}: {
    applications: Paginated<JobApplication>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Candidatures" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Candidatures"
                        description="Une page privée par entreprise : mot d'accompagnement, projets choisis et CV, avec le suivi des visites"
                    />
                    <Button asChild>
                        <Link href={JobApplicationController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                            candidature
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={applications}
                    filters={filters}
                    searchPlaceholder="Entreprise, poste ou recruteur…"
                    columns={[
                        { header: 'Entreprise', cell: (row) => row.company },
                        { header: 'Poste', cell: (row) => row.position },
                        { header: 'Vues', cell: (row) => row.views_count },
                        { header: 'CV', cell: (row) => row.cv_downloads_count },
                        {
                            header: 'Dernière visite',
                            cell: (row) => lastSeen(row.last_viewed_at),
                        },
                        {
                            header: 'Statut',
                            fit: true,
                            cell: (row) => (
                                <>
                                    <StatusMenu
                                        type="job_application"
                                        id={row.id}
                                        status={row.status}
                                        options={Object.entries(
                                            STATUS_LABELS,
                                        ).map(([value, label]) => ({
                                            value,
                                            label,
                                        }))}
                                        variants={{
                                            active: 'default',
                                            draft: 'secondary',
                                            archived: 'secondary',
                                        }}
                                    />
                                    <AiDraftBadge
                                        draftedBy={row.drafted_by}
                                        status={
                                            row.status === 'draft'
                                                ? 'draft'
                                                : 'published'
                                        }
                                    />
                                </>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <FilterSelect
                            state={state}
                            name="status"
                            value={filters.status}
                            label="Statut"
                            options={JOB_APPLICATION_STATUSES.map((status) => ({
                                value: status.value,
                                label: STATUS_LABELS[status.value],
                            }))}
                        />
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={JobApplicationController.show(
                                            row.id,
                                        )}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={JobApplicationController.edit(
                                            row.id,
                                        )}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={JobApplicationController.destroy.url(
                                    row.id,
                                )}
                                confirmMessage={`Supprimer la candidature « ${row.company} » ? Son lien ne répondra plus.`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

JobApplicationsIndex.layout = {
    breadcrumbs: [{ title: 'Candidatures', href: pageIndex() }],
};
