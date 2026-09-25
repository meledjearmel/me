import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, SquarePen } from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { PublicationStatus, Translatable } from '@/types';

export type DetailItem = {
    label: string;
    value: ReactNode;
    /** Occupe toute la largeur (textes longs). */
    wide?: boolean;
};

export type DetailSection = {
    title: string;
    description?: string;
    items?: DetailItem[];
    content?: ReactNode;
};

type ShowPageProps = {
    title: string;
    description?: string;
    badge?: ReactNode;
    backHref: Parameters<typeof Link>[0]['href'];
    backLabel: string;
    editHref?: Parameters<typeof Link>[0]['href'];
    actions?: ReactNode;
    sections: DetailSection[];
};

const isEmpty = (value: ReactNode) =>
    value === null || value === undefined || value === '' || value === false;

/** Page de consultation d'un élément : titre, actions, puis des cartes de détails. */
export default function ShowPage({
    title,
    description,
    badge,
    backHref,
    backLabel,
    editHref,
    actions,
    sections,
}: ShowPageProps) {
    return (
        <>
            <Head title={title} />

            <div className="flex max-w-4xl flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <Heading title={title} description={description} />
                        {badge}
                    </div>
                    <div className="flex items-center gap-2">
                        {actions}
                        <Button variant="outline" asChild>
                            <Link href={backHref}>
                                <ArrowLeft data-icon="inline-start" />
                                {backLabel}
                            </Link>
                        </Button>
                        {editHref && (
                            <Button asChild>
                                <Link href={editHref}>
                                    <SquarePen data-icon="inline-start" />
                                    Modifier
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {sections.map((section) => (
                    <Card key={section.title}>
                        <CardHeader>
                            <CardTitle>{section.title}</CardTitle>
                            {section.description && (
                                <CardDescription>
                                    {section.description}
                                </CardDescription>
                            )}
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            {section.items && (
                                <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                                    {section.items.map((item) => (
                                        <div
                                            key={item.label}
                                            className={
                                                item.wide
                                                    ? 'sm:col-span-2'
                                                    : undefined
                                            }
                                        >
                                            <dt className="text-sm text-muted-foreground">
                                                {item.label}
                                            </dt>
                                            <dd className="mt-0.5 text-sm break-words whitespace-pre-line">
                                                {isEmpty(item.value)
                                                    ? '—'
                                                    : item.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                            {section.content}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </>
    );
}

/** Une valeur traduite, FR puis EN. */
export function Bilingual({
    value,
}: {
    value: Translatable | null | undefined;
}) {
    if (!value || (!value.fr && !value.en)) {
        return null;
    }

    return (
        <div className="flex flex-col gap-1">
            {value.fr && (
                <p>
                    <span className="mr-2 text-xs font-medium text-muted-foreground">
                        FR
                    </span>
                    {value.fr}
                </p>
            )}
            {value.en && (
                <p>
                    <span className="mr-2 text-xs font-medium text-muted-foreground">
                        EN
                    </span>
                    {value.en}
                </p>
            )}
        </div>
    );
}

export function PublicationBadge({ status }: { status: PublicationStatus }) {
    return (
        <Badge variant={status === 'published' ? 'default' : 'secondary'}>
            {status === 'published' ? 'Publié' : 'Brouillon'}
        </Badge>
    );
}

/** Liste de pastilles (technologies, domaines…). */
export function Pills({ items }: { items: ReactNode[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-1.5">
            {items.map((item, index) => (
                <Badge key={index} variant="secondary">
                    {item}
                </Badge>
            ))}
        </div>
    );
}

const dateFormat = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

export const formatDate = (value: string | null | undefined): string | null =>
    value ? dateFormat.format(new Date(value)) : null;

export const formatPeriod = (
    start: string,
    end: string | null | undefined,
): string => `${formatDate(start)} — ${end ? formatDate(end) : 'en cours'}`;
