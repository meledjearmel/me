import { Download } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useLocalizedPath, useTranslations } from '@/lib/i18n';
import { xsrfToken } from '@/lib/utils';

type Status = 'idle' | 'loading' | 'done' | 'error';

/** Nom du fichier proposé par le serveur (Content-Disposition), sinon un nom par défaut. */
function filenameFrom(response: Response): string {
    const header = response.headers.get('Content-Disposition') ?? '';
    const match = header.match(/filename="?([^";]+)"?/);

    return match ? match[1] : 'CV.pdf';
}

/**
 * Carte « Mon CV » de la page contact : le visiteur peut laisser son email
 * (facultatif) puis télécharger le PDF, sans quitter la page. Le serveur
 * enregistre le téléchargement et sa provenance.
 */
export default function CvDownloadCard() {
    const t = useTranslations();
    const path = useLocalizedPath();
    const [email, setEmail] = useState('');
    const [website, setWebsite] = useState('');
    const [status, setStatus] = useState<Status>('idle');
    const [invalidEmail, setInvalidEmail] = useState(false);

    const download = async (event: FormEvent) => {
        event.preventDefault();
        setStatus('loading');
        setInvalidEmail(false);

        try {
            const response = await fetch(path('cv'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/pdf, application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                body: JSON.stringify({ email: email || null, website }),
            });

            if (response.status === 422) {
                setInvalidEmail(true);
                setStatus('idle');

                return;
            }

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const url = URL.createObjectURL(await response.blob());
            const link = document.createElement('a');

            link.href = url;
            link.download = filenameFrom(response);
            document.body.append(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
            setStatus('done');
        } catch {
            setStatus('error');
        }
    };

    return (
        <form className="pub-cv" onSubmit={download} noValidate>
            <p className="pub-contact__label">{t.contact.cvLabel}</p>
            <p className="pub-cv__text">{t.contact.cvText}</p>

            <label className="pub-cv__field">
                <span className="sr-only">{t.contact.cvEmailLabel}</span>
                <input
                    type="email"
                    name="email"
                    autoComplete="email"
                    placeholder={t.contact.cvEmailPlaceholder}
                    value={email}
                    aria-invalid={invalidEmail}
                    aria-describedby="pub-cv-hint"
                    onChange={(event) => setEmail(event.target.value)}
                />
            </label>
            <p id="pub-cv-hint" className="pub-cv__hint">
                {invalidEmail
                    ? t.contact.cvEmailInvalid
                    : t.contact.cvEmailHint}
            </p>

            {/* Champ piège, invisible pour les humains. */}
            <input
                type="text"
                name="website"
                tabIndex={-1}
                autoComplete="off"
                hidden
                aria-hidden="true"
                value={website}
                onChange={(event) => setWebsite(event.target.value)}
            />

            <button
                type="submit"
                className="pub-cv__button"
                disabled={status === 'loading'}
            >
                <Download size={18} aria-hidden="true" />
                {status === 'loading'
                    ? t.contact.cvLoading
                    : t.contact.cvButton}
            </button>

            <p className="pub-cv__status" aria-live="polite">
                {status === 'done' && t.contact.cvThanks}
                {status === 'error' && t.contact.cvError}
            </p>
        </form>
    );
}
