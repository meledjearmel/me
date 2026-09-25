import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import ActionMenu from '@/components/public/action-menu';
import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { useSmoothAnchors } from '@/hooks/use-smooth-anchors';
import type { PublicProfile } from '@/types';

export default function PublicShell({
    children,
    overHero = false,
}: {
    children: ReactNode;
    overHero?: boolean;
}) {
    const { props } = usePage<{ profile: PublicProfile }>();

    useSmoothAnchors();

    return (
        <div className={`pub${overHero ? '' : ' pub--plain'}`}>
            <SiteHeader overHero={overHero} />
            {/* role explicite : main est en display: contents (voir public.css) */}
            <main role="main">{children}</main>
            <SiteFooter
                name={props.profile.name}
                email={props.profile.email}
                socialLinks={props.profile.social_links}
            />
            <ActionMenu />
        </div>
    );
}
