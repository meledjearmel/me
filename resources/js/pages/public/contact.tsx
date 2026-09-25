import { Form, Head, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useState } from 'react';
import ContactController from '@/actions/App/Http/Controllers/ContactController';
import PageHero from '@/components/public/page-hero';
import PhoneLink from '@/components/public/phone-link';
import PublicShell from '@/components/public/public-shell';
import { useLocale, useTranslations } from '@/lib/i18n';
import type { PublicProfile } from '@/types';

/** Panneau de coordonnées dans le bandeau : email, lieu, délai de réponse, réseaux. */
function ContactMeta({ profile }: { profile: PublicProfile }) {
    const t = useTranslations();
    const socials = [
        { label: 'GitHub', href: profile.social_links?.github },
        { label: 'LinkedIn', href: profile.social_links?.linkedin },
    ].filter((social): social is { label: string; href: string } => Boolean(social.href));

    return (
        <motion.dl
            className="pub-meta"
            initial={{ opacity: 0, y: 24 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.3, ease: [0.22, 1, 0.36, 1] }}
        >
            <div>
                <dt>{t.contact.emailLabel}</dt>
                <dd className="pub-meta__links">
                    <a href={`mailto:${profile.email}`}>{profile.email}</a>
                </dd>
            </div>

            {profile.phone && (
                <div>
                    <dt>{t.contact.phoneLabel}</dt>
                    <dd className="pub-meta__links">
                        <PhoneLink phone={profile.phone} />
                    </dd>
                </div>
            )}

            {profile.location && (
                <div>
                    <dt>{t.contact.locationLabel}</dt>
                    <dd>{profile.location}</dd>
                </div>
            )}

            <div>
                <dt>{t.contact.replyLabel}</dt>
                <dd>{t.contact.replyValue}</dd>
            </div>

            {socials.length > 0 && (
                <div>
                    <dt>{t.contactDrawer.socials}</dt>
                    <dd className="pub-meta__links">
                        {socials.map((social) => (
                            <a key={social.label} href={social.href} target="_blank" rel="noreferrer">
                                {social.label} ↗
                            </a>
                        ))}
                    </dd>
                </div>
            )}
        </motion.dl>
    );
}

export default function Contact() {
    const t = useTranslations();
    const locale = useLocale();
    const { props } = usePage<{ profile: PublicProfile }>();
    const [subject, setSubject] = useState('');
    // Changer la clé remonte le formulaire : il repart vide après « envoyer un autre message ».
    const [formKey, setFormKey] = useState(0);
    const [sent, setSent] = useState(false);

    const topics = [
        t.contact.topic1,
        t.contact.topic2,
        t.contact.topic3,
        t.contact.topic4,
        t.contact.topic5,
    ];

    return (
        <>
            <Head title={t.contact.title}>
                <meta name="description" content={t.contactDrawer.intro} />
            </Head>

            <PublicShell overHero>
                <PageHero
                    id="pub-contact-title"
                    eyebrow={t.contact.kicker}
                    title={t.contact.heading}
                    aside={<ContactMeta profile={props.profile} />}
                    lead={
                        <>
                            <p>{t.contactDrawer.intro}</p>
                            <p className="pub-contact__avail">
                                <i aria-hidden="true" />
                                {t.contactDrawer.eyebrow}
                            </p>
                        </>
                    }
                />

                <section className="pub-contact" aria-label={t.contact.title}>
                    <div className="site-wrap pub-contact__grid">
                        <div className="pub-contact__card" id="pub-contact-form">
                            <h2 className="pub-contact__title">{t.contact.cardTitle}</h2>

                            {sent ? (
                                <div className="pub-drawer__done" role="status">
                                    <svg className="pub-drawer__check" viewBox="0 0 64 64" aria-hidden="true">
                                        <circle cx="32" cy="32" r="28" />
                                        <path d="M19 33l9 9 17-19" />
                                    </svg>
                                    <h3>{t.contactDrawer.doneTitle}</h3>
                                    <p>{t.contact.success}</p>
                                    <button
                                        type="button"
                                        className="pub-contact__again"
                                        onClick={() => {
                                            setSent(false);
                                            setSubject('');
                                            setFormKey((key) => key + 1);
                                        }}
                                    >
                                        {t.contact.sendAnother}
                                    </button>
                                </div>
                            ) : (
                                <Form
                                    key={formKey}
                                    {...ContactController.store.form(locale)}
                                    className="pub-contact__form"
                                    resetOnSuccess
                                    onSuccess={() => setSent(true)}
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            {/* Piège anti-spam : jamais rempli par un humain. */}
                                            <input
                                                type="text"
                                                name="website"
                                                tabIndex={-1}
                                                autoComplete="off"
                                                hidden
                                                aria-hidden="true"
                                            />

                                            <div className="pub-drawer__fields">
                                                <label className="pub-drawer__field">
                                                    <span>{t.contact.name}</span>
                                                    <input name="name" autoComplete="name" required />
                                                    {errors.name && <small>{errors.name}</small>}
                                                </label>

                                                <label className="pub-drawer__field">
                                                    <span>{t.contact.email}</span>
                                                    <input
                                                        type="email"
                                                        name="email"
                                                        autoComplete="email"
                                                        required
                                                    />
                                                    {errors.email && <small>{errors.email}</small>}
                                                </label>

                                                <label className="pub-drawer__field">
                                                    <span>{t.contact.subject}</span>
                                                    <input
                                                        name="subject"
                                                        value={subject}
                                                        onChange={(event) => setSubject(event.target.value)}
                                                    />
                                                    {errors.subject && <small>{errors.subject}</small>}
                                                </label>

                                                <label className="pub-drawer__field">
                                                    <span>{t.contact.message}</span>
                                                    <textarea name="message" rows={4} required />
                                                    {errors.message && <small>{errors.message}</small>}
                                                </label>
                                            </div>

                                            <button
                                                type="submit"
                                                className="pub-drawer__send"
                                                disabled={processing}
                                            >
                                                {processing ? t.contact.sending : t.contact.submit}
                                                <span aria-hidden="true">→</span>
                                            </button>
                                        </>
                                    )}
                                </Form>
                            )}
                        </div>

                        <aside className="pub-contact__side">
                            <p className="pub-contact__label">{t.contact.topicsLabel}</p>
                            <ul className="pub-contact__topics">
                                {topics.map((topic) => (
                                    <li key={topic}>
                                        <button
                                            type="button"
                                            aria-pressed={subject === topic}
                                            onClick={() => {
                                                setSent(false);
                                                setSubject(topic);
                                            }}
                                        >
                                            {topic}
                                        </button>
                                    </li>
                                ))}
                            </ul>

                            <p className="pub-contact__label">{t.contactDrawer.altLabel}</p>
                            <a className="pub-contact__mail" href={`mailto:${props.profile.email}`}>
                                {props.profile.email}
                            </a>
                            {props.profile.phone && (
                                <PhoneLink
                                    className="pub-contact__phone"
                                    phone={props.profile.phone}
                                />
                            )}
                        </aside>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
