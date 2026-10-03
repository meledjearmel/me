import { usePage } from '@inertiajs/react';
import { MotionConfig } from 'framer-motion';
import type { ReactNode } from 'react';
import ActionMenu from '@/components/public/action-menu';
import ChatAssistant from '@/components/public/chat-assistant';
import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { useSmoothAnchors } from '@/hooks/use-smooth-anchors';
import { useTranslations } from '@/lib/i18n';
import type { PublicProfile } from '@/types';

export default function PublicShell({
    children,
    overHero = false,
}: {
    children: ReactNode;
    overHero?: boolean;
}) {
    const { props } = usePage<{ profile: PublicProfile }>();
    const t = useTranslations();

    useSmoothAnchors();

    // Avec « réduire les animations », framer-motion ne garde que les fondus.
    return (
        <MotionConfig reducedMotion="user">
            <div className={`pub${overHero ? '' : ' pub--plain'}`}>
                <a className="pub-skip" href="#contenu">
                    {t.nav.skipToContent}
                </a>
                <SiteHeader overHero={overHero} />
                {/* role explicite : main est en display: contents (voir public.css) */}
                <main role="main">
                    {/* main en display: contents ne peut pas recevoir le focus : la cible du lien d'évitement est cet élément. */}
                    <div id="contenu" tabIndex={-1} className="pub-skip-target" />
                    {children}
                </main>
                <SiteFooter
                    name={props.profile.name}
                    email={props.profile.email}
                    socialLinks={props.profile.social_links}
                />
                <ActionMenu />
                <ChatAssistant />
            </div>
        </MotionConfig>
    );
}
