export type Translatable = {
    fr: string;
    en: string;
};

export type Domain = {
    id: number;
    key: string;
    label: Translatable;
    color: string;
    icon: string;
    sort_order: number;
};

export type Technology = {
    id: number;
    name: string;
    category:
        | 'langages'
        | 'frameworks'
        | 'donnees'
        | 'qualite'
        | 'securite'
        | 'infra'
        | 'ia';
    icon: string | null;
};

export type JobProfile = {
    id: number;
    key: string;
    label: Translatable;
    description: Translatable;
    cv_description: Translatable;
    sort_order: number;
};

export type Skill = {
    id: number;
    domain_id: number;
    domain?: Domain;
    name: Translatable;
    description: Translatable | null;
    sort_order: number;
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
    highlights?: ExperienceHighlight[];
};

export type Project = {
    id: number;
    title: Translatable;
    slug: string;
    context: Translatable;
    realization: Translatable;
    result: Translatable;
    repo_url: string | null;
    demo_url: string | null;
    is_featured: boolean;
    status: 'published' | 'archived';
    sort_order: number;
    domains?: Domain[];
    job_profiles?: JobProfile[];
    technologies?: Technology[];
    cover_url?: string | null;
    gallery_urls?: string[];
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
    submitted_at: string;
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
    photo_url?: string | null;
};
