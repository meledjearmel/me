import { Link } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    CalendarClock,
    CalendarCog,
    CalendarDays,
    Disc3,
    Laptop,
    BadgeCheck,
    Trophy,
    Clock,
    FolderGit,
    GitCompare,
    FolderGit2,
    GraduationCap,
    FileDown,
    MailCheck,
    Handshake,
    Heart,
    Calculator,
    Coins,
    ReceiptText,
    ListPlus,
    Store,
    FileText,
    Send,
    CalendarCheck,
    Share2,
    BadgeDollarSign,
    IdCard,
    LayoutGrid,
    Layers,
    Mail,
    Link2,
    MessageSquareQuote,
    MessagesSquare,
    Newspaper,
    Music,
    PartyPopper,
    Sparkles,
    Tags,
    ListOrdered,
    User,
    UserCheck,
    Wrench,
} from 'lucide-react';
import { ArrowUpRight, ChevronRight, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as appointmentTypesIndex } from '@/routes/admin/appointment-types';
import { index as appointmentsIndex } from '@/routes/admin/appointments';
import { index as availabilityIndex } from '@/routes/admin/availability';
import { index as celebrationsIndex } from '@/routes/admin/celebrations';
import { index as congratulationsIndex } from '@/routes/admin/congratulations';
import { index as contactsIndex } from '@/routes/admin/contacts';
import { index as cvDownloadsIndex } from '@/routes/admin/cv-downloads';
import { index as reviewInvitationsIndex } from '@/routes/admin/review-invitations';
import { index as subscribersIndex } from '@/routes/admin/subscribers';
import { index as engagementsIndex } from '@/routes/admin/engagements';
import { index as estimatorItemsIndex } from '@/routes/admin/estimator-items';
import { index as servicesIndex } from '@/routes/admin/services';
import { index as jobApplicationsIndex } from '@/routes/admin/job-applications';
import { index as proposalsIndex } from '@/routes/admin/proposals';
import { show as weeklyReportShow } from '@/routes/admin/weekly-report';
import { edit as syndicationEdit } from '@/routes/admin/syndication';
import { index as estimatorOptionsIndex } from '@/routes/admin/estimator-options';
import { index as estimatorRatesIndex } from '@/routes/admin/estimator-rates';
import { index as commercialDocumentsIndex } from '@/routes/admin/commercial-documents';
import { edit as estimatorSettingsEdit } from '@/routes/admin/estimator-settings';
import { index as domainsIndex } from '@/routes/admin/domains';
import { index as educationsIndex } from '@/routes/admin/educations';
import { index as experiencesIndex } from '@/routes/admin/experiences';
import { index as jobProfilesIndex } from '@/routes/admin/job-profiles';
import { edit as profileEdit } from '@/routes/admin/profile';
import { index as referencesIndex } from '@/routes/admin/professional-references';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as postTagsIndex } from '@/routes/admin/post-tags';
import { index as postSeriesIndex } from '@/routes/admin/post-series';
import { index as projectsIndex } from '@/routes/admin/projects';
import { index as skillsIndex } from '@/routes/admin/skills';
import { index as technologiesIndex } from '@/routes/admin/technologies';
import { index as technologyCategoriesIndex } from '@/routes/admin/technology-categories';
import { index as musicGenresIndex } from '@/routes/admin/music-genres';
import { index as usesItemsIndex } from '@/routes/admin/uses-items';
import { index as certificationsIndex } from '@/routes/admin/certifications';
import { index as distinctionsIndex } from '@/routes/admin/distinctions';
import { edit as nowPageEdit } from '@/routes/admin/now-page';
import { edit as githubEdit } from '@/routes/admin/github';
import { index as revisionsIndex } from '@/routes/admin/revisions';
import { index as testimonialsIndex } from '@/routes/admin/testimonials';
import { index as postCommentsIndex } from '@/routes/admin/post-comments';
import { index as tracksIndex } from '@/routes/admin/tracks';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Rapport de la semaine',
        href: weeklyReportShow(),
        icon: CalendarCheck,
    },
];

const careerNavItems: NavItem[] = [
    { title: 'Profil', href: profileEdit(), icon: User },
    { title: 'Expériences', href: experiencesIndex(), icon: BriefcaseBusiness },
    { title: 'Formation', href: educationsIndex(), icon: GraduationCap },
    { title: 'Compétences', href: skillsIndex(), icon: Sparkles },
];

const blogNavItems: NavItem[] = [
    { title: 'Articles', href: postsIndex(), icon: Newspaper },
    { title: 'Tags du blog', href: postTagsIndex(), icon: Tags },
    { title: 'Séries du blog', href: postSeriesIndex(), icon: ListOrdered },
    { title: 'Commentaires', href: postCommentsIndex(), icon: MessagesSquare },
    { title: 'Republication', href: syndicationEdit(), icon: Share2 },
];

