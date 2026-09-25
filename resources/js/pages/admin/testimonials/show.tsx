import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import ShowPage, { Bilingual, formatDate } from '@/components/admin/show-page';
import { edit, index as testimonialsIndex } from '@/routes/admin/testimonials';
import type { Testimonial } from '@/types';

const STATUS_VARIANT = {
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive',
} as const;

const STATUS_LABEL = {
    pending: 'En attente',
    approved: 'Approuvé',
    rejected: 'Refusé',
} as const;

export default function TestimonialShow({
    testimonial: row,
}: {
    testimonial: Testimonial;
}) {
    return (
        <ShowPage
            title={`Avis de ${row.author_name}`}
            description={row.author_email}
            badge={
                <Badge variant={STATUS_VARIANT[row.status]}>
                    {STATUS_LABEL[row.status]}
                </Badge>
            }
            backHref={testimonialsIndex()}
            backLabel="Avis"
            editHref={edit(row.id)}
            sections={[
                {
                    title: 'Auteur',
                    items: [
                        { label: 'Nom', value: row.author_name },
                        { label: 'Email', value: row.author_email },
                        { label: 'Rôle', value: row.author_role },
                        {
                            label: 'Projet',
                            value: row.project ? (
                                <Link
                                    href={ProjectController.show(
                                        row.project.id,
                                    )}
                                    className="hover:underline"
                                >
                                    {row.project.title.fr}
                                </Link>
                            ) : (
                                'Général'
                            ),
                        },
                        {
                            label: 'Reçu le',
                            value: formatDate(row.submitted_at),
                        },
                        {
                            label: 'À la une',
                            value: row.is_featured ? 'Oui' : 'Non',
                        },
                    ],
                },
                {
                    title: 'Avis',
                    items: [
                        {
                            label: 'Contenu',
                            value: <Bilingual value={row.content} />,
                            wide: true,
                        },
                    ],
                },
            ]}
        />
    );
}

TestimonialShow.layout = {
    breadcrumbs: [
        { title: 'Avis', href: testimonialsIndex() },
        { title: 'Détail', href: '' },
    ],
};
