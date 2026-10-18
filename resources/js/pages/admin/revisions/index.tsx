import { Head, Link } from '@inertiajs/react';
import { Bot, ChevronRight } from 'lucide-react';
import ContentRevisionController from '@/actions/App/Http/Controllers/Admin/ContentRevisionController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pageIndex } from '@/routes/admin/revisions';
import type { ContentRevisionSummary } from '@/types';

const STATUSES = [
    { value: 'pending', label: 'À relire' },
    { value: 'applied', label: 'Appliquées' },
    { value: 'rejected', label: 'Rejetées' },
] as const;

/**
 * Modifications de contenus publiés (et de la page « Now ») proposées par l'IA via le serveur
 * MCP : rien ne change sur le site avant d'être appliqué.
 */
export default function RevisionsIndex({
    revisions,
    status,
}: {
    revisions: ContentRevisionSummary[];
    status: string;
}) {
    return (
        <>
            <Head title="Révisions proposées" />

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
                <Heading
                    title="Révisions proposées"
                    description="Modifications de contenus en ligne proposées par l'IA. Rien ne change sur le site avant que vous les appliquiez."
                />

                <div className="flex flex-wrap gap-2">
                    {STATUSES.map((option) => (
                        <Button
                            key={option.value}
                            variant={
                                option.value === status ? 'default' : 'outline'
                            }
                            size="sm"
                            asChild
                        >
                            <Link
                                href={pageIndex({
                                    query: { status: option.value },
                                })}
                            >
                                {option.label}
                            </Link>
                        </Button>
                    ))}
                </div>

                {revisions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {status === 'pending'
                            ? 'Aucune révision à relire.'
                            : 'Aucune révision.'}
                    </p>
                ) : (
                    <ul className="divide-y rounded-md border">
                        {revisions.map((revision) => (
                            <li key={revision.id}>
                                <Link
                                    href={ContentRevisionController.show(
                                        revision.id,
                                    )}
                                    className="flex items-center gap-3 p-3 hover:bg-muted/50"
                                >
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="secondary">
                                                {revision.content_type_label}
                                            </Badge>
                                            <span className="font-medium">
                                                {revision.title}
                                            </span>
                                            {revision.drafted_by && (
                                                <Badge variant="outline">
                                                    <Bot />{' '}
                                                    {revision.drafted_by}
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {revision.summary}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Proposée le{' '}
                                            {revision.created_at
                                                ? new Date(
                                                      revision.created_at,
                                                  ).toLocaleString('fr-FR')
                                                : '—'}
                                        </p>
                                    </div>
                                    <ChevronRight className="size-4 text-muted-foreground" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

RevisionsIndex.layout = {
    breadcrumbs: [{ title: 'Révisions proposées', href: pageIndex() }],
};
