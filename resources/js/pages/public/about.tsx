import { Head, usePage } from '@inertiajs/react';
import AboutFacts from '@/components/public/about-facts';
import AboutIntro from '@/components/public/about-intro';
import AboutStory from '@/components/public/about-story';
import AboutYears from '@/components/public/about-years';
import ExperienceList from '@/components/public/experience-list';
import PublicShell from '@/components/public/public-shell';
import { useTranslations } from '@/lib/i18n';
import EducationList from '@/components/public/education-list';
import type {
    PublicEducation,
    PublicExperience,
    PublicProfile,
} from '@/types';

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
    const { props } = usePage<{ profile: PublicProfile }>();
    const t = useTranslations();

    return (
        <>
            <Head title={t.about.title}>
                <meta name="description" content={props.profile.bio_short} />
            </Head>

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
