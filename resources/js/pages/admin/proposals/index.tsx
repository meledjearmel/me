import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import ProposalController from '@/actions/App/Http/Controllers/Admin/ProposalController';
import ActionTooltip from '@/components/admin/action-tooltip';
import { FilterSelect, ResourceList } from '@/components/admin/data-list';
import DeleteButton from '@/components/admin/delete-button';
import {
    PROPOSAL_PLATFORMS,
    PROPOSAL_STATUSES,
} from '@/components/admin/proposal-fields';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/proposals';
import type { ListFilters, Paginated, Proposal } from '@/types';

const label = (
    options: readonly { value: string; label: string }[],
    value: string,
) => options.find((option) => option.value === value)?.label ?? value;

export default function ProposalsIndex({
    proposals,
    filters,
}: {
    proposals: Paginated<Proposal>;
    filters: ListFilters;
}) {
    return (
        <>
            <Head title="Propositions freelance" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Propositions freelance"
                        description="Privées : historique et exemples pour l’IA qui rédige les suivantes"
                    />
                    <Button asChild>
                        <Link href={ProposalController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                            proposition
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={proposals}
                    filters={filters}
                    searchPlaceholder="Offre, client, texte…"
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="platform"
                                value={filters.platform}
                                label="Plateforme"
                                options={[...PROPOSAL_PLATFORMS]}
                            />
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[...PROPOSAL_STATUSES]}
                            />
                        </>
                    )}
                    columns={[
                        {
                            header: 'Offre',
                            className: 'max-w-md whitespace-normal',
                            cell: (row) => row.job_title,
                        },
                        {
                            header: 'Plateforme',
                            cell: (row) =>
                                `${label(PROPOSAL_PLATFORMS, row.platform)} · ${row.locale.toUpperCase()}`,
                        },
                        {
                            header: 'Statut',
                            fit: true,
                            cell: (row) => (
                                <Badge
                                    variant={
                                        row.status === 'hired' ||
                                        row.status === 'interview'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {label(PROPOSAL_STATUSES, row.status)}
                                </Badge>
                            ),
                        },
                        {
                            header: 'Envoyée le',
                            cell: (row) =>
                                row.sent_at
                                    ? new Date(row.sent_at).toLocaleDateString(
                                          'fr-FR',
                                      )
                                    : '—',
                        },
                    ]}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={ProposalController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={ProposalController.destroy.url(row.id)}
                                confirmMessage="Supprimer cette proposition ?"
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

ProposalsIndex.layout = {
    breadcrumbs: [{ title: 'Propositions freelance', href: pageIndex() }],
};
