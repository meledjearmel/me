import { Link } from '@inertiajs/react';
import {
    Briefcase,
    FolderGit2,
    GraduationCap,
    Handshake,
    LayoutGrid,
    Layers,
    Mail,
    MessageSquareQuote,
    Sparkles,
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
import { index as testimonialsIndex } from '@/routes/admin/testimonials';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const contentNavItems: NavItem[] = [
    { title: 'Profil', href: profileEdit(), icon: User },
    { title: 'Domaines', href: domainsIndex(), icon: Layers },
    { title: 'Technologies', href: technologiesIndex(), icon: Wrench },
    { title: 'Profils métier', href: jobProfilesIndex(), icon: Briefcase },
    { title: 'Compétences', href: skillsIndex(), icon: Sparkles },
    { title: 'Formation', href: educationsIndex(), icon: GraduationCap },
    { title: 'Expériences', href: experiencesIndex(), icon: Briefcase },
    { title: 'Projets', href: projectsIndex(), icon: FolderGit2 },
    { title: 'Avis', href: testimonialsIndex(), icon: MessageSquareQuote },
    { title: 'Contacts', href: contactsIndex(), icon: Mail },
    { title: 'Collaborations', href: engagementsIndex(), icon: Handshake },
    { title: 'Références', href: referencesIndex(), icon: UserCheck },
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

            {/* Les deux groupes de menus sont centrés verticalement dans la barre. */}
            <SidebarContent className="justify-center gap-4">
                <NavMain items={mainNavItems} />
                <NavMain items={contentNavItems} label="Contenu" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
