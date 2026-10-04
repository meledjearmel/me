import { Form } from '@inertiajs/react';
import { useState } from 'react';
import NewsletterController from '@/actions/App/Http/Controllers/NewsletterController';
import { useLocale, useTranslations } from '@/lib/i18n';

/** Inscription à la newsletter du blog : un email, confirmé ensuite par un lien. */
export default function NewsletterSignup() {
    const t = useTranslations();
    const locale = useLocale();
    const [sent, setSent] = useState(false);

    return (
        <section
            className="pub-newsletter"
            aria-labelledby="pub-newsletter-title"
        >
            <div className="site-wrap pub-newsletter__grid">
                <div>
                    <h2
                        id="pub-newsletter-title"
                        className="pub-newsletter__title"
                    >
                        {t.newsletter.title}
                    </h2>
                    <p className="pub-newsletter__text">{t.newsletter.text}</p>
                </div>

                {sent ? (
                    <p className="pub-newsletter__done" role="status">
                        {t.newsletter.done}
                    </p>
                ) : (
                    <Form
                        {...NewsletterController.store.form(locale)}
                        options={{ preserveScroll: true }}
                        className="pub-newsletter__form"
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
                                <label className="pub-drawer__field">
                                    <span>{t.newsletter.email}</span>
                                    <input
                                        type="email"
                                        name="email"
                                        autoComplete="email"
                                        placeholder={t.newsletter.placeholder}
                                        required
                                    />
                                    {errors.email && (
                                        <small>{errors.email}</small>
                                    )}
                                </label>
                                <button
                                    type="submit"
                                    className="pub-project-cta__button"
                                    disabled={processing}
                                >
                                    {t.newsletter.submit}
                                    <span aria-hidden="true">→</span>
                                </button>
                                <p className="pub-newsletter__hint">
                                    {t.newsletter.hint}
                                </p>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </section>
    );
}
