import { Link } from '@inertiajs/react';
import ProfessionalReferenceController from '@/actions/App/Http/Controllers/Admin/ProfessionalReferenceController';
import { Badge } from '@/components/ui/badge';
import { REFERENCE_VISIBLE_FIELDS } from '@/lib/admin-options';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import ShowPage, { Pills } from '@/components/admin/show-page';
import { index as referencesIndex } from '@/routes/admin/professional-references';
import type { ProfessionalReference } from '@/types';

export default function ProfessionalReferenceShow({
    professionalReference: row,
}: {
    professionalReference: ProfessionalReference;
}) {
    return (
        <ShowPage
            title={row.name}
            description={
                [row.role, row.company].filter(Boolean).join(' · ') ||
                'Référence professionnelle'
            }
            badge={
                <Badge variant={row.is_public ? 'default' : 'secondary'}>
                    {row.is_public ? 'Sur le CV' : 'Privée'}
                </Badge>
            }
            backHref={referencesIndex()}
            backLabel="Références"
            editHref={ProfessionalReferenceController.edit(row.id)}
            sections={[
                {
                    title: 'Coordonnées',
                    items: [
                        { label: 'Nom', value: row.name },
                        { label: 'Rôle', value: row.role },
                        { label: 'Société', value: row.company },
                        { label: 'Relation', value: row.relationship },
                        { label: 'Email', value: row.email },
                        { label: 'Téléphone', value: row.phone },
                        {
                            label: 'Projet lié',
                            value: row.project && (
                                <Link
                                    href={ProjectController.show(
                                        row.project.id,
                                    )}
                                    className="hover:underline"
                                >
                                    {row.project.title.fr}
                                </Link>
                            ),
                        },
                    ],
                },
                {
                    title: 'Sur le CV',
                    items: [
                        {
                            label: 'Incluse dans le CV',
                            value: row.is_public ? 'Oui' : 'Non',
                        },
                        {
                            label: 'Champs affichés',
                            value: (
                                <Pills
                                    items={REFERENCE_VISIBLE_FIELDS.filter(
                                        (field) =>
                                            row.visible_fields?.includes(
                                                field.value,
                                            ),
                                    ).map((field) => field.label)}
                                />
                            ),
                        },
                        {
                            label: 'Notes privées',
                            value: row.notes,
                            wide: true,
                        },
                    ],
                },
            ]}
        />
    );
}

ProfessionalReferenceShow.layout = {
    breadcrumbs: [
        { title: 'Références', href: referencesIndex() },
        { title: 'Détail', href: '' },
    ],
};
