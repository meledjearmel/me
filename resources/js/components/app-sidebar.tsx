import { Link } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    Disc3,
    FolderGit2,
    GraduationCap,
    Handshake,
    IdCard,
    LayoutGrid,
    Layers,
    Mail,
    MessageSquareQuote,
    Music,
    Sparkles,
    Tags,
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
import { index as contactsIndex } from '@/routes/admin/contacts';
import { index as engagementsIndex } from '@/routes/admin/engagements';
import { index as domainsIndex } from '@/routes/admin/domains';
import { index as educationsIndex } from '@/routes/admin/educations';
import { index as experiencesIndex } from '@/routes/admin/experiences';
import { index as jobProfilesIndex } from '@/routes/admin/job-profiles';
import { edit as profileEdit } from '@/routes/admin/profile';
import { index as referencesIndex } from '@/routes/admin/professional-references';
import { index as projectsIndex } from '@/routes/admin/projects';
import { index as skillsIndex } from '@/routes/admin/skills';
import { index as technologiesIndex } from '@/routes/admin/technologies';
import { index as technologyCategoriesIndex } from '@/routes/admin/technology-categories';
import { index as musicGenresIndex } from '@/routes/admin/music-genres';
import { index as testimonialsIndex } from '@/routes/admin/testimonials';
import { index as tracksIndex } from '@/routes/admin/tracks';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const careerNavItems: NavItem[] = [
    { title: 'Profil', href: profileEdit(), icon: User },
    { title: 'Expériences', href: experiencesIndex(), icon: BriefcaseBusiness },
    { title: 'Formation', href: educationsIndex(), icon: GraduationCap },
    { title: 'Compétences', href: skillsIndex(), icon: Sparkles },
];

const portfolioNavItems: NavItem[] = [
    { title: 'Projets', href: projectsIndex(), icon: FolderGit2 },
    { title: 'Avis', href: testimonialsIndex(), icon: MessageSquareQuote },
    { title: 'Références', href: referencesIndex(), icon: UserCheck },
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
                <NavMain items={careerNavItems} label="Parcours" />
                <NavMain items={portfolioNavItems} label="Réalisations" />
                <NavMain items={referenceNavItems} label="Référentiels" />
                <NavMain items={musicNavItems} label="Musique" />
                <NavMain items={inboxNavItems} label="Échanges" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
