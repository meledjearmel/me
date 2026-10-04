import { router, usePage } from "@inertiajs/react";
import { AnimatePresence, motion } from "framer-motion";
import { useMemo, useState } from "react";
import type { CSSProperties } from "react";
import Seo from "@/components/public/seo";
import PageHero from "@/components/public/page-hero";
import ProjectCard from "@/components/public/project-card";
import PublicShell from "@/components/public/public-shell";
import { useLocale, useTranslations } from "@/lib/i18n";
import type { PublicDomain, PublicProject } from "@/types";

/** Comme sur l'accueil : rangées alternées large / étroit, la dernière seule prend toute la largeur. */
const spanFor = (index: number, total: number) => {
    if (index === total - 1 && index % 2 === 0) {
        return "pub-card--full";
    }

    return (index + Math.floor(index / 2)) % 2 === 0
        ? "pub-card--wide"
        : "pub-card--narrow";
};

export default function ProjectsIndex({
    projects,
    domains,
}: {
    projects: PublicProject[];
    domains: PublicDomain[];
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { url } = usePage();
    // Un domaine (son id), les projets open source, ou tous les projets (null).
    // Le filtre vit dans l'URL (?filtre=<clé du domaine>|open-source) pour être partagé et retrouvé.
    const [activeFilter, setActiveFilterState] = useState<
        number | "open-source" | null
    >(() => {
        const filter = new URL(url, "http://localhost").searchParams.get(
            "filtre",
        );

        if (filter === "open-source") {
            return "open-source";
        }

        return domains.find((domain) => domain.key === filter)?.id ?? null;
    });

    const setActiveFilter = (filter: number | "open-source" | null) => {
        setActiveFilterState(filter);

        const key =
            filter === "open-source"
                ? filter
                : domains.find((domain) => domain.id === filter)?.key;
        const nextUrl = new URL(window.location.href);

        if (key) {
            nextUrl.searchParams.set("filtre", key);
        } else {
            nextUrl.searchParams.delete("filtre");
        }

        router.replace({
            url: nextUrl.pathname + nextUrl.search,
            preserveScroll: true,
            preserveState: true,
        });
    };

    const usedDomains = useMemo(
        () =>
            domains
                .map((domain) => ({
                    domain,
                    count: projects.filter((project) =>
                        project.domains.some((d) => d.id === domain.id),
                    ).length,
                }))
                .filter((entry) => entry.count > 0),
        [domains, projects],
    );

    const technologyCount = useMemo(
        () =>
            new Set(
                projects.flatMap((project) =>
                    project.technologies.map((technology) => technology.id),
                ),
            ).size,
        [projects],
    );

    const openSourceCount = projects.filter(
        (project) => project.is_open_source,
    ).length;

    const visibleProjects =
        activeFilter === "open-source"
            ? projects.filter((project) => project.is_open_source)
            : activeFilter
              ? projects.filter((project) =>
                    project.domains.some(
                        (domain) => domain.id === activeFilter,
                    ),
                )
              : projects;

    return (
        <>
            <Seo
                title={t.projects.title}
                description={t.seo.projects}
                breadcrumbs={[[t.nav.projects, `/${locale}/projects`]]}
            />

            <PublicShell overHero>
                <PageHero
                    id="pub-projects-title"
                    eyebrow={t.projects.title}
                    title={t.projects.heading}
                    aside={
                        <dl className="pub-stats pub-reveal pub-reveal--late">
                            <div>
                                <dt>{t.projects.countLabel}</dt>
                                <dd>{projects.length}</dd>
                            </div>
                            <div>
                                <dt>{t.projects.techLabel}</dt>
                                <dd>{technologyCount}</dd>
                            </div>
                            <div>
                                <dt>{t.projects.domainsLabel}</dt>
                                <dd>{usedDomains.length}</dd>
                            </div>
                        </dl>
                    }
                >
                    <p>{t.projects.hook}</p>
                </PageHero>

                <section className="pub-projects" aria-label={t.projects.title}>
                    <div className="site-wrap">
                        <div
                            className="pub-filters"
                            role="group"
                            aria-label={t.projects.filterLabel}
                        >
                            <button
                                type="button"
                                className="pub-filter"
                                onClick={() => setActiveFilter(null)}
                                aria-pressed={activeFilter === null}
                            >
                                {t.projects.all}
                                <span>{projects.length}</span>
                            </button>

                            {usedDomains.map(({ domain, count }) => (
                                <button
                                    key={domain.id}
                                    type="button"
                                    className="pub-filter"
                                    style={
                                        {
                                            "--domain": domain.color,
                                        } as CSSProperties
                                    }
                                    onClick={() => setActiveFilter(domain.id)}
                                    aria-pressed={activeFilter === domain.id}
                                >
                                    <i aria-hidden="true" />
                                    {domain.label}
                                    <span>{count}</span>
                                </button>
                            ))}

                            {openSourceCount > 0 && (
                                <button
                                    type="button"
                                    className="pub-filter"
                                    onClick={() =>
                                        setActiveFilter("open-source")
                                    }
                                    aria-pressed={
                                        activeFilter === "open-source"
                                    }
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="6" cy="6" r="2.4" />
                                        <circle cx="6" cy="18" r="2.4" />
                                        <circle cx="18" cy="9" r="2.4" />
                                        <path d="M6 8.4v7.2M18 11.4c0 3.6-6 2.4-12 4.2" />
                                    </svg>
                                    {t.projects.openSource}
                                    <span>{openSourceCount}</span>
                                </button>
                            )}
                        </div>

                        <h2 className="sr-only">{t.nav.projects}</h2>
                        <div className="pub-featured__grid pub-projects__grid">
                            <AnimatePresence mode="popLayout">
                                {visibleProjects.map((project, index) => (
                                    <motion.div
                                        key={project.id}
                                        layout
                                        className={spanFor(
                                            index,
                                            visibleProjects.length,
                                        )}
                                        initial={{ opacity: 0, scale: 0.94 }}
                                        animate={{ opacity: 1, scale: 1 }}
                                        exit={{ opacity: 0, scale: 0.94 }}
                                        transition={{
                                            duration: 0.45,
                                            ease: [0.22, 1, 0.36, 1],
                                        }}
                                    >
                                        <ProjectCard
                                            project={project}
                                            index={projects.indexOf(project)}
                                            bare
                                            compact
                                        />
                                    </motion.div>
                                ))}
                            </AnimatePresence>
                        </div>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