const portfolioNavItems: NavItem[] = [
    { title: 'Projets', href: projectsIndex(), icon: FolderGit2 },
    { title: 'Services', href: servicesIndex(), icon: Store },
    { title: 'Certifications', href: certificationsIndex(), icon: BadgeCheck },
    { title: 'Distinctions', href: distinctionsIndex(), icon: Trophy },
    { title: 'Avis', href: testimonialsIndex(), icon: MessageSquareQuote },
    {
        title: 'Demandes d’avis',
        href: reviewInvitationsIndex(),
        icon: Link2,
    },
    { title: 'Références', href: referencesIndex(), icon: UserCheck },
];

const publicPageNavItems: NavItem[] = [
    { title: 'Uses', href: usesItemsIndex(), icon: Laptop },
    { title: 'Page « Now »', href: nowPageEdit(), icon: Clock },
    { title: 'Dépôts GitHub', href: githubEdit(), icon: FolderGit },
    { title: 'Révisions IA', href: revisionsIndex(), icon: GitCompare },
    { title: 'Surprises', href: celebrationsIndex(), icon: PartyPopper },
];

const referenceNavItems: NavItem[] = [
    { title: 'Domaines', href: domainsIndex(), icon: Layers },
    { title: 'Technologies', href: technologiesIndex(), icon: Wrench },
    {
        title: 'Catégories tech.',
        href: technologyCategoriesIndex(),
        icon: Tags,
    },
    { title: 'Profils métier', href: jobProfilesIndex(), icon: IdCard },
];

const musicNavItems: NavItem[] = [
    { title: 'Pistes', href: tracksIndex(), icon: Music },
    { title: 'Registres', href: musicGenresIndex(), icon: Disc3 },
];

const inboxNavItems: NavItem[] = [
    { title: 'Contacts', href: contactsIndex(), icon: Mail },
    { title: 'Collaborations', href: engagementsIndex(), icon: Handshake },
    {
        title: 'Devis et factures',
        href: commercialDocumentsIndex(),
        icon: ReceiptText,
    },
    { title: 'Candidatures', href: jobApplicationsIndex(), icon: Send },
    {
        title: 'Propositions freelance',
        href: proposalsIndex(),
        icon: FileText,
    },
    { title: 'Téléchargements CV', href: cvDownloadsIndex(), icon: FileDown },
    { title: 'Newsletter', href: subscribersIndex(), icon: MailCheck },
    { title: 'Félicitations', href: congratulationsIndex(), icon: Heart },
];

const estimatorNavItems: NavItem[] = [
    { title: 'Types de projet', href: estimatorItemsIndex(), icon: Calculator },
    { title: 'Options', href: estimatorOptionsIndex(), icon: ListPlus },
    { title: 'Grilles tarifaires', href: estimatorRatesIndex(), icon: Coins },
    {
        title: 'Tarifs et calcul',
        href: estimatorSettingsEdit(),
        icon: BadgeDollarSign,
    },
];

const bookingNavItems: NavItem[] = [
    { title: 'Rendez-vous', href: appointmentsIndex(), icon: CalendarDays },
    { title: 'Disponibilités', href: availabilityIndex(), icon: CalendarClock },
    {
        title: 'Types de rendez-vous',
        href: appointmentTypesIndex(),
        icon: CalendarCog,
    },
];

const navigation: { label: string; items: NavItem[] }[] = [
    { label: 'Échanges', items: inboxNavItems },
    { label: 'Rendez-vous', items: bookingNavItems },
    { label: 'Estimateur', items: estimatorNavItems },
    { label: 'Blog', items: blogNavItems },
    { label: 'Portfolio', items: portfolioNavItems },
    { label: 'Parcours', items: careerNavItems },
    { label: 'Page publique', items: publicPageNavItems },
    { label: 'Référentiels', items: referenceNavItems },
    { label: 'Musique', items: musicNavItems },
];

/**
 * Barre latérale de la console (référence Donezo, comme la console de Folio) : une carte
 * fixe, le logo, le menu principal puis les rubriques repliables ; l'entrée active est
 * marquée par un trait à l'accent de la palette. Tiroir sur mobile.
 */
