export const TECHNOLOGY_CATEGORIES = [
    { value: 'langages', label: 'Langages' },
    { value: 'frameworks', label: 'Frameworks' },
    { value: 'donnees', label: 'Données' },
    { value: 'qualite', label: 'Qualité' },
    { value: 'securite', label: 'Sécurité' },
    { value: 'infra', label: 'Infra' },
    { value: 'ia', label: 'IA' },
] as const;

export const PROJECT_STATUSES = [
    { value: 'published', label: 'Publié' },
    { value: 'archived', label: 'Archivé' },
] as const;

export const TESTIMONIAL_STATUSES = [
    { value: 'pending', label: 'En attente' },
    { value: 'approved', label: 'Approuvé' },
    { value: 'rejected', label: 'Rejeté' },
] as const;

export const CONTACT_STATUSES = [
    { value: 'new', label: 'Nouveau' },
    { value: 'read', label: 'Lu' },
    { value: 'replied', label: 'Répondu' },
] as const;

export const REFERENCE_VISIBLE_FIELDS = [
    { value: 'name', label: 'Nom' },
    { value: 'role', label: 'Rôle' },
    { value: 'company', label: 'Société' },
    { value: 'email', label: 'Email' },
    { value: 'phone', label: 'Téléphone' },
    { value: 'relationship', label: 'Relation' },
] as const;
