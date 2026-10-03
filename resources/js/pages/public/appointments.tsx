import { Form, useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import AppointmentBookingController from '@/actions/App/Http/Controllers/AppointmentBookingController';
import PageHero from '@/components/public/page-hero';
import PublicShell from '@/components/public/public-shell';
import Seo from '@/components/public/seo';
import { useLocale, useTranslations } from '@/lib/i18n';
import type { AppointmentLocation, PublicAppointmentType } from '@/types';

type SlotDay = { date: string; slots: string[] };

/** Fuseau du visiteur (Abidjan si le navigateur ne le donne pas). */
const visitorTimeZone = (): string => {
    try {
        return (
            Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Abidjan'
        );
    } catch {
        return 'Africa/Abidjan';
    }
};

/**
 * Prise de rendez-vous en trois temps : le type d'échange, le créneau (affiché
 * dans le fuseau du visiteur), puis ses coordonnées. Le créneau est bloqué dès
 * l'envoi, en attendant ma confirmation.
 */
export default function Appointments({
    types,
}: {
    types: PublicAppointmentType[];
}) {
    const t = useTranslations();
    const locale = useLocale();
    const { submit } = useHttp();
    const timeZone = useMemo(visitorTimeZone, []);

    const [typeId, setTypeId] = useState<number | null>(
        types.length === 1 ? types[0].id : null,
    );
    const [days, setDays] = useState<SlotDay[] | null>(null);
    const [loadFailed, setLoadFailed] = useState(false);
    const [day, setDay] = useState<string | null>(null);
    const [startsAt, setStartsAt] = useState<string | null>(null);
    // Le premier lieu proposé est choisi d'office ; le visiteur peut en changer.
    const [location, setLocation] = useState<AppointmentLocation | null>(
        types.length === 1 ? types[0].locations[0] : null,
    );
    const [sent, setSent] = useState(false);

    const type = types.find((candidate) => candidate.id === typeId) ?? null;

    const dateKey = useMemo(
        () =>
            new Intl.DateTimeFormat('en-CA', {
                timeZone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }),
        [timeZone],
    );
    const dayLabel = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                timeZone,
                weekday: 'short',
                day: 'numeric',
                month: 'short',
            }),
        [locale, timeZone],
    );
    const timeLabel = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                timeZone,
                hour: '2-digit',
                minute: '2-digit',
            }),
        [locale, timeZone],
    );
    const fullLabel = useMemo(
        () =>
            new Intl.DateTimeFormat(locale, {
                timeZone,
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                hour: '2-digit',
                minute: '2-digit',
            }),
        [locale, timeZone],
    );

    // Les créneaux arrivent par jour d'Abidjan : on les regroupe par jour du visiteur.
    const slotsByDay = useMemo(() => {
        const grouped = new Map<string, string[]>();

        for (const slot of (days ?? []).flatMap((entry) => entry.slots)) {
            const key = dateKey.format(new Date(slot));
            grouped.set(key, [...(grouped.get(key) ?? []), slot]);
        }

        return grouped;
    }, [days, dateKey]);

    const loadSlots = useCallback(
        async (id: number) => {
            setDays(null);
            setLoadFailed(false);

            try {
                const response = (await submit(
                    AppointmentBookingController.slots(locale, {
                        query: { type: id },
                    }),
                )) as { days: SlotDay[] };

                setDays(response.days);
            } catch {
                setLoadFailed(true);
            }
        },
        [locale, submit],
    );

    useEffect(() => {
        if (typeId !== null) {
            void loadSlots(typeId);
        }
    }, [typeId, loadSlots]);

    const chooseType = (chosen: PublicAppointmentType) => {
        setTypeId(chosen.id);
        setDay(null);
        setStartsAt(null);
        setLocation(chosen.locations[0] ?? null);
    };

    const restart = () => {
        setSent(false);
        setDay(null);
        setStartsAt(null);

        if (typeId !== null) {
            void loadSlots(typeId);
        }
    };

    const needsPhone = location === 'phone' || location === 'whatsapp';
    const firstDay = slotsByDay.keys().next().value ?? null;
    const activeDay = day ?? firstDay;

    return (
        <>
            <Seo
                title={t.booking.title}
                description={t.booking.lead}
                breadcrumbs={[[t.booking.title, `/${locale}/appointments`]]}
            />

            <PublicShell overHero>
                <PageHero
                    id="pub-booking-title"
                    eyebrow={t.booking.kicker}
                    title={t.booking.heading}
                    lead={<p>{t.booking.lead}</p>}
                />

                <section
                    className="pub-contact pub-booking"
                    aria-label={t.booking.title}
                >
                    <div className="site-wrap">
                        <div className="pub-contact__card pub-booking__card">
                            {sent ? (
                                <div className="pub-drawer__done" role="status">
                                    <svg
                                        className="pub-drawer__check"
                                        viewBox="0 0 64 64"
                                        aria-hidden="true"
                                    >
                                        <circle cx="32" cy="32" r="28" />
                                        <path d="M19 33l9 9 17-19" />
                                    </svg>
                                    <h3>{t.booking.doneTitle}</h3>
                                    <p>{t.booking.done}</p>
                                    <button
                                        type="button"
                                        className="pub-contact__again"
                                        onClick={restart}
                                    >
                                        {t.booking.again}
                                    </button>
                                </div>
                            ) : (
                                <>
                                    <fieldset className="pub-booking__step">
                                        <legend>
                                            <span>01</span> {t.booking.step1}
                                        </legend>
                                        <div className="pub-booking__types">
                                            {types.map((candidate) => (
                                                <button
                                                    key={candidate.id}
                                                    type="button"
                                                    className="pub-booking__type"
                                                    aria-pressed={
                                                        candidate.id === typeId
                                                    }
                                                    onClick={() =>
                                                        chooseType(candidate)
                                                    }
                                                >
                                                    <strong>
                                                        {candidate.name}
                                                    </strong>
                                                    <em>
                                                        {t.booking.minutes(
                                                            candidate.duration_minutes,
                                                        )}
                                                    </em>
                                                    {candidate.description && (
                                                        <span>
                                                            {
                                                                candidate.description
                                                            }
                                                        </span>
                                                    )}
                                                </button>
                                            ))}
                                        </div>
                                    </fieldset>

                                    {type && (
                                        <fieldset className="pub-booking__step">
                                            <legend>
                                                <span>02</span>{' '}
                                                {t.booking.step2}
                                            </legend>

                                            {loadFailed ? (
                                                <p className="pub-booking__note">
                                                    {t.booking.loadError}{' '}
                                                    <button
                                                        type="button"
                                                        className="pub-booking__link"
                                                        onClick={() =>
                                                            void loadSlots(
                                                                type.id,
                                                            )
                                                        }
                                                    >
                                                        {t.booking.retry}
                                                    </button>
                                                </p>
                                            ) : days === null ? (
                                                <div
                                                    className="pub-booking__skeleton"
                                                    role="status"
                                                    aria-label={
                                                        t.booking.loading
                                                    }
                                                >
                                                    {Array.from(
                                                        { length: 6 },
                                                        (_, index) => (
                                                            <i key={index} />
                                                        ),
                                                    )}
                                                </div>
                                            ) : slotsByDay.size === 0 ? (
                                                <p className="pub-booking__note">
                                                    {t.booking.noSlots}
                                                </p>
                                            ) : (
                                                <>
                                                    <p className="pub-booking__label">
                                                        {t.booking.chooseDay}
                                                    </p>
                                                    <div
                                                        className="pub-booking__days"
                                                        role="group"
                                                        aria-label={
                                                            t.booking.chooseDay
                                                        }
                                                    >
                                                        {[
                                                            ...slotsByDay.entries(),
                                                        ].map(
                                                            ([key, slots]) => (
                                                                <button
                                                                    key={key}
                                                                    type="button"
                                                                    className="pub-filter"
                                                                    aria-pressed={
                                                                        key ===
                                                                        activeDay
                                                                    }
                                                                    onClick={() => {
                                                                        setDay(
                                                                            key,
                                                                        );
                                                                        setStartsAt(
                                                                            null,
                                                                        );
                                                                    }}
                                                                >
                                                                    {dayLabel.format(
                                                                        new Date(
                                                                            slots[0],
                                                                        ),
                                                                    )}
                                                                </button>
                                                            ),
                                                        )}
                                                    </div>

                                                    <p className="pub-booking__label">
                                                        {t.booking.chooseTime}
                                                    </p>
                                                    <div
                                                        className="pub-booking__times"
                                                        role="group"
                                                        aria-label={
                                                            t.booking.chooseTime
                                                        }
                                                    >
                                                        {(
                                                            slotsByDay.get(
                                                                activeDay ?? '',
                                                            ) ?? []
                                                        ).map((slot) => (
                                                            <button
                                                                key={slot}
                                                                type="button"
                                                                className="pub-filter"
                                                                aria-pressed={
                                                                    slot ===
                                                                    startsAt
                                                                }
                                                                onClick={() =>
                                                                    setStartsAt(
                                                                        slot,
                                                                    )
                                                                }
                                                            >
                                                                {timeLabel.format(
                                                                    new Date(
                                                                        slot,
                                                                    ),
                                                                )}
                                                            </button>
                                                        ))}
                                                    </div>

                                                    <p className="pub-booking__note">
                                                        {t.booking.timezoneNote(
                                                            timeZone,
                                                        )}
                                                    </p>
                                                </>
                                            )}
                                        </fieldset>
                                    )}

                                    {type && startsAt && (
                                        <Form
                                            {...AppointmentBookingController.store.form(
                                                locale,
                                            )}
                                            className="pub-booking__step pub-contact__form"
                                            options={{ preserveScroll: true }}
                                            onSuccess={() => setSent(true)}
                                            onError={(errors) => {
                                                // Le créneau a été pris entre-temps : on recharge les disponibilités.
                                                if (errors.starts_at) {
                                                    setStartsAt(null);
                                                    void loadSlots(type.id);
                                                }
                                            }}
                                        >
                                            {({ processing, errors }) => (
                                                <>
                                                    <h3 className="pub-booking__legend">
                                                        <span>03</span>{' '}
                                                        {t.booking.step3}
                                                    </h3>

                                                    <input
                                                        type="text"
                                                        name="website"
                                                        tabIndex={-1}
                                                        autoComplete="off"
                                                        hidden
                                                        aria-hidden="true"
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="appointment_type_id"
                                                        value={type.id}
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="starts_at"
                                                        value={startsAt}
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="timezone"
                                                        value={timeZone}
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name="location"
                                                        value={location ?? ''}
                                                    />

                                                    <p className="pub-booking__summary">
                                                        <span>
                                                            {t.booking.summary}
                                                        </span>
                                                        <strong>
                                                            {type.name} ·{' '}
                                                            {fullLabel.format(
                                                                new Date(
                                                                    startsAt,
                                                                ),
                                                            )}
                                                        </strong>
                                                    </p>

                                                    <p className="pub-booking__label">
                                                        {t.booking.location}
                                                    </p>
                                                    <div
                                                        className="pub-booking__times"
                                                        role="group"
                                                        aria-label={
                                                            t.booking.location
                                                        }
                                                    >
                                                        {type.locations.map(
                                                            (candidate) => (
                                                                <button
                                                                    key={
                                                                        candidate
                                                                    }
                                                                    type="button"
                                                                    className="pub-filter"
                                                                    aria-pressed={
                                                                        candidate ===
                                                                        location
                                                                    }
                                                                    onClick={() =>
                                                                        setLocation(
                                                                            candidate,
                                                                        )
                                                                    }
                                                                >
                                                                    {
                                                                        t
                                                                            .booking
                                                                            .locations[
                                                                            candidate
                                                                        ]
                                                                    }
                                                                </button>
                                                            ),
                                                        )}
                                                    </div>
                                                    {errors.location && (
                                                        <small className="pub-booking__error">
                                                            {errors.location}
                                                        </small>
                                                    )}
                                                    {errors.starts_at && (
                                                        <small className="pub-booking__error">
                                                            {errors.starts_at}
                                                        </small>
                                                    )}

                                                    <div className="pub-drawer__fields">
                                                        <label className="pub-drawer__field">
                                                            <span>
                                                                {t.booking.name}
                                                            </span>
                                                            <input
                                                                name="name"
                                                                autoComplete="name"
                                                                required
                                                            />
                                                            {errors.name && (
                                                                <small>
                                                                    {
                                                                        errors.name
                                                                    }
                                                                </small>
                                                            )}
                                                        </label>

                                                        <label className="pub-drawer__field">
                                                            <span>
                                                                {
                                                                    t.booking
                                                                        .email
                                                                }
                                                            </span>
                                                            <input
                                                                type="email"
                                                                name="email"
                                                                autoComplete="email"
                                                                required
                                                            />
                                                            {errors.email && (
                                                                <small>
                                                                    {
                                                                        errors.email
                                                                    }
                                                                </small>
                                                            )}
                                                        </label>

                                                        {needsPhone && (
                                                            <label className="pub-drawer__field">
                                                                <span>
                                                                    {
                                                                        t
                                                                            .booking
                                                                            .phone
                                                                    }
                                                                </span>
                                                                <input
                                                                    type="tel"
                                                                    name="phone"
                                                                    autoComplete="tel"
                                                                    required
                                                                />
                                                                {errors.phone && (
                                                                    <small>
                                                                        {
                                                                            errors.phone
                                                                        }
                                                                    </small>
                                                                )}
                                                            </label>
                                                        )}

                                                        <label className="pub-drawer__field">
                                                            <span>
                                                                {
                                                                    t.booking
                                                                        .company
                                                                }
                                                            </span>
                                                            <input
                                                                name="company"
                                                                autoComplete="organization"
                                                            />
                                                        </label>

                                                        <label className="pub-drawer__field pub-drawer__field--wide">
                                                            <span>
                                                                {
                                                                    t.booking
                                                                        .message
                                                                }
                                                            </span>
                                                            <textarea
                                                                name="message"
                                                                rows={3}
                                                            />
                                                            {errors.message && (
                                                                <small>
                                                                    {
                                                                        errors.message
                                                                    }
                                                                </small>
                                                            )}
                                                        </label>
                                                    </div>

                                                    <button
                                                        type="submit"
                                                        className="pub-drawer__send"
                                                        disabled={
                                                            processing ||
                                                            location === null
                                                        }
                                                    >
                                                        {processing
                                                            ? t.booking.sending
                                                            : t.booking.submit}
                                                        <span aria-hidden="true">
                                                            →
                                                        </span>
                                                    </button>
                                                </>
                                            )}
                                        </Form>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </section>
            </PublicShell>
        </>
    );
}
