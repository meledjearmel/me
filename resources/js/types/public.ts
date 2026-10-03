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
    /** Les visiteurs peuvent joindre ou filmer une vidéo avec leur avis. */
    testimonial_video_enabled: boolean;
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
    /** Libellé traduit de la catégorie, dans la langue de la page. */
    category: string;
    icon: string | null;
    icon_light_url: string | null;
    icon_dark_url: string | null;
    description: string | null;
};

export type PublicProject = {
    id: number;
    title: string;
    slug: string;
    context: string;
    realization: string;
    result: string;
    tagline: string | null;
    role: string | null;
    client: string | null;
    platform: string | null;
    key_figures: { value: string; label: string }[];
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

/** Durée (secondes) et dimensions : connues seulement une fois la vidéo traitée. */
export type TestimonialVideo = {
    url: string;
    poster_url: string | null;
    duration: number | null;
    width: number | null;
    height: number | null;
    uploaded_at: string | null;
};

export type PublicTestimonial = {
    id: number;
    author_name: string;
    author_role: string | null;
    content: string;
    /** Phrase d'accroche choisie dans l'admin, affichée en grand sur les cartes. */
    highlight: string | null;
    video_transcript: string | null;
    video: TestimonialVideo | null;
};

export type PlaylistTrack = {
    id: number;
    title: string;
    artist: string | null;
    url: string;
};

export type PlaylistGenre = {
    id: number;
    key: string;
    label: string;
    tracks: PlaylistTrack[];
};

/** Surprise tirée au sort par le serveur (voir ShareSitePublicData). */
export type SharedCelebration = {
    id: number;
    message: string;
    buttonLabel: string;
    total: number;
    /** Probabilité (0 à 1) qu'une session voie la surprise. */
    chance: number;
    delaySeconds: number;
    /** Sans interaction, la bulle repart au bout de ce délai. */
    displaySeconds: number;
    /** Fermée par le visiteur, elle ne lui est plus proposée pendant ce nombre de jours. */
    snoozeDays: number;
};
