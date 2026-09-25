import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Eye,
    Handshake,
    Mail,
    MessageSquareQuote,
    PartyPopper,
    Sparkles,
} from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    LabelList,
    XAxis,
    YAxis,
} from 'recharts';
import { Badge } from '@/components/ui/badge';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { Empty, EmptyDescription, EmptyHeader } from '@/components/ui/empty';
import { TECHNOLOGY_CATEGORIES } from '@/lib/admin-options';
import { dashboard } from '@/routes';
import { index as contactsIndex } from '@/routes/admin/contacts';
import { index as engagementsIndex } from '@/routes/admin/engagements';
import { index as referencesIndex } from '@/routes/admin/professional-references';
import { edit as profileEdit } from '@/routes/admin/profile';
import { index as projectsIndex } from '@/routes/admin/projects';
import { index as testimonialsIndex } from '@/routes/admin/testimonials';

type Bar = { label: string; color?: string; count: number };

type DashboardProps = {
    todo: { contacts: number; engagements: number; testimonials: number };
    visits: {
        total: number;
        period_days: number;
        period: number;
        today: number;
        french: number;
        english: number;
        daily: { date: string; count: number }[];
        top_pages: { path: string; count: number }[];
    };
    content: {
        projects: {
            published: number;
            archived: number;
            featured: number;
            open_source: number;
        };
        skills: number;
        technologies: number;
        domains: number;
        experiences: number;
        educations: number;
        references_on_cv: number;
        years_of_experience: number;
        testimonials: {
            approved: number;
            pending: number;
            rejected: number;
            featured: number;
        };
        contacts: number;
        engagements: { freelance: number; hiring: number; cv_sent: number };
        congratulations: number;
    };
    distribution: {
        projects_by_domain: Bar[];
        skills_by_domain: Bar[];
        technologies_by_category: { category: string; count: number }[];
    };
    health: { key: string; ok: boolean; count: number }[];
    recent: {
        contacts: {
            id: number;
            name: string;
            subject: string | null;
            is_new: boolean;
            at: string;
        }[];
        engagements: {
            id: number;
            name: string;
            company: string | null;
            type: 'freelance' | 'hiring';
            subject: string | null;
            is_new: boolean;
            at: string;
        }[];
        testimonials: {
            id: number;
            name: string;
            excerpt: string;
            at: string;
        }[];
    };
};

const number = new Intl.NumberFormat('fr-FR');
const shortDate = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
});
const longDate = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

/** Libellés et liens de la liste « à compléter ». */
const HEALTH = {
    profile_photo: {
        label: 'Photo du profil (site)',
        done: 'Photo du profil renseignée',
        href: profileEdit(),
    },
    cv_photo: {
        label: 'Photo du CV',
        done: 'Photo du CV renseignée',
        href: profileEdit(),
    },
    cv_identity: {
        label: 'Nom et prénoms du CV',
        done: 'Nom et prénoms du CV renseignés',
        href: profileEdit(),
    },
    cv_references: {
        label: 'Références à joindre au CV',
        done: 'Références jointes au CV',
        href: referencesIndex(),
    },
    project_covers: {
        label: 'Images de couverture des projets',
        done: 'Tous les projets ont une couverture',
        href: projectsIndex(),
    },
    featured_testimonials: {
        label: 'Avis à la une choisis',
        done: 'Avis à la une choisis',
        href: testimonialsIndex(),
    },
} as const;

function StatCard({
    title,
    value,
    hint,
    icon: Icon,
}: {
    title: string;
    value: string;
    hint?: string;
    icon: ComponentType<{ className?: string }>;
}) {
    return (
        <Card className="gap-2 py-4">
            <CardHeader className="flex flex-row items-center justify-between gap-2 px-5">
                <CardDescription>{title}</CardDescription>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent className="px-5">
                <p className="text-3xl font-semibold tracking-tight tabular-nums">
                    {value}
                </p>
                {hint && (
                    <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
                )}
            </CardContent>
        </Card>
    );
}

