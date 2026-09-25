export type PublicProfile = {
    name: string;
    headline: string;
    bio_short: string;
    bio_full: string;
    email: string;
    phone: string | null;
    location: string | null;
    social_links: { github?: string; linkedin?: string } | null;
    photo_url: string | null;
};

export type PublicDomain = {
    id: number;
    key: string;
    label: string;
    color: string;
    icon: string;
};

export type PublicSkill = {
    id: number;
    domain: PublicDomain;
    name: string;
    description: string | null;
    details: string | null;
    technologies: PublicTechnology[];
};

export type PublicEducation = {
    id: number;
    institution: string;
    degree: string;
    field: string;
    start_date: string;
    end_date: string | null;
    description: string | null;
};

export type PublicExperience = {
    id: number;
    company: string;
    role: string;
    location: string | null;
    start_date: string;
    end_date: string | null;
    description: string | null;
    highlights: string[];
};

export type PublicTechnology = {
    id: number;
    name: string;
    category: string;
    icon: string | null;
    icon_light_url: string | null;
    icon_dark_url: string | null;
};

export type PublicProject = {
    id: number;
    title: string;
    slug: string;
    context: string;
    realization: string;
    result: string;
    accent_color: string | null;
    repo_url: string | null;
    demo_url: string | null;
    is_featured: boolean;
    is_open_source: boolean;
    domains: PublicDomain[];
    technologies: PublicTechnology[];
    cover_url: string | null;
    gallery_urls: string[];
    related_projects: PublicProject[];
};

export type PublicJobProfile = {
    id: number;
    key: string;
    label: string;
    hero_title: string;
    hero_words: string[];
};

export type PublicTestimonial = {
    id: number;
    author_name: string;
    author_role: string | null;
    content: string;
};