export function AppSidebar({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    return (
        <>
            <div
                className={cn(
                    'fixed inset-0 z-40 bg-black/40 lg:hidden',
                    open ? 'block' : 'hidden',
                )}
                onClick={onClose}
            />
            <aside
                className={cn(
                    'console-no-scrollbar fixed inset-y-3 left-3 z-50 flex w-64 shrink-0 flex-col overflow-y-auto rounded-[1.6rem] bg-[var(--card)] px-5 pb-5 transition-transform lg:static lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-[120%]',
                )}
            >
                <div className="sticky top-0 z-20 -mx-5 flex items-center justify-between bg-[var(--card)] px-5 pt-5 pb-3">
                    <Link
                        href={dashboard()}
                        prefetch
                        className="flex items-center gap-2.5 text-xl font-extrabold tracking-tight"
                    >
                        <span className="grid size-9 place-items-center rounded-full bg-[var(--fg)] text-[var(--bg)]">
                            <AppLogoIcon className="size-5 fill-current" />
                        </span>
                        Console
                    </Link>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-full p-1.5 lg:hidden"
                        aria-label="Fermer le menu"
                    >
                        <X className="size-5" />
                    </button>
                </div>

                <NavGroup
                    title="Menu"
                    items={mainNavItems}
                    onNavigate={onClose}
                />
                {navigation.map((group) => (
                    <NavGroup
                        key={group.label}
                        title={group.label}
                        items={group.items}
                        onNavigate={onClose}
                        collapsible
                    />
                ))}

                <div className="relative mt-8 shrink-0 overflow-hidden rounded-2xl bg-[var(--nav)] p-4 text-white">
                    <div
                        aria-hidden
                        className="absolute -top-10 -right-10 size-36 rounded-full bg-[radial-gradient(circle,var(--brand),transparent_70%)] opacity-60"
                    />
                    <p className="relative text-lg leading-tight font-bold">
                        Le site public
                    </p>
                    <p className="relative mt-1 text-xs text-[var(--nav-icon)]">
                        Voir le portfolio tel que les visiteurs le voient.
                    </p>
                    <a
                        href="/"
                        target="_blank"
                        rel="noreferrer"
                        className="relative mt-4 flex items-center justify-center gap-2 rounded-full bg-[var(--brand)] py-2.5 text-sm font-semibold text-[var(--on-brand)]"
                    >
                        Ouvrir le site
                        <ArrowUpRight className="size-4" />
                    </a>
                </div>
            </aside>
        </>
    );
}

function readStoredOpen(key: string): boolean {
    try {
        return localStorage.getItem(key) === 'open';
    } catch {
        return false;
    }
}

function NavGroup({
    title,
    items,
    onNavigate,
    collapsible = false,
}: {
    title: string;
    items: NavItem[];
    onNavigate: () => void;
    collapsible?: boolean;
}) {
    const { isCurrentUrl } = useCurrentUrl();
    // Le tableau de bord est la racine de la console : seule l'adresse exacte l'active.
    const isActive = (item: NavItem) =>
        isCurrentUrl(item.href, undefined, item.href !== mainNavItems[0].href);
    const hasActiveItem = items.some(isActive);
    const storageKey = `admin-nav:${title}`;
    // Le choix mémorisé n'est lu qu'après l'hydratation : le rendu serveur n'a pas de localStorage.
    const [open, setOpen] = useState(!collapsible || hasActiveItem);

    useEffect(() => {
        if (collapsible && (hasActiveItem || readStoredOpen(storageKey))) {
            setOpen(true);
        }
    }, [collapsible, hasActiveItem, storageKey]);

    const toggle = () => {
        setOpen(!open);

        try {
            localStorage.setItem(storageKey, open ? 'closed' : 'open');
        } catch {
            // Stockage indisponible : l'état reste valable pour la session.
        }
    };

    return (
        <nav className="mt-4 first-of-type:mt-5">
            {collapsible ? (
                <button
                    type="button"
                    onClick={toggle}
                    aria-expanded={open}
                    className="sticky top-[68px] z-10 -mx-5 flex w-[calc(100%+2.5rem)] items-center bg-[color-mix(in_oklab,var(--card)_72%,transparent)] px-8 py-2 text-xs font-medium tracking-wider text-[var(--muted-fg)] uppercase backdrop-blur-md hover:text-[var(--fg)]"
                >
                    {title}
                    <ChevronRight
                        className={cn(
                            'ml-auto size-4 transition-transform',
                            open && 'rotate-90',
                        )}
                    />
                </button>
            ) : (
                <p className="sticky top-[68px] z-10 -mx-5 bg-[color-mix(in_oklab,var(--card)_72%,transparent)] px-8 py-2 text-xs font-medium tracking-wider text-[var(--muted-fg)] uppercase backdrop-blur-md">
                    {title}
                </p>
            )}
            {open && (
                <ul className="mt-2 space-y-0.5">
                    {items.map((item) => {
                        const active = isActive(item);

                        return (
                            <li key={item.title}>
                                <Link
                                    href={item.href}
                                    prefetch
                                    onClick={onNavigate}
                                    className={cn(
                                        'relative flex items-center gap-3 rounded-xl px-3 py-2 text-[15px] transition',
                                        active
                                            ? 'font-semibold text-[var(--fg)]'
                                            : 'text-[var(--muted-fg)] hover:text-[var(--fg)]',
                                    )}
                                >
                                    {active && (
                                        <span className="absolute top-1/2 -left-5 h-8 w-1.5 -translate-y-1/2 rounded-r-full bg-[var(--brand)]" />
                                    )}
                                    {item.icon && (
                                        <item.icon className="size-5 shrink-0" />
                                    )}
                                    <span className="truncate">
                                        {item.title}
                                    </span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            )}
        </nav>
    );
}