/** Une ligne « à traiter » : nombre, libellé et lien ; discrète quand il n'y a rien à faire. */
function TodoCard({
    title,
    count,
    href,
    icon: Icon,
    done,
}: {
    title: string;
    count: number;
    href: ReturnType<typeof contactsIndex>;
    icon: ComponentType<{ className?: string }>;
    done: string;
}) {
    const busy = count > 0;

    return (
        <Link href={href} prefetch>
            <Card
                className={`gap-1 py-4 transition-colors hover:bg-muted/40 ${busy ? 'border-primary/40 bg-primary/5' : ''}`}
            >
                <CardHeader className="flex flex-row items-center justify-between px-5">
                    <CardDescription className={busy ? 'text-foreground' : ''}>
                        {title}
                    </CardDescription>
                    <Icon className="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent className="px-5">
                    {busy ? (
                        <p className="text-3xl font-semibold tabular-nums">
                            {count}
                        </p>
                    ) : (
                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <CheckCircle2 className="size-4 text-primary" />{' '}
                            {done}
                        </p>
                    )}
                </CardContent>
            </Card>
        </Link>
    );
}

/** Barres horizontales, dans la couleur du domaine quand elle existe. */
function Bars({
    rows,
    empty = 'Aucune donnée.',
}: {
    rows: Bar[];
    empty?: string;
}) {
    if (rows.length === 0) {
        return (
            <Empty className="border py-6">
                <EmptyHeader>
                    <EmptyDescription>{empty}</EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <ChartContainer
            config={{ count: { label: 'Éléments', color: 'var(--chart-1)' } }}
            className="w-full"
            style={{ height: rows.length * 36 + 8 }}
        >
            <BarChart
                data={rows}
                layout="vertical"
                margin={{ left: 0, right: 16 }}
            >
                <YAxis
                    dataKey="label"
                    type="category"
                    tickLine={false}
                    axisLine={false}
                    width={110}
                />
                <XAxis type="number" hide allowDecimals={false} />
                <ChartTooltip
                    cursor={false}
                    content={<ChartTooltipContent hideLabel />}
                />
                <Bar dataKey="count" radius={4} barSize={18}>
                    {rows.map((row) => (
                        <Cell
                            key={row.label}
                            fill={row.color ?? 'var(--color-count)'}
                        />
                    ))}
                    <LabelList
                        dataKey="count"
                        position="right"
                        className="fill-foreground"
                        fontSize={12}
                    />
                </Bar>
            </BarChart>
        </ChartContainer>
    );
}

/** Courbe d'audience : visites par jour sur la période, valeur exacte au survol. */
function VisitsChart({ daily }: { daily: DashboardProps['visits']['daily'] }) {
    return (
        <ChartContainer
            config={{ count: { label: 'Visites', color: 'var(--chart-1)' } }}
            className="h-56 w-full"
        >
            <AreaChart data={daily} margin={{ left: 0, right: 8, top: 8 }}>
                <CartesianGrid vertical={false} />
                <XAxis
                    dataKey="date"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={32}
                    tickFormatter={(value: string) =>
                        shortDate.format(new Date(value))
                    }
                />
                <YAxis
                    tickLine={false}
                    axisLine={false}
                    width={28}
                    allowDecimals={false}
                />
                <ChartTooltip
                    cursor={false}
                    content={
                        <ChartTooltipContent
                            indicator="line"
                            labelFormatter={(value) =>
                                shortDate.format(new Date(String(value)))
                            }
                        />
                    }
                />
                <defs>
                    <linearGradient
                        id="visits-fill"
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop
                            offset="5%"
                            stopColor="var(--color-count)"
                            stopOpacity={0.6}
                        />
                        <stop
                            offset="95%"
                            stopColor="var(--color-count)"
                            stopOpacity={0.05}
                        />
                    </linearGradient>
                </defs>
                <Area
                    dataKey="count"
                    type="monotone"
                    stroke="var(--color-count)"
                    fill="url(#visits-fill)"
                    strokeWidth={2}
                />
            </AreaChart>
        </ChartContainer>
    );
}

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                {description && (
                    <CardDescription>{description}</CardDescription>
                )}
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

function RecentList({
    items,
    empty,
}: {
    items: {
        key: number;
        title: string;
        meta: string;
        isNew?: boolean;
        badge?: string;
    }[];
    empty: string;
}) {
    if (items.length === 0) {
        return (
            <Empty className="border py-6">
                <EmptyHeader>
                    <EmptyDescription>{empty}</EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <ul className="divide-y">
            {items.map((item) => (
                <li
                    key={item.key}
                    className="flex items-center justify-between gap-3 py-2.5 text-sm first:pt-0 last:pb-0"
                >
                    <div className="min-w-0">
                        <p className="truncate font-medium">{item.title}</p>
                        <p className="truncate text-xs text-muted-foreground">
                            {item.meta}
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {item.badge && (
                            <Badge variant="secondary">{item.badge}</Badge>
                        )}
                        {item.isNew && <Badge>Nouveau</Badge>}
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default function Dashboard({
    todo,
    visits,
    content,
    distribution,
    health,
    recent,
}: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = auth.user.name.split(' ')[0];
    const categories = Object.fromEntries(
        TECHNOLOGY_CATEGORIES.map((category) => [
            category.value,
            category.label,
        ]),
    );
    const french =
        visits.french + visits.english > 0
            ? Math.round(
                  (visits.french / (visits.french + visits.english)) * 100,
              )
            : 0;
    const incomplete = health.filter((item) => !item.ok).length;
    const relative = (iso: string) => shortDate.format(new Date(iso));

    return (
        <>
            <Head title="Tableau de bord" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Bonjour {firstName}
                    </h1>
                    <p className="text-sm text-muted-foreground first-letter:capitalize">
                        {longDate.format(new Date())}
                    </p>
                </div>

                {/* À traiter */}
                <div className="grid gap-4 md:grid-cols-3">
                    <TodoCard
                        title="Messages non lus"
                        count={todo.contacts}
                        href={contactsIndex()}
                        icon={Mail}
                        done="Aucun message en attente"
                    />
                    <TodoCard
                        title="Collaborations à traiter"
                        count={todo.engagements}
                        href={engagementsIndex()}
                        icon={Handshake}
                        done="Aucune demande en attente"
                    />
                    <TodoCard
                        title="Avis à modérer"
                        count={todo.testimonials}
                        href={testimonialsIndex()}
                        icon={MessageSquareQuote}
                        done="Aucun avis en attente"
                    />
                </div>

                {/* Chiffres clés */}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Visites aujourd'hui"
                        value={number.format(visits.today)}
                        hint={`${number.format(visits.total)} depuis le début`}
                        icon={Eye}
                    />
                    <StatCard
                        title={`Visites sur ${visits.period_days} jours`}
                        value={number.format(visits.period)}
                        hint={`${french} % en français`}
                        icon={Eye}
                    />
                    <StatCard
                        title="Félicitations reçues"
                        value={number.format(content.congratulations)}
                        hint="Distinction du CIAPOL"
                        icon={PartyPopper}
                    />
                    <StatCard
                        title="Années d'expérience"
                        value={String(content.years_of_experience)}
                        hint={`${content.experiences} expériences, ${content.educations} formations`}
                        icon={Sparkles}
                    />
                </div>

                {/* Audience */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Section
                            title="Audience"
                            description={`Visites par jour sur les ${visits.period_days} derniers jours`}
                        >
                            <VisitsChart daily={visits.daily} />
                        </Section>
                    </div>
                    <Section
                        title="Pages les plus vues"
                        description={`Sur ${visits.period_days} jours`}
                    >
                        {visits.top_pages.length === 0 ? (
                            <Empty className="border py-6">
                                <EmptyHeader>
                                    <EmptyDescription>
                                        Aucune visite enregistrée.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <ul className="space-y-2.5">
                                {visits.top_pages.map((page) => (
                                    <li
                                        key={page.path}
                                        className="flex items-center justify-between gap-3 text-sm"
                                    >
                                        <span className="truncate font-mono text-xs">
                                            {page.path}
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {number.format(page.count)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Section>
                </div>

                {/* Répartition du contenu */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Section
                        title="Projets par domaine"
                        description={`${content.projects.published} publiés · ${content.projects.featured} à la une · ${content.projects.open_source} open source`}
                    >
                        <Bars
                            rows={distribution.projects_by_domain}
                            empty="Aucun projet."
                        />
                    </Section>
                    <Section
                        title="Compétences par domaine"
                        description={`${content.skills} compétences · ${content.domains} domaines`}
                    >
                        <Bars
                            rows={distribution.skills_by_domain}
                            empty="Aucune compétence."
                        />
                    </Section>
                    <Section
                        title="Technologies par catégorie"
                        description={`${content.technologies} technologies`}
                    >
                        <Bars
                            rows={distribution.technologies_by_category.map(
                                (row) => ({
                                    label:
                                        categories[row.category] ??
                                        row.category,
                                    count: row.count,
                                }),
                            )}
                            empty="Aucune technologie."
                        />
                    </Section>
                </div>

                {/* Santé du contenu + activité */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Section
                        title="Contenu à compléter"
                        description={
                            incomplete === 0
                                ? 'Tout est en place.'
                                : `${incomplete} point${incomplete > 1 ? 's' : ''} à compléter pour un site et des CV complets`
                        }
                    >
                        <ul className="space-y-2.5">
                            {health.map((item) => {
                                const meta =
                                    HEALTH[item.key as keyof typeof HEALTH];

                                return (
                                    <li key={item.key}>
                                        <Link
                                            href={meta.href}
                                            className="flex items-center gap-2.5 text-sm hover:underline"
                                        >
                                            {item.ok ? (
                                                <CheckCircle2 className="size-4 shrink-0 text-primary" />
                                            ) : (
                                                <AlertTriangle className="size-4 shrink-0 text-destructive" />
                                            )}
                                            <span
                                                className={
                                                    item.ok
                                                        ? 'text-muted-foreground'
                                                        : ''
                                                }
                                            >
                                                {item.ok
                                                    ? meta.done
                                                    : meta.label}
                                                {!item.ok &&
                                                    item.count > 0 &&
                                                    ` (${item.count})`}
                                            </span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </Section>

                    <Section
                        title="Derniers messages"
                        description={`${content.contacts} reçus au total`}
                    >
                        <RecentList
                            empty="Aucun message reçu."
                            items={recent.contacts.map((contact) => ({
                                key: contact.id,
                                title: contact.name,
                                meta: `${contact.subject ?? 'Sans sujet'} · ${relative(contact.at)}`,
                                isNew: contact.is_new,
                            }))}
                        />
                    </Section>

                    <Section
                        title="Dernières collaborations"
                        description={`${content.engagements.freelance} freelance · ${content.engagements.hiring} embauche · ${content.engagements.cv_sent} CV envoyés`}
                    >
                        <RecentList
                            empty="Aucune demande reçue."
                            items={recent.engagements.map((engagement) => ({
                                key: engagement.id,
                                title: engagement.company
                                    ? `${engagement.name} · ${engagement.company}`
                                    : engagement.name,
                                meta: `${engagement.subject ?? ''} · ${relative(engagement.at)}`,
                                badge:
                                    engagement.type === 'hiring'
                                        ? 'Embauche'
                                        : 'Freelance',
                                isNew: engagement.is_new,
                            }))}
                        />
                    </Section>
                </div>

                {recent.testimonials.length > 0 && (
                    <Section
                        title="Avis en attente de modération"
                        description={`${content.testimonials.approved} approuvés · ${content.testimonials.featured} à la une sur 3`}
                    >
                        <RecentList
                            empty=""
                            items={recent.testimonials.map((testimonial) => ({
                                key: testimonial.id,
                                title: testimonial.name,
                                meta: `${testimonial.excerpt} · ${relative(testimonial.at)}`,
                            }))}
                        />
                    </Section>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Tableau de bord',
            href: dashboard(),
        },
    ],
};
