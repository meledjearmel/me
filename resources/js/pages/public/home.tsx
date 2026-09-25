import { Head, usePage } from '@inertiajs/react';
import FeaturedProjects from '@/components/public/featured-projects';
import Hero from '@/components/public/hero';
import Intro from '@/components/public/intro';
import Testimonials from '@/components/public/testimonials';
import PublicShell from '@/components/public/public-shell';
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
            <Head title={props.profile.name}>
                <meta name="description" content={props.profile.headline} />
                <meta property="og:title" content={props.profile.name} />
                <meta
                    property="og:description"
                    content={props.profile.headline}
                />
                {props.profile.photo_url && (
                    <meta
                        property="og:image"
                        content={props.profile.photo_url}
                    />
                )}
                <link rel="alternate" hrefLang="fr" href="/fr" />
                <link rel="alternate" hrefLang="en" href="/en" />
                <link rel="canonical" href={`/${locale}`} />
            </Head>

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
