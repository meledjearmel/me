export type Translatable = {
    fr: string;
    en: string;
};

export type PublicationStatus = 'draft' | 'published';

export type Domain = {
    id: number;
    key: string;
    label: Translatable;
    color: string;
    icon: string;
    sort_order: number;
    status: PublicationStatus;
    skills?: Skill[];
    projects_count?: number;
};

export type MusicGenre = {
    id: number;
    key: string;
    label: Translatable;
    sort_order: number;
    tracks_count?: number;
};

export type Track = {
    id: number;
    music_genre_id: number;
    title: string;
    artist: string | null;
    sort_order: number;
    audio_url?: string | null;
    genre?: MusicGenre;
};

/** Surprise du site public : un personnage annonce une bonne nouvelle à féliciter. */
export type Celebration = {
    id: number;
    message: Translatable;
    button_label: Translatable;
    congratulated_for: string;
    is_active: boolean;
    starts_at: string | null;
    ends_at: string | null;
    weight: number;
    chance_percent: number;
    delay_seconds: number;
    display_seconds: number;
    snooze_days: number;
    congratulations_count: number;
    created_at: string;
};

/** Un envoi de félicitations d'un visiteur, avec son motif. */
export type Congratulation = {
    id: number;
    source: 'about' | 'surprise';
    celebration_id: number | null;
    reason: string;
    count: number;
    locale: string | null;
    created_at: string;
};

export type TechnologyCategory = {
    id: number;
    key: string;
    label: Translatable;
    sort_order: number;
    technologies_count?: number;
    technologies?: Technology[];
};

export type Technology = {
    id: number;
    name: string;
    category_id: number;
    category?: TechnologyCategory;
    icon: string | null;
    description: Translatable | null;
    projects?: Project[];
};

/** Logo de la bibliothèque `public/icons/tech` (variantes de thème regroupées). */
export type TechnologyIconOption = {
    slug: string;
    light_url: string | null;
    dark_url: string | null;
};

export type JobProfile = {
    id: number;
    key: string;
    label: Translatable;
    description: Translatable;
    hero_title: Translatable | null;
    hero_words: Translatable | null;
    cv_description: Translatable;
    cv_files?: Record<'fr' | 'en', { file_name: string; url: string } | null>;
    sort_order: number;
    status: PublicationStatus;
    projects?: Project[];
};

export type Skill = {
    id: number;
    domain_id: number;
    domain?: Domain;
    name: Translatable;
    description: Translatable | null;
    details: Translatable | null;
    technologies?: Technology[];
    sort_order: number;
    status: PublicationStatus;
};

export type Education = {
    id: number;
    institution: string;
    degree: Translatable;
    field: Translatable;
    start_date: string;
    end_date: string | null;
    description: Translatable | null;
    sort_order: number;
    status: PublicationStatus;
};

export type ExperienceHighlight = {
    id: number;
    experience_id: number;
    text: Translatable;
    sort_order: number;
};

export type Experience = {
    id: number;
    company: string;
    role: Translatable;
    location: string | null;
    start_date: string;
    end_date: string | null;
    description: Translatable | null;
    sort_order: number;
    status: PublicationStatus;
    highlights?: ExperienceHighlight[];
};

export type ProjectKeyFigure = {
    value: string;
    label: Translatable;
};

export type Project = {
    id: number;
    title: Translatable;
    slug: string;
    context: Translatable;
    realization: Translatable;
    result: Translatable;
    tagline: Translatable | null;
    role: Translatable | null;
    client: Translatable | null;
    platform: Translatable | null;
    key_figures: ProjectKeyFigure[] | null;
    accent_color: string | null;
    repo_url: string | null;
    demo_url: string | null;
    is_featured: boolean;
    is_open_source: boolean;
    status: 'published' | 'archived';
    sort_order: number;
    domains?: Domain[];
    job_profiles?: JobProfile[];
    related_projects?: Project[];
    technologies?: Technology[];
    cover_url?: string | null;
    gallery_urls?: string[];
    gallery?: { id: number; url: string }[];
};

export type Testimonial = {
    id: number;
    author_name: string;
    author_email: string;
    author_role: string | null;
    content: Translatable;
    project_id: number | null;
    project?: Project;
    status: 'pending' | 'approved' | 'rejected';
    is_featured: boolean;
    submitted_at: string;
};

export type CvDownload = {
    id: number;
    job_profile_id: number | null;
    job_profile?: { id: number; label: Translatable } | null;
    locale: 'fr' | 'en';
    source: 'uploaded' | 'generated';
    email: string | null;
    country_code: string | null;
    country: string | null;
    city: string | null;
    referrer_host: string | null;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    device: 'desktop' | 'mobile' | 'tablet' | null;
    origin?: string;
    created_at: string;
};

export type Contact = {
    id: number;
    name: string;
    email: string;
    subject: string | null;
    message: string;
    status: 'new' | 'read' | 'replied';
    created_at: string;
};

export type ProfessionalReference = {
    id: number;
    name: string;
    role: string | null;
    company: string | null;
    email: string | null;
    phone: string | null;
    relationship: string | null;
    project_id: number | null;
    project?: Project;
    is_public: boolean;
    visible_fields: string[];
    notes: string | null;
};

export type Profile = {
    id: number;
    name: string;
    headline: Translatable;
    bio_short: Translatable;
    bio_full: Translatable;
    email: string;
    phone: string | null;
    location: string | null;
    social_links: { github?: string; linkedin?: string } | null;
    congratulation_notify_minutes: number;
    cv_job_profile_id: number | null;
    cv_source: 'uploaded' | 'generated';
    cv_last_name?: string | null;
    cv_first_name?: string | null;
    photo_url?: string | null;
    cv_photo_url?: string | null;
    music?: { file_name: string; url: string } | null;
};

export type Engagement = {
    id: number;
    type: 'freelance' | 'hiring';
    name: string;
    email: string;
    company: string | null;
    subject: string | null;
    contract: string | null;
    budget_label: string | null;
    timeline: string | null;
    message: string | null;
    locale: string;
    status: 'new' | 'handled';
    cv_sent_at: string | null;
    created_at: string;
    job_profile?: { id: number; label: Translatable } | null;
};

export type TrashItem = {
    id: number;
    type: string;
    label: string;
    title: string;
    deleted_at: string;
};
