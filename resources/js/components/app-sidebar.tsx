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
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
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

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-3">
                <NavMain items={mainNavItems} />
                <NavMain items={inboxNavItems} label="Échanges" collapsible />
                <NavMain
                    items={bookingNavItems}
                    label="Rendez-vous"
                    collapsible
                />
                <NavMain
                    items={estimatorNavItems}
                    label="Estimateur"
                    collapsible
                />
                <NavMain items={blogNavItems} label="Blog" collapsible />
                <NavMain
                    items={portfolioNavItems}
                    label="Portfolio"
                    collapsible
                />
                <NavMain items={careerNavItems} label="Parcours" collapsible />
                <NavMain
                    items={publicPageNavItems}
                    label="Page publique"
                    collapsible
                />
                <NavMain
                    items={referenceNavItems}
                    label="Référentiels"
                    collapsible
                />
                <NavMain items={musicNavItems} label="Musique" collapsible />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
