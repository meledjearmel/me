import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import FormSelect from '@/components/admin/form-select';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { index as availabilityIndex } from '@/routes/admin/availability';
import { edit as pageEdit } from '@/routes/admin/site-settings';
import type { JobProfile, SiteSettings } from '@/types';

const TABS = [
    { value: 'site', label: 'Site' },
    { value: 'reviews', label: 'Avis' },
    { value: 'blog', label: 'Blog' },
    { value: 'cv', label: 'CV' },
    { value: 'notifications', label: 'Notifications' },
    { value: 'booking', label: 'Rendez-vous' },
] as const;

type Tab = (typeof TABS)[number]['value'];

/** L'onglet de chaque réglage : en cas d'erreur, la page bascule sur le premier concerné. */
const FIELD_TAB: Record<string, Tab> = {
    contact_opens_drawer: 'site',
    availability_status: 'site',
    available_from: 'site',
    testimonial_video_enabled: 'reviews',
    blog_enabled: 'blog',
    blog_reactions_enabled: 'blog',
    blog_comments_enabled: 'blog',
    cv_job_profile_id: 'cv',
    cv_source: 'cv',
    congratulation_notify_minutes: 'notifications',
    booking_enabled: 'booking',
    booking_min_notice_hours: 'booking',
    booking_horizon_days: 'booking',
    booking_buffer_minutes: 'booking',
    booking_video_provider: 'booking',
    booking_video_link: 'booking',
};

/**
 * Le contenu d'un onglet. Il reste monté (forceMount) même masqué : ses champs
 * partent avec le formulaire, quel que soit l'onglet affiché à l'enregistrement.
 */
function Section({
    value,
    description,
    children,
}: {
    value: Tab;
    description?: ReactNode;
    children: ReactNode;
}) {
    return (
        <TabsContent
            value={value}
            forceMount
            className="grid gap-4 rounded-lg border p-4 data-[state=inactive]:hidden"
        >
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
            {children}
        </TabsContent>
    );
}

/**
 * Ma disponibilité, affichée dans le badge de l'en-tête et la fenêtre de contact.
 * La date n'apparaît que pour « à partir du… ».
 */
function AvailabilityFields({
    settings,
    errors,
}: {
    settings: SiteSettings;
    errors: Record<string, string>;
}) {
    const [status, setStatus] = useState(settings.availability_status);

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <Field data-invalid={!!errors.availability_status}>
                <FieldLabel htmlFor="availability_status">
                    Ma disponibilité
                </FieldLabel>
                <FormSelect
                    id="availability_status"
                    name="availability_status"
                    defaultValue={status}
                    onValueChange={(value) =>
                        setStatus(value as SiteSettings['availability_status'])
                    }
                >
                    <option value="available">Disponible</option>
                    <option value="from">Disponible à partir d’une date</option>
                    <option value="unavailable">Indisponible</option>
                </FormSelect>
                <FieldDescription>
                    Badge de l’en-tête et mention de la fenêtre de contact. Une
                    date passée affiche « Disponible ».
                </FieldDescription>
                <FieldError>{errors.availability_status}</FieldError>
            </Field>

            {status === 'from' && (
                <Field data-invalid={!!errors.available_from}>
                    <FieldLabel htmlFor="available_from">
                        Disponible à partir du
                    </FieldLabel>
                    <Input
                        id="available_from"
                        name="available_from"
                        type="date"
                        required
                        defaultValue={settings.available_from ?? ''}
                    />
                    <FieldError>{errors.available_from}</FieldError>
                </Field>
            )}
        </div>
    );
}

/** Case à cocher envoyée même décochée (valeur 0). */
function Toggle({
    name,
    label,
    defaultChecked,
}: {
    name: string;
    label: string;
    defaultChecked: boolean;
}) {
    return (
        <Field orientation="horizontal">
            <input type="hidden" name={name} value="0" />
            <Checkbox
                id={name}
                name={name}
                value="1"
                defaultChecked={defaultChecked}
            />
            <FieldLabel htmlFor={name}>{label}</FieldLabel>
        </Field>
    );
}

