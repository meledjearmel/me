import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

function readStoredOpen(key: string): boolean {
    try {
        return localStorage.getItem(key) === 'open';
    } catch {
        return false;
    }
}

export function NavMain({
    items,
    label = 'Plateforme',
    collapsible = false,
}: {
    items: NavItem[];
    label?: string;
    collapsible?: boolean;
}) {
    const { isCurrentUrl } = useCurrentUrl();
    const hasActiveItem = items.some((item) => isCurrentUrl(item.href));
    const storageKey = `admin-nav:${label}`;
    // Le choix mémorisé n'est lu qu'après l'hydratation : le rendu serveur n'a pas de localStorage.
    const [open, setOpen] = useState(hasActiveItem);

    useEffect(() => {
        if (hasActiveItem || readStoredOpen(storageKey)) {
            setOpen(true);
        }
    }, [hasActiveItem, storageKey]);

    const handleOpenChange = (value: boolean) => {
        setOpen(value);

        try {
            localStorage.setItem(storageKey, value ? 'open' : 'closed');
        } catch {
            // Stockage indisponible : l'état reste valable pour la session.
        }
    };

    const menu = (
        <SidebarMenu>
            {items.map((item) => (
                <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton
                        asChild
                        isActive={isCurrentUrl(item.href)}
                        tooltip={{ children: item.title }}
                    >
                        <Link href={item.href} prefetch>
                            {item.icon && <item.icon />}
                            <span>{item.title}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            ))}
        </SidebarMenu>
    );

    if (!collapsible) {
        return (
            <SidebarGroup className="px-2 py-0">
                <SidebarGroupLabel>{label}</SidebarGroupLabel>
                {menu}
            </SidebarGroup>
        );
    }

    return (
        <Collapsible open={open} onOpenChange={handleOpenChange} className="group/collapsible">
            <SidebarGroup className="px-2 py-0">
                <SidebarGroupLabel asChild>
                    <CollapsibleTrigger className="cursor-pointer hover:text-sidebar-foreground">
                        {label}
                        <ChevronRight className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                    </CollapsibleTrigger>
                </SidebarGroupLabel>
                <CollapsibleContent>{menu}</CollapsibleContent>
            </SidebarGroup>
        </Collapsible>
    );
}
