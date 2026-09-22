import { Head, Link } from '@inertiajs/react';
import { Plus, SquarePen } from 'lucide-react';
import DomainController from '@/actions/App/Http/Controllers/Admin/DomainController';
import DeleteButton from '@/components/admin/delete-button';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as domainsIndex } from '@/routes/admin/domains';
import type { Domain } from '@/types';

export default function DomainsIndex({ domains }: { domains: Domain[] }) {
    return (
        <>
            <Head title="Domaines" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Domaines"
                        description="Dev, Infra, Sécurité, Design..."
                    />
                    <Button asChild>
                        <Link href={DomainController.create()}>
                            <Plus /> Nouveau domaine
                        </Link>
                    </Button>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Clé</TableHead>
                            <TableHead>Libellé (FR)</TableHead>
                            <TableHead>Couleur</TableHead>
                            <TableHead>Ordre</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {domains.map((domain) => (
                            <TableRow key={domain.id}>
                                <TableCell className="font-mono text-xs">
                                    {domain.key}
                                </TableCell>
                                <TableCell>{domain.label.fr}</TableCell>
                                <TableCell>
                                    <span className="inline-flex items-center gap-2">
                                        <span
                                            className="size-3 rounded-full border"
                                            style={{
                                                backgroundColor: domain.color,
                                            }}
                                        />
                                        {domain.color}
                                    </span>
                                </TableCell>
                                <TableCell>{domain.sort_order}</TableCell>
                                <TableCell className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild>
                                        <Link
                                            href={DomainController.edit(
                                                domain.id,
                                            )}
                                        >
                                            <SquarePen />
                                        </Link>
                                    </Button>
                                    <DeleteButton
                                        href={DomainController.destroy.url(
                                            domain.id,
                                        )}
                                        confirmMessage={`Supprimer le domaine "${domain.label.fr}" ?`}
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

DomainsIndex.layout = {
    breadcrumbs: [{ title: 'Domaines', href: domainsIndex() }],
};
