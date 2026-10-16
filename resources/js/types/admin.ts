export type Translatable = {
    fr: string;
    en: string;
};

export type PublicationStatus = "draft" | "published";

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
    source: "about" | "surprise";
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
    cv_files?: Record<"fr" | "en", { file_name: string; url: string } | null>;
    sort_order: number;
    status: PublicationStatus;
    projects?: Project[];
};

export type Skill = {
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
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
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
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
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
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

export type ProjectDecision = {
    choice: Translatable;
    reason: Translatable;
};

export type Project = {
    id: number;
    title: Translatable;
    slug: string;
    context: Translatable;
    challenges: Translatable | null;
    realization: Translatable;
    decisions: ProjectDecision[] | null;
    /** Mois au format AAAA-MM. */
    started_on: string | null;
    ended_on: string | null;
    team_size: number | null;
    result: Translatable;
    tagline: Translatable | null;
    role: Translatable | null;
    client: Translatable | null;
    platform: Translatable | null;
    key_figures: ProjectKeyFigure[] | null;
    accent_color: string | null;
    repo_url: string | null;
    /** « propriétaire/nom », privé compris. */
    github_repository: string | null;
    demo_url: string | null;
    is_featured: boolean;
    is_open_source: boolean;
    status: "draft" | "published" | "archived";
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
    sort_order: number;
    domains?: Domain[];
    job_profiles?: JobProfile[];
    related_projects?: Project[];
    technologies?: Technology[];
    cover_url?: string | null;
    gallery_urls?: string[];
    gallery?: { id: number; url: string }[];
};

export type PostComment = {
    id: number;
    author_name: string;
    author_email: string | null;
    body: string;
    locale: "fr" | "en";
    status: "pending" | "approved" | "rejected";
    post: { id: number; slug: string; title: Translatable } | null;
    created_at: string;
};

export type Testimonial = {
    id: number;
    author_name: string;
    author_email: string;
    author_role: string | null;
    content: Translatable;
    highlight: Partial<Translatable> | null;
    video_transcript: Partial<Translatable> | null;
    project_id: number | null;
    project?: Project;
    experience_id: number | null;
    experience?: Pick<Experience, "id" | "company" | "role"> | null;
    education_id: number | null;
    education?: Pick<Education, "id" | "institution" | "degree"> | null;
    status: "pending" | "approved" | "rejected";
    is_featured: boolean;
    submitted_at: string;
};

export type CvDownload = {
    id: number;
    job_profile_id: number | null;
    job_profile?: { id: number; label: Translatable } | null;
    locale: "fr" | "en";
    source: "uploaded" | "generated";
    email: string | null;
    country_code: string | null;
    country: string | null;
    city: string | null;
    referrer_host: string | null;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    device: "desktop" | "mobile" | "tablet" | null;
    origin?: string;
    created_at: string;
};

export type Certification = {
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
    id: number;
    kind: "certification" | "course";
    name: Translatable;
    issuer: string;
    /** AAAA-MM-JJ. */
    issued_on: string;
    expires_on: string | null;
    credential_id: string | null;
    credential_url: string | null;
    status: "published" | "draft";
    sort_order: number;
    badge_url?: string | null;
};

export type DistinctionContextType = "experience" | "education" | "project";

export type Distinction = {
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
    id: number;
    title: Translatable;
    issuer: string;
    description: Partial<Translatable> | null;
    /** AAAA-MM-JJ. */
    awarded_on: string;
    url: string | null;
    context_type: DistinctionContextType | null;
    context_id: number | null;
    /** Entreprise, établissement ou titre du projet (liste). */
    context_label?: string | null;
    congratulations_count: number;
    status: "published" | "draft";
    sort_order: number;
};

/** Contextes qu'on peut choisir pour une distinction, par type. */
export type DistinctionContextOptions = Record<
    DistinctionContextType,
    { id: number; label: string }[]
>;

export type UsesItem = {
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
    id: number;
    category: "hardware" | "development" | "apps" | "services";
    name: string;
    description: Translatable | null;
    url: string | null;
    status: "published" | "draft";
    sort_order: number;
};

export type Subscriber = {
    id: number;
    email: string;
    locale: "fr" | "en";
    confirmed_at: string | null;
    unsubscribed_at: string | null;
    created_at: string;
};

export type ReviewInvitation = {
    id: number;
    name: string | null;
    email: string | null;
    locale: "fr" | "en";
    url: string;
    status: "pending" | "used" | "expired";
    note: string | null;
    project: Pick<Project, "id" | "title"> | null;
    experience: Pick<Experience, "id" | "role" | "company"> | null;
    education: Pick<Education, "id" | "degree" | "institution"> | null;
    testimonial_id: number | null;
    expires_at: string | null;
    used_at: string | null;
    created_at: string;
};

export type Contact = {
    id: number;
    name: string;
    email: string;
    subject: string | null;
    message: string;
    status: "new" | "read" | "replied";
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
    cv_last_name?: string | null;
    cv_first_name?: string | null;
    photo_url?: string | null;
    cv_photo_url?: string | null;
    music?: { file_name: string; url: string } | null;
};

export type Engagement = {
    id: number;
    type: "freelance" | "hiring";
    name: string;
    email: string;
    company: string | null;
    subject: string | null;
    contract: string | null;
    budget_label: string | null;
    estimate_label: string | null;
    estimate: {
        features: string[];
        currency: EstimatorCurrency;
        price_min: number;
        price_max: number;
    } | null;
    /** Issue : signée (avec le montant signé) ou perdue ; null sans issue. */
    outcome: "won" | "lost" | null;
    outcome_amount: number | null;
    outcome_currency: EstimatorCurrency | null;
    outcome_at: string | null;
    ai_analysis: EngagementAiAnalysis | null;
    ai_analyzed_at: string | null;
    timeline: string | null;
    message: string | null;
    locale: string;
    status: "new" | "handled";
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

export type AppointmentLocation = "video" | "phone" | "whatsapp" | "in_person";

export type AppointmentStatus =
    "pending" | "confirmed" | "declined" | "cancelled";

export type AppointmentType = {
    id: number;
    name: Translatable;
    description: Partial<Translatable> | null;
    duration_minutes: number;
    locations: AppointmentLocation[];
    is_active: boolean;
    sort_order: number;
    appointments_count?: number;
};

export type EstimatorCurrency = "CHF" | "EUR" | "USD" | "XOF";

export type EstimatorBilling = "fixed" | "hourly";

export type EstimatorPeriod = "monthly" | "quarterly" | "semiannual" | "yearly";

export type EstimatorItem = {
    id: number;
    kind: "project_type" | "feature";
    name: Translatable;
    description: Partial<Translatable> | null;
    hours_min: number;
    hours_max: number;
    pricing: "hours" | "percent" | "recurring";
    percent: number | null;
    /** Options récurrentes : périodes proposées et leur remise en %. */
    periods: Partial<Record<EstimatorPeriod, number>> | null;
    billing: EstimatorBilling | null;
    /** Grille tarifaire suivie (aucune : taux propres ou taux global). */
    estimator_rate_id: number | null;
    /** Taux propres, prioritaires sur la grille et le taux global. */
    hourly_rates: Partial<Record<EstimatorCurrency, number>> | null;
    /** Grille tarifaire (liste). */
    rate?: { id: number; name: Translatable } | null;
    is_active: boolean;
    sort_order: number;
    /** Options : types de projet visés (édition). */
    project_type_ids?: number[];
    /** Options : types de projet visés (liste). */
    project_types?: { id: number; name: Translatable }[];
};

export type EstimatorRate = {
    id: number;
    name: Translatable;
    hourly_rates: Partial<Record<EstimatorCurrency, number>>;
    sort_order: number;
    items_count?: number;
};

/** Grille tarifaire proposée dans le formulaire d'un élément. */
export type EstimatorRateOption = {
    id: number;
    label: string;
    rates: Partial<Record<EstimatorCurrency, number>>;
};

export type CommercialDocumentType = "quote" | "proforma" | "invoice";

export type CommercialDocumentStatus =
    | "draft"
    | "issued"
    | "accepted"
    | "declined"
    | "paid"
    | "cancelled";

export type CommercialDocumentLine = {
    label: string;
    description: string | null;
    quantity: number;
    unit: "hour" | "day" | "flat" | "month" | "quarter" | "semester" | "year";
    unit_price: number;
};

export type CommercialDocument = {
    id: number;
    type: CommercialDocumentType;
    status: CommercialDocumentStatus;
    number: string | null;
    engagement_id: number | null;
    source_id: number | null;
    locale: "fr" | "en";
    client_name: string;
    client_email: string | null;
    client_company: string | null;
    client_address: string | null;
    title: string;
    currency: EstimatorCurrency;
    lines: CommercialDocumentLine[];
    total: number;
    deposit_percent: number;
    payment_terms: string | null;
    notes: string | null;
    issued_at: string | null;
    valid_until: string | null;
    due_at: string | null;
    sent_at: string | null;
    accepted_at: string | null;
    declined_at: string | null;
    paid_at: string | null;
    reminders_count: number;
    last_reminded_at: string | null;
    /** Relance suggérée (devis sans réponse, facture en retard). */
    reminder_due_at: string | null;
    created_at: string;
    /** Fiche : acompte, lien du client (devis), demande et document d'origine. */
    deposit_amount?: number;
    public_url?: string | null;
    engagement?: { id: number; name: string; subject: string | null } | null;
    source?: { id: number; number: string | null; type: CommercialDocumentType } | null;
};

export type BillingSettings = {
    billing_business_name: string | null;
    billing_legal_form: string | null;
    billing_address: string | null;
    billing_registration_number: string | null;
    billing_iban: string | null;
    billing_bank: string | null;
    billing_vat_mention: string | null;
    billing_payment_terms: string | null;
    billing_quote_validity_days: number;
    billing_invoice_due_days: number;
    billing_deposit_percent: number;
    billing_quote_reminder_days: number;
    billing_invoice_reminder_days: number;
    billing_max_reminders: number;
};

export type Service = {
    id: number;
    slug: string;
    title: Translatable;
    tagline: Partial<Translatable> | null;
    description: Partial<Translatable> | null;
    /** Ce qui est compris, une ligne par élément. */
    includes: Partial<Translatable> | null;
    price_mode: "estimator" | "manual" | "quote";
    estimator_item_id: number | null;
    manual_prices: Partial<Record<EstimatorCurrency, number>> | null;
    status: PublicationStatus;
    drafted_by: string | null;
    sort_order: number;
    projects_count?: number;
    project_ids?: number[];
};

export type JobApplicationStatus = "draft" | "active" | "archived";

export type JobApplication = {
    id: number;
    token: string;
    company: string;
    contact_name: string | null;
    position: string;
    job_profile_id: number | null;
    cv_tailoring: {
        title: string | null;
        summary: string | null;
        experiences: Record<string, number[]>;
        skill_ids: number[];
        reasoning: string | null;
    } | null;
    cv_tailored_at: string | null;
    locale: "fr" | "en";
    intro: string;
    job_posting_url: string | null;
    job_posting_text: string | null;
    notes: string | null;
    status: JobApplicationStatus;
    drafted_by: string | null;
    expires_on: string | null;
    views_count: number;
    cv_downloads_count: number;
    first_viewed_at: string | null;
    last_viewed_at: string | null;
    created_at: string;
    project_ids?: number[];
};

export type JobApplicationEvent = {
    id: number;
    type: "view" | "cv_download";
    country_code: string | null;
    country: string | null;
    city: string | null;
    device: string | null;
    referrer_host: string | null;
    created_at: string;
};

export type EstimatorProposalItem = {
    ref?: string;
    id?: number;
    kind: "project_type" | "feature";
    name_fr: string;
    name_en: string;
    description_fr?: string;
    description_en?: string;
    hours_min?: number;
    hours_max?: number;
    pricing?: "hours" | "percent" | "recurring";
    percent?: number;
    periods?: Partial<Record<EstimatorPeriod, number>>;
    billing?: EstimatorBilling;
    project_types?: (string | number)[];
};

export type EstimatorProposal = {
    settings: {
        hourly_rates?: Partial<Record<EstimatorCurrency, number>>;
        risk_percent?: number;
        hours_per_week?: number;
    } | null;
    items: EstimatorProposalItem[];
    reasoning: string | null;
};

export type EstimatorSettings = {
    estimator_hourly_rates: Record<EstimatorCurrency, number | null>;
    estimator_risk_percent: number;
    estimator_hours_per_week: number;
};

export type EngagementAiAnalysis = {
    summary: string;
    risks: string[];
    unclear_points: string[];
    questions: string[];
    hours_min: number;
    hours_max: number;
    billing: EstimatorBilling;
    reasoning: string | null;
    price_min: number | null;
    price_max: number | null;
    currency: string | null;
};

export type Appointment = {
    id: number;
    appointment_type_id: number | null;
    appointment_type?: AppointmentType | null;
    name: string;
    email: string;
    phone: string | null;
    company: string | null;
    location: AppointmentLocation;
    message: string | null;
    starts_at: string;
    ends_at: string;
    timezone: string | null;
    locale: string;
    status: AppointmentStatus;
    meeting_details: string | null;
    decline_reason: string | null;
    confirmed_at: string | null;
    cancelled_at: string | null;
    created_at: string;
};

export type SiteSettings = {
    contact_opens_drawer: boolean;
    availability_status: "available" | "from" | "unavailable";
    /** AAAA-MM-JJ. */
    available_from: string | null;
    testimonial_video_enabled: boolean;
    blog_enabled: boolean;
    blog_reactions_enabled: boolean;
    blog_comments_enabled: boolean;
    cv_job_profile_id: number | null;
    cv_source: "uploaded" | "generated";
    congratulation_notify_minutes: number;
    booking_enabled: boolean;
    booking_min_notice_hours: number;
    booking_horizon_days: number;
    booking_buffer_minutes: number;
    booking_video_provider: "jitsi" | "link";
    booking_video_link: string | null;
};

export type AvailabilityRule = {
    id: number;
    days: string[];
    start: string;
    end: string;
};

export type BlockedPeriod = {
    id: number;
    label: string | null;
    from: string;
    to: string;
};

export type Post = {
    id: number;
    title: Partial<Translatable>;
    slug: string;
    excerpt: Partial<Translatable> | null;
    body: Partial<Translatable>;
    reading_minutes: number;
    is_featured: boolean;
    status: "draft" | "published";
    /** Client IA qui a rédigé le brouillon (serveur MCP). */
    drafted_by?: string | null;
    published_at: string | null;
    created_at: string;
    updated_at: string;
    tags: string[] | { id: number; name: Translatable; slug: string }[];
    /** Nom français de la série, `null` hors série. */
    series?: string | null;
    series_position?: number | null;
    cover_url?: string | null;
    /** Lectures sur le site : une par session toutes les 30 minutes, robots exclus. */
    views_count?: number;
};

export type PostSeries = {
    id: number;
    slug: string;
    name: Translatable;
    posts_count: number;
};

export type PostTag = {
    id: number;
    slug: string;
    name: Translatable;
    posts_count: number;
};

/** Révision d'un contenu publié proposée par l'IA (serveur MCP). */
export type ContentRevisionSummary = {
    id: number;
    content_type: string;
    content_type_label: string;
    content_id: number | null;
    title: string;
    edit_url: string | null;
    summary: string;
    drafted_by: string | null;
    status: "pending" | "applied" | "rejected";
    created_at: string | null;
    decided_at: string | null;
};

export type ContentRevisionDetail = ContentRevisionSummary & {
    /** Chaque champ modifié : valeur à la proposition, proposée et actuelle. */
    fields: {
        field: string;
        locale: "fr" | "en";
        before: unknown;
        after: unknown;
        current: unknown;
    }[];
};
