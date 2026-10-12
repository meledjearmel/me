import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, SquarePen } from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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

/** Une section de détails : libellés à gauche, valeurs à droite, lignes séparées. */
function Section({
    section,
    hideTitle = false,
}: {
    section: DetailSection;
    hideTitle?: boolean;
}) {
    return (
        <section className="space-y-3">
            {(!hideTitle || section.description) && (
                <div>
                    {!hideTitle && (
                        <h2 className="text-sm font-medium">{section.title}</h2>
                    )}
                    {section.description && (
                        <p className="text-sm text-muted-foreground">
                            {section.description}
                        </p>
                    )}
                </div>
            )}
            {section.items && (
                <dl className="divide-y rounded-lg border">
                    {section.items.map((item) => (
                        <div
                            key={item.label}
                            className={
                                item.wide
                                    ? 'space-y-1 px-4 py-3'
                                    : 'grid gap-1 px-4 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4'
                            }
                        >
                            <dt className="text-sm text-muted-foreground">
                                {item.label}
                            </dt>
                            <dd className="text-sm break-words whitespace-pre-line">
                                {isEmpty(item.value) ? '—' : item.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
            {section.content}
        </section>
    );
}

/** Page de consultation d'un élément : titre, actions, puis ses détails (en onglets dès trois sections). */
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

            <div className="mx-auto w-full max-w-5xl space-y-6 p-4">
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

                {sections.length >= 3 ? (
                    <Tabs
                        defaultValue={sections[0].title}
                        className="space-y-4"
                    >
                        <TabsList className="h-auto flex-wrap">
                            {sections.map((section) => (
                                <TabsTrigger
                                    key={section.title}
                                    value={section.title}
                                >
                                    {section.title}
                                </TabsTrigger>
                            ))}
                        </TabsList>
                        {sections.map((section) => (
                            <TabsContent
                                key={section.title}
                                value={section.title}
                            >
                                <Section section={section} hideTitle />
                            </TabsContent>
                        ))}
                    </Tabs>
                ) : (
                    sections.map((section) => (
                        <Section key={section.title} section={section} />
                    ))
                )}
            </div>
        </>
    );
}

/** Une valeur traduite, FR puis EN. */
export function Bilingual({
    value,
}: {
    value: Partial<Translatable> | null | undefined;
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
