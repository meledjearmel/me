import { Head, Link } from "@inertiajs/react";
import { PUBLICATION_STATUSES } from "@/lib/admin-options";
import StatusMenu, {
    PUBLICATION_VARIANTS,
} from "@/components/admin/status-menu";
import { Plus, Eye, SquarePen } from "lucide-react";
import { index as pageIndex } from "@/routes/admin/skills";
import SkillController from "@/actions/App/Http/Controllers/Admin/SkillController";
import DeleteButton from "@/components/admin/delete-button";
import { FilterSelect, ResourceList } from "@/components/admin/data-list";
import Heading from "@/components/heading";
import AiDraftBadge from "@/components/admin/ai-draft-badge";
import { Button } from "@/components/ui/button";
import type { Domain, ListFilters, Paginated, Skill } from "@/types";
import ActionTooltip from "@/components/admin/action-tooltip";

export default function SkillsIndex({
    skills,
    filters,
    domains,
}: {
    skills: Paginated<Skill>;
    filters: ListFilters;
    domains: Pick<Domain, "id" | "label">[];
}) {
    return (
        <>
            <Head title="Compétences" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="Compétences"
                        description="Regroupées par domaine"
                    />
                    <Button asChild>
                        <Link href={SkillController.create()}>
                            <Plus data-icon="inline-start" /> Nouvelle
                            compétence
                        </Link>
                    </Button>
                </div>

                <ResourceList
                    paginator={skills}
                    filters={filters}
                    searchPlaceholder="Nom de la compétence…"
                    columns={[
                        { header: "Nom (FR)", cell: (row) => row.name.fr },
                        {
                            header: "Domaine",
                            cell: (row) => row.domain?.label.fr,
                        },
                        { header: "Ordre", cell: (row) => row.sort_order },
                        {
                            header: "Statut",
                            fit: true,
                            cell: (row) => (
                                <>
                                    <StatusMenu
                                        type="skill"
                                        id={row.id}
                                        status={row.status}
                                        options={PUBLICATION_STATUSES}
                                        variants={PUBLICATION_VARIANTS}
                                    />
                                    <AiDraftBadge
                                        draftedBy={row.drafted_by}
                                        status={row.status}
                                    />
                                </>
                            ),
                        },
                    ]}
                    filterControls={(state) => (
                        <>
                            <FilterSelect
                                state={state}
                                name="domain_id"
                                value={filters.domain_id}
                                label="Domaine"
                                options={domains.map((domain) => ({
                                    value: String(domain.id),
                                    label: domain.label.fr,
                                }))}
                            />
                            <FilterSelect
                                state={state}
                                name="status"
                                value={filters.status}
                                label="Statut"
                                options={[
                                    { value: "published", label: "Publié" },
                                    { value: "draft", label: "Brouillon" },
                                ]}
                            />
                        </>
                    )}
                    actions={(row) => (
                        <>
                            <ActionTooltip label="Voir">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={SkillController.show(row.id)}
                                        aria-label="Voir"
                                    >
                                        <Eye />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <ActionTooltip label="Modifier">
                                <Button variant="ghost" size="icon" asChild>
                                    <Link
                                        href={SkillController.edit(row.id)}
                                        aria-label="Modifier"
                                    >
                                        <SquarePen />
                                    </Link>
                                </Button>
                            </ActionTooltip>
                            <DeleteButton
                                href={SkillController.destroy.url(row.id)}
                                confirmMessage={`Supprimer la compétence "${row.name.fr}" ?`}
                            />
                        </>
                    )}
                />
            </div>
        </>
    );
}

SkillsIndex.layout = {
    breadcrumbs: [{ title: "Compétences", href: pageIndex() }],
};
