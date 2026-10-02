import { usePage } from '@inertiajs/react';
import Seo from '@/components/public/seo';
import AboutFacts from '@/components/public/about-facts';
import AboutIntro from '@/components/public/about-intro';
import AboutStory from '@/components/public/about-story';
import AboutYears from '@/components/public/about-years';
import ExperienceList from '@/components/public/experience-list';
import PublicShell from '@/components/public/public-shell';
import { useTranslations } from '@/lib/i18n';
import EducationList from '@/components/public/education-list';
import type { PublicEducation, PublicExperience, PublicProfile } from '@/types';

export default function About({
    experiences,
    educations,
    congratulations,
    yearsOfExperience,
}: {
    experiences: PublicExperience[];
    educations: PublicEducation[];
    congratulations: number;
    yearsOfExperience: number;
}) {
    const { props } = usePage<{ profile: PublicProfile; siteUrl: string }>();
    const t = useTranslations();

    return (
        <>
            <Seo
                title={t.about.title}
                description={props.profile.bio_short}
                type="profile"
                jsonLd={{
                    '@type': 'ProfilePage',
                    mainEntity: {
                        '@type': 'Person',
                        '@id': `${props.siteUrl}/#person`,
                        name: props.profile.name,
                        jobTitle: props.profile.headline,
                        description: props.profile.bio_full,
                        image: props.profile.photo_url ?? undefined,
                    },
                }}
            />

            <PublicShell overHero>
                <AboutIntro years={yearsOfExperience} />
                <AboutStory text={props.profile.bio_full} />
                <AboutYears years={yearsOfExperience} />
                <ExperienceList experiences={experiences} />
                <EducationList educations={educations} />
                <AboutFacts congratulations={congratulations} />
            </PublicShell>
        </>
    );
}
