import { Head, Link } from '@inertiajs/react';
import { SquarePen } from 'lucide-react';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    destroy,
    edit,
    index as testimonialsIndex,
} from '@/routes/admin/testimonials';
import type { Testimonial } from '@/types';

const STATUS_VARIANT = {
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive',
} as const;

export default function TestimonialsIndex({
    testimonials,
}: {
    testimonials: Testimonial[];
}) {
    return (
        <>
            <Head title="Avis" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Avis"
                    description="Modération des avis soumis publiquement"
                />

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Auteur</TableHead>
                            <TableHead>Projet</TableHead>
                            <TableHead>Statut</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {testimonials.map((testimonial) => (
                            <TableRow key={testimonial.id}>
                                <TableCell>{testimonial.author_name}</TableCell>
                                <TableCell>
                                    {testimonial.project?.title.fr ?? 'Général'}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant={
                                            STATUS_VARIANT[testimonial.status]
                                        }
                                    >
                                        {testimonial.status}
                                    </Badge>
                                </TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link href={edit(testimonial.id)}>
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={destroy.url(testimonial.id)}
                                        confirmMessage={`Supprimer l'avis de "${testimonial.author_name}" ?`}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

TestimonialsIndex.layout = {
    breadcrumbs: [{ title: 'Avis', href: testimonialsIndex() }],
};
