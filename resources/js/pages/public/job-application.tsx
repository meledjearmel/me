import { Head, Link, usePage } from '@inertiajs/react';
import JobApplicationController from '@/actions/App/Http/Controllers/JobApplicationController';
import { accentStyle } from '@/components/public/project-parts';
import MentionText, {
    type MentionCards,
} from '@/components/public/mention-text';
import PublicShell from '@/components/public/public-shell';
import { useLocale, useLocalizedPath, useTranslations } from '@/lib/i18n';
import type { PublicProfile, PublicProject, PublicTestimonial } from '@/types';

type Application = {
    token: string;
    company: string;
    contact_name: string | null;
    position: string;
    intro: string;
    has_cv: boolean;
    /** Cartes des projets cités dans le mot, par « type:id ». */
    mentions: MentionCards;
};

/**
 * Page de candidature vue par l'entreprise, en forme de lettre : l'en-tête « Pour Acme SA »,
 * la salutation et le mot en colonne de lecture, signés ; une carte collante avec le poste,
 * le CV et le rendez-vous ; puis les projets choisis en grandes cartes alternées et les avis.
 * Privée et jamais indexée.
 */
export default function JobApplicationPage({
    application,
    projects,
    testimonials,
}: {
    application: Application;
    projects: PublicProject[];
    testimonials: PublicTestimonial[];
}) {
    const t = useTranslations();
    const locale = useLocale();
    const path = useLocalizedPath();
    const { profile, bookingOpen } = usePage<{
        profile: PublicProfile;
        bookingOpen?: boolean;
    }>().props;
    const paragraphs = application.intro.split(/\n{2,}/).filter(Boolean);

    return (
        <>
            <Head title={t.jobApplication.title(application.company)}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <PublicShell>
                <div className="pub-application site-wrap">
                    <header className="pub-application__head">
                        <p className="pub-application__for">
                            {t.jobApplication.forCompany(application.company)}
                        </p>
                        <h1 className="pub-application__position">
                            {application.position}
                        </h1>
                    </header>

                    <div className="pub-application__layout">
                        <article className="pub-application__body">
                            <p className="pub-application__greeting">
                                {t.jobApplication.greeting(
                                    application.contact_name,
                                )}
                            </p>
                            {paragraphs.map((paragraph, index) => (
                                <p
                                    key={index}
                                    className="pub-application__paragraph"
                                >
                                    <MentionText
                                        text={paragraph}
                                        mentions={application.mentions}
                                    />
                                </p>
                            ))}
                            <p className="pub-application__paragraph">
                                {t.jobApplication.closing}
                            </p>

                            <footer className="pub-application__signature">
                                {profile.photo_url && (
                                    <img
                                        src={profile.photo_url}
                                        alt=""
                                        className="pub-application__photo"
                                    />
                                )}
                                <span>
                                    <strong>{profile.name}</strong>
                                    <span>{profile.headline}</span>
                                </span>
                            </footer>
                        </article>

                        <aside
                            className="pub-application__card"
                            aria-label={t.jobApplication.sticky}
                        >
                            <p className="pub-application__label">
                                {t.jobApplication.position}
                            </p>
                            <p className="pub-application__card-title">
                                {application.position}
                            </p>
                            <p className="pub-application__card-company">
                                {application.company}
                            </p>

                            <div className="pub-application__actions">
                                {application.has_cv && (
                                    <a
                                        href={JobApplicationController.cv.url({
                                            locale,
                                            token: application.token,
                                        })}
                                        className="pub-drawer__send"
                                    >
                                        {t.jobApplication.cv}
                                        <span aria-hidden="true">↓</span>
                                    </a>
                                )}
                                {bookingOpen && (
                                    <Link
                                        href={path('appointments')}
                                        className="pub-application__secondary"
                                    >
                                        {t.jobApplication.book}
                                        <span aria-hidden="true">→</span>
                                    </Link>
                                )}
                                <a
                                    href={`mailto:${profile.email}`}
                                    className="pub-application__secondary"
                                >
                                    {t.jobApplication.contact}
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>

                            <p className="pub-application__label">
                                {t.jobApplication.yourContact}
                            </p>
                            <p className="pub-application__contact">
                                {profile.name}
                                <br />
                                <a href={`mailto:${profile.email}`}>
                                    {profile.email}
                                </a>
                                {profile.phone && (
                                    <>
                                        <br />
                                        <a
                                            href={`tel:${profile.phone.replace(/\s+/g, '')}`}
                                        >
                                            {profile.phone}
                                        </a>
                                    </>
                                )}
                            </p>
                        </aside>
                    </div>

                    {projects.length > 0 && (
                        <section
                            className="pub-application__projects"
                            aria-labelledby="pub-application-projects"
                        >
                            <h2
                                id="pub-application-projects"
                                className="pub-application__section-title"
                            >
                                {t.jobApplication.projects}
                            </h2>

                            {projects.map((project, index) => (
                                <article
                                    key={project.id}
                                    className="pub-application__project"
                                    style={accentStyle(
                                        project.accent_color,
                                        index,
                                    )}
                                >
                                    <Link
                                        href={path(`projects/${project.slug}`)}
                                        className="pub-application__project-media"
                                        tabIndex={-1}
                                        aria-hidden="true"
                                    >
                                        {project.cover_url ? (
                                            <img
                                                src={project.cover_url}
                                                alt=""
                                                loading="lazy"
                                            />
                                        ) : (
                                            <span>{project.title}</span>
                                        )}
                                    </Link>

                                    <div className="pub-application__project-copy">
                                        <p className="pub-application__label">
                                            {String(index + 1).padStart(2, '0')}{' '}
                                            ·{' '}
                                            {t.jobApplication.projectIndex(
                                                index + 1,
                                                projects.length,
                                            )}
                                        </p>
                                        <h3>{project.title}</h3>
                                        {project.tagline && (
                                            <p>{project.tagline}</p>
                                        )}
                                        {project.technologies.length > 0 && (
                                            <ul
                                                className="pub-application__tags"
                                                aria-label={
                                                    t.jobApplication
                                                        .technologies
                                                }
                                            >
                                                {project.technologies
                                                    .slice(0, 6)
                                                    .map((technology) => (
                                                        <li key={technology.id}>
                                                            {technology.name}
                                                        </li>
                                                    ))}
                                            </ul>
                                        )}
                                        <Link
                                            href={path(
                                                `projects/${project.slug}`,
                                            )}
                                            className="pub-application__secondary"
                                        >
                                            {t.jobApplication.readProject}
                                            <span aria-hidden="true">→</span>
                                        </Link>
                                    </div>
                                </article>
                            ))}
                        </section>
                    )}

                    {testimonials.length > 0 && (
                        <section
                            className="pub-application__quotes"
                            aria-labelledby="pub-application-quotes"
                        >
                            <h2
                                id="pub-application-quotes"
                                className="pub-application__section-title"
                            >
                                {t.jobApplication.testimonials}
                            </h2>
                            <div className="pub-application__quote-grid">
                                {testimonials.map((testimonial) => (
                                    <figure key={testimonial.id}>
                                        <blockquote>
                                            {testimonial.highlight ??
                                                testimonial.content}
                                        </blockquote>
                                        <figcaption>
                                            <strong>
                                                {testimonial.author_name}
                                            </strong>
                                            {testimonial.author_role && (
                                                <span>
                                                    {testimonial.author_role}
                                                </span>
                                            )}
                                        </figcaption>
                                    </figure>
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            </PublicShell>
        </>
    );
}