export default function SiteSettingsEdit({
    settings,
    jobProfiles,
}: {
    settings: SiteSettings;
    jobProfiles: Pick<JobProfile, 'id' | 'label'>[];
}) {
    const [tab, setTab] = useState<Tab>('site');

    return (
        <>
            <Head title="Réglages du site" />

            <div className="flex max-w-3xl flex-col gap-6 p-4">
                <Heading
                    title="Réglages du site"
                    description="Comment le site fonctionne pour les visiteurs"
                />

                <Form
                    {...SiteSettingController.update.form()}
                    options={{ preserveScroll: true }}
                    onError={(errors) => {
                        const first = Object.keys(errors)
                            .map((field) => FIELD_TAB[field])
                            .find(Boolean);

                        if (first) {
                            setTab(first);
                        }
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Tabs
                                value={tab}
                                onValueChange={(value) => setTab(value as Tab)}
                            >
                                <TabsList>
                                    {TABS.map((item) => (
                                        <TabsTrigger
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </TabsTrigger>
                                    ))}
                                </TabsList>

                                <Section value="site">
                                    <Field
                                        data-invalid={
                                            !!errors.contact_opens_drawer
                                        }
                                    >
                                        <FieldLabel htmlFor="contact_opens_drawer">
                                            Bouton « Contact » du menu
                                        </FieldLabel>
                                        <FormSelect
                                            id="contact_opens_drawer"
                                            name="contact_opens_drawer"
                                            defaultValue={
                                                settings.contact_opens_drawer
                                                    ? '1'
                                                    : '0'
                                            }
                                        >
                                            <option value="1">
                                                Ouvre un tiroir latéral avec le
                                                formulaire, sans quitter la page
                                            </option>
                                            <option value="0">
                                                Mène à la page Contact
                                            </option>
                                        </FormSelect>
                                        <FieldDescription>
                                            S'applique aussi au grand titre du
                                            pied de page.
                                        </FieldDescription>
                                        <FieldError>
                                            {errors.contact_opens_drawer}
                                        </FieldError>
                                    </Field>

                                    <AvailabilityFields
                                        settings={settings}
                                        errors={errors}
                                    />
                                </Section>

                                <Section value="reviews">
                                    <Toggle
                                        name="testimonial_video_enabled"
                                        label="Autoriser les avis vidéo"
                                        defaultChecked={
                                            settings.testimonial_video_enabled
                                        }
                                    />
                                    <FieldDescription>
                                        Les visiteurs peuvent joindre une vidéo
                                        à leur avis ou se filmer depuis la page.
                                        Désactivé, le formulaire ne propose plus
                                        que le texte. Les vidéos déjà reçues
                                        restent affichées.
                                    </FieldDescription>
                                    <FieldError>
                                        {errors.testimonial_video_enabled}
                                    </FieldError>
                                </Section>

                                <Section value="blog">
                                    <Toggle
                                        name="blog_enabled"
                                        label="Afficher le blog sur le site"
                                        defaultChecked={settings.blog_enabled}
                                    />
                                    <FieldDescription>
                                        Désactivé, les pages du blog renvoient
                                        une erreur 404 et le lien disparaît de
                                        la navigation et du plan du site. Les
                                        articles restent modifiables ici.
                                    </FieldDescription>
                                    <FieldError>
                                        {errors.blog_enabled}
                                    </FieldError>

                                    <Toggle
                                        name="blog_reactions_enabled"
                                        label="Réactions sous les articles"
                                        defaultChecked={
                                            settings.blog_reactions_enabled
                                        }
                                    />
                                    <FieldDescription>
                                        Les lecteurs réagissent sans compte
                                        (j’aime, j’adore, impressionnant,
                                        instructif, réflexion), une fois par
                                        réaction.
                                        Désactivé, les boutons disparaissent ;
                                        les réactions déjà données sont
                                        conservées.
                                    </FieldDescription>
                                    <FieldError>
                                        {errors.blog_reactions_enabled}
                                    </FieldError>

                                    <Toggle
                                        name="blog_comments_enabled"
                                        label="Commentaires sous les articles"
                                        defaultChecked={
                                            settings.blog_comments_enabled
                                        }
                                    />
                                    <FieldDescription>
                                        Un commentaire n’apparaît qu’une fois
                                        publié depuis « Commentaires ».
                                        Désactivé, le formulaire et les
                                        commentaires sont masqués.
                                    </FieldDescription>
                                    <FieldError>
                                        {errors.blog_comments_enabled}
                                    </FieldError>
                                </Section>

                                <Section value="cv">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field
                                            data-invalid={
                                                !!errors.cv_job_profile_id
                                            }
                                        >
                                            <FieldLabel htmlFor="cv_job_profile_id">
                                                Profil métier principal
                                            </FieldLabel>
                                            <FormSelect
                                                id="cv_job_profile_id"
                                                name="cv_job_profile_id"
                                                defaultValue={
                                                    settings.cv_job_profile_id ??
                                                    ''
                                                }
                                            >
                                                <option value="">
                                                    Le premier profil publié
                                                </option>
                                                {jobProfiles.map(
                                                    (jobProfile) => (
                                                        <option
                                                            key={jobProfile.id}
                                                            value={
                                                                jobProfile.id
                                                            }
                                                        >
                                                            {
                                                                jobProfile.label
                                                                    .fr
                                                            }
                                                        </option>
                                                    ),
                                                )}
                                            </FormSelect>
                                            <FieldDescription>
                                                Son CV est proposé au
                                                téléchargement, dans la langue
                                                de la page.
                                            </FieldDescription>
                                            <FieldError>
                                                {errors.cv_job_profile_id}
                                            </FieldError>
                                        </Field>

                                        <Field
                                            data-invalid={!!errors.cv_source}
                                        >
                                            <FieldLabel htmlFor="cv_source">
                                                Source prioritaire
                                            </FieldLabel>
                                            <FormSelect
                                                id="cv_source"
                                                name="cv_source"
                                                defaultValue={
                                                    settings.cv_source
                                                }
                                            >
                                                <option value="uploaded">
                                                    CV importé (PDF du profil
                                                    métier)
                                                </option>
                                                <option value="generated">
                                                    CV généré depuis le site
                                                </option>
                                            </FormSelect>
                                            <FieldDescription>
                                                Sans CV importé, le CV généré
                                                prend le relais. S'applique
                                                aussi au CV envoyé aux
                                                recruteurs.
                                            </FieldDescription>
                                            <FieldError>
                                                {errors.cv_source}
                                            </FieldError>
                                        </Field>
                                    </div>
                                </Section>

                                <Section value="notifications">
                                    <Field
                                        data-invalid={
                                            !!errors.congratulation_notify_minutes
                                        }
                                    >
                                        <FieldLabel htmlFor="congratulation_notify_minutes">
                                            Notification de félicitations et
                                            de réactions au plus toutes les
                                            (minutes)
                                        </FieldLabel>
                                        <Input
                                            id="congratulation_notify_minutes"
                                            name="congratulation_notify_minutes"
                                            type="number"
                                            min={0}
                                            max={1440}
                                            defaultValue={
                                                settings.congratulation_notify_minutes
                                            }
                                            required
                                        />
                                        <FieldDescription>
                                            Par motif (chaque surprise, la page
                                            À propos) et par article du blog
                                            pour les réactions. 0 : une
                                            notification à chaque envoi.
                                            L'historique garde toutes les
                                            félicitations. Chaque commentaire
                                            est notifié.
                                        </FieldDescription>
                                        <FieldError>
                                            {
                                                errors.congratulation_notify_minutes
                                            }
                                        </FieldError>
                                    </Field>
                                </Section>

                                <Section
                                    value="booking"
                                    description={
                                        <>
                                            Les plages horaires et les jours
                                            bloqués se gèrent dans{' '}
                                            <Link
                                                href={availabilityIndex()}
                                                className="underline"
                                            >
                                                Disponibilités
                                            </Link>
                                            . Heures d'Abidjan (GMT).
                                        </>
                                    }
                                >
                                    <Toggle
                                        name="booking_enabled"
                                        label="Prise de rendez-vous ouverte"
                                        defaultChecked={
                                            settings.booking_enabled
                                        }
                                    />
                                    <FieldDescription>
                                        Fermée, la page de réservation et ses
                                        raccourcis disparaissent du site.
                                    </FieldDescription>

                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <Field
                                            data-invalid={
                                                !!errors.booking_min_notice_hours
                                            }
                                        >
                                            <FieldLabel htmlFor="booking_min_notice_hours">
                                                Délai minimum (heures)
                                            </FieldLabel>
                                            <Input
                                                id="booking_min_notice_hours"
                                                name="booking_min_notice_hours"
                                                type="number"
                                                min={0}
                                                max={720}
                                                defaultValue={
                                                    settings.booking_min_notice_hours
                                                }
                                            />
                                            <FieldError>
                                                {
                                                    errors.booking_min_notice_hours
                                                }
                                            </FieldError>
                                        </Field>
                                        <Field
                                            data-invalid={
                                                !!errors.booking_horizon_days
                                            }
                                        >
                                            <FieldLabel htmlFor="booking_horizon_days">
                                                Réservable jusqu'à (jours)
                                            </FieldLabel>
                                            <Input
                                                id="booking_horizon_days"
                                                name="booking_horizon_days"
                                                type="number"
                                                min={1}
                                                max={365}
                                                defaultValue={
                                                    settings.booking_horizon_days
                                                }
                                            />
                                            <FieldError>
                                                {errors.booking_horizon_days}
                                            </FieldError>
                                        </Field>
                                        <Field
                                            data-invalid={
                                                !!errors.booking_buffer_minutes
                                            }
                                        >
                                            <FieldLabel htmlFor="booking_buffer_minutes">
                                                Pause entre deux RDV (min)
                                            </FieldLabel>
                                            <Input
                                                id="booking_buffer_minutes"
                                                name="booking_buffer_minutes"
                                                type="number"
                                                min={0}
                                                max={240}
                                                step={5}
                                                defaultValue={
                                                    settings.booking_buffer_minutes
                                                }
                                            />
                                            <FieldError>
                                                {errors.booking_buffer_minutes}
                                            </FieldError>
                                        </Field>
                                    </div>

                                    <Field
                                        data-invalid={
                                            !!errors.booking_video_provider
                                        }
                                    >
                                        <FieldLabel htmlFor="booking_video_provider">
                                            Visio
                                        </FieldLabel>
                                        <FormSelect
                                            id="booking_video_provider"
                                            name="booking_video_provider"
                                            defaultValue={
                                                settings.booking_video_provider
                                            }
                                        >
                                            <option value="jitsi">
                                                Un lien Jitsi unique pour chaque
                                                rendez-vous (gratuit)
                                            </option>
                                            <option value="link">
                                                Mon lien fixe (ci-dessous)
                                            </option>
                                        </FormSelect>
                                        <FieldDescription>
                                            Jitsi : le lien est créé à la
                                            confirmation et envoyé au visiteur,
                                            qui rejoint sans compte. Pour lancer
                                            la réunion, connectez-vous à Jitsi
                                            (Google, GitHub ou Facebook).
                                        </FieldDescription>
                                        <FieldError>
                                            {errors.booking_video_provider}
                                        </FieldError>
                                    </Field>

                                    <Field
                                        data-invalid={
                                            !!errors.booking_video_link
                                        }
                                    >
                                        <FieldLabel htmlFor="booking_video_link">
                                            Lien visio fixe
                                        </FieldLabel>
                                        <Input
                                            id="booking_video_link"
                                            name="booking_video_link"
                                            type="url"
                                            placeholder="https://meet.google.com/…"
                                            defaultValue={
                                                settings.booking_video_link ??
                                                ''
                                            }
                                        />
                                        <FieldDescription>
                                            Avec « Mon lien fixe » : repris à la
                                            confirmation d'une visio si vous
                                            n'en précisez pas d'autre.
                                        </FieldDescription>
                                        <FieldError>
                                            {errors.booking_video_link}
                                        </FieldError>
                                    </Field>
                                </Section>
                            </Tabs>

                            <Button disabled={processing} className="w-fit">
                                Enregistrer
                            </Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

SiteSettingsEdit.layout = {
    breadcrumbs: [{ title: 'Réglages du site', href: pageEdit() }],
};
