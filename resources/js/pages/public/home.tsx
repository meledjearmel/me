import { usePage } from '@inertiajs/react';
import FeaturedProjects from '@/components/public/featured-projects';
import Hero from '@/components/public/hero';
import Intro from '@/components/public/intro';
import Testimonials from '@/components/public/testimonials';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale } from '@/lib/i18n';
import type {
    PublicJobProfile,
    PublicProfile,
    PublicProject,
    PublicTechnology,
    PublicTestimonial,
} from '@/types';

type SharedProps = {
    profile: PublicProfile;
    visitCount: number;
    siteUrl: string;
};

export default function Home({
    jobProfiles,
    featuredProjects,
    technologies,
    testimonials,
    testimonialCount,
}: {
    jobProfiles: PublicJobProfile[];
    featuredProjects: PublicProject[];
    technologies: PublicTechnology[];
    testimonials: PublicTestimonial[];
    testimonialCount: number;
}) {
    const { props } = usePage<SharedProps>();
    const locale = useLocale();

    return (
        <>
            <Seo
                title={props.profile.headline}
                description={props.profile.bio_short || props.profile.headline}
                type="profile"
                jsonLd={[
                    {
                        '@type': 'Person',
                        name: props.profile.name,
                        jobTitle: props.profile.headline,
                        description: props.profile.bio_short,
                        url: `${props.siteUrl}/${locale}`,
                        image: props.profile.photo_url ?? undefined,
                        address: props.profile.location ?? undefined,
                        sameAs: Object.values(
                            props.profile.social_links ?? {},
                        ).filter(Boolean),
                    },
                    {
                        '@type': 'WebSite',
                        name: props.profile.name,
                        url: props.siteUrl,
                        inLanguage: locale,
                    },
                ]}
            />

            <PublicShell overHero>
                <Hero profile={props.profile} jobProfiles={jobProfiles} />
                <Intro text={props.profile.bio_short} />
                <FeaturedProjects
                    projects={featuredProjects}
                    technologies={technologies}
                />
                <Testimonials testimonials={testimonials} total={testimonialCount} />
            </PublicShell>
        </>
    );
}
