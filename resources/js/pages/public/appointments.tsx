import { Form, useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
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
    // Trois étapes : le rendez-vous (déjà choisi s'il n'y en a qu'un), le créneau, les coordonnées.
    const steps = ['type', 'slot', 'details'] as const;
    const [stepIndex, setStepIndex] = useState(0);
    const [blocked, setBlocked] = useState(false);
    const headingRef = useRef<HTMLElement>(null);
    const hasNavigated = useRef(false);
    const setHeading = (element: HTMLElement | null) => {
        headingRef.current = element;
    };
    const step = steps[stepIndex];
    const stepTitles = {
        type: t.booking.step1,
        slot: t.booking.step2,
        details: t.booking.step3,
    };

    // Le titre de l'étape prend le focus à chaque changement, pour les lecteurs d'écran.
    useEffect(() => {
        if (hasNavigated.current) {
            headingRef.current?.focus();
        }
    }, [stepIndex]);

    const goTo = (index: number) => {
        hasNavigated.current = true;
        setBlocked(false);
        setStepIndex(Math.max(0, Math.min(index, steps.length - 1)));
    };

    /** « Continuer » : refusé tant que l'étape n'a pas son choix (type, puis créneau). */
    const next = () => {
        if (
            (step === 'type' && typeId === null) ||
            (step === 'slot' && startsAt === null)
        ) {
            setBlocked(true);

            return;
        }

        goTo(stepIndex + 1);
    };

    /** Barre « Étape 2 sur 3 » : les étapes faites restent cliquables. */
    const progress = (
        <div className="pub-estimate__progress">
            <p className="pub-booking__label">
                {t.booking.progress(stepIndex + 1, steps.length)}
                <span className="pub-estimate__current">
                    {` · ${stepTitles[step]}`}
                </span>
            </p>
            <ol>
                {steps.map((key, index) => (
                    <li
                        key={key}
                        data-state={
                            index < stepIndex
                                ? 'done'
                                : index === stepIndex
                                  ? 'current'
                                  : 'todo'
                        }
                        aria-current={index === stepIndex ? 'step' : undefined}
                    >
                        {index < stepIndex ? (
                            <button type="button" onClick={() => goTo(index)}>
                                {stepTitles[key]}
                            </button>
                        ) : (
                            <span>{stepTitles[key]}</span>
                        )}
                    </li>
                ))}
            </ol>
        </div>
    );

    const navigation = (
        <div className="pub-estimate__nav">
            {stepIndex > 0 ? (
                <button
                    type="button"
                    className="pub-booking__link"
                    onClick={() => goTo(stepIndex - 1)}
                >
                    ← {t.booking.back}
                </button>
            ) : (
                <span />
            )}
            <button type="button" className="pub-drawer__send" onClick={next}>
                {t.booking.next}
                <span aria-hidden="true">→</span>
            </button>
        </div>
    );

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
        setBlocked(false);
        setDay(null);
        setStartsAt(null);
        setLocation(chosen.locations[0] ?? null);
    };

    const restart = () => {
        setSent(false);
        setDay(null);
        setStartsAt(null);
        goTo(0);

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
                                    {progress}

                                    {step === 'type' && (
                                        <fieldset className="pub-booking__step pub-estimate__panel">
                                            <legend
                                                ref={setHeading}
                                                tabIndex={-1}
                                            >
                                                <span>
                                                    {String(
                                                        stepIndex + 1,
                                                    ).padStart(2, '0')}
                                                </span>{' '}
                                                {t.booking.step1}
                                            </legend>
                                            <div className="pub-booking__types">
                                                {types.map((candidate) => (
                                                    <button
                                                        key={candidate.id}
                                                        type="button"
                                                        className="pub-booking__type"
                                                        aria-pressed={
                                                            candidate.id ===
                                                            typeId
                                                        }
                                                        onClick={() =>
                                                            chooseType(
                                                                candidate,
                                                            )
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
                                            {blocked && (
                                                <small className="pub-booking__error">
                                                    {t.booking.chooseType}
                                                </small>
                                            )}
                                            {navigation}
                                        </fieldset>
                                    )}

                                    {step === 'slot' && type && (
                                        <fieldset className="pub-booking__step pub-estimate__panel">
                                            <legend
                                                ref={setHeading}
                                                tabIndex={-1}
                                            >
                                                <span>
                                                    {String(
                                                        stepIndex + 1,
                                                    ).padStart(2, '0')}
                                                </span>{' '}
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
                                                                onClick={() => {
                                                                    setStartsAt(
                                                                        slot,
                                                                    );
                                                                    setBlocked(
                                                                        false,
                                                                    );
                                                                }}
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
                                            {blocked && (
                                                <small className="pub-booking__error">
                                                    {t.booking.chooseSlot}
                                                </small>
                                            )}
                                            {navigation}
                                        </fieldset>
                                    )}

                                    {step === 'details' && type && startsAt && (
                                        <Form
                                            {...AppointmentBookingController.store.form(
                                                locale,
                                            )}
                                            className="pub-booking__step pub-contact__form pub-estimate__panel"
                                            options={{ preserveScroll: true }}
                                            onSuccess={() => setSent(true)}
                                            onError={(errors) => {
                                                // Le créneau a été pris entre-temps : on recharge les disponibilités et on y retourne.
                                                if (errors.starts_at) {
                                                    setStartsAt(null);
                                                    void loadSlots(type.id);
                                                    goTo(steps.indexOf('slot'));
                                                }
                                            }}
                                        >
                                            {({ processing, errors }) => (
                                                <>
                                                    <h2
                                                        ref={setHeading}
                                                        tabIndex={-1}
                                                        className="pub-booking__legend"
                                                    >
                                                        <span>
                                                            {String(
                                                                stepIndex + 1,
                                                            ).padStart(2, '0')}
                                                        </span>{' '}
                                                        {t.booking.step3}
                                                    </h2>

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

                                                    <div className="pub-estimate__nav">
                                                        <button
                                                            type="button"
                                                            className="pub-booking__link"
                                                            onClick={() =>
                                                                goTo(
                                                                    stepIndex -
                                                                        1,
                                                                )
                                                            }
                                                        >
                                                            ← {t.booking.back}
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            className="pub-drawer__send"
                                                            disabled={
                                                                processing ||
                                                                location ===
                                                                    null
                                                            }
                                                        >
                                                            {processing
                                                                ? t.booking
                                                                      .sending
                                                                : t.booking
                                                                      .submit}
                                                            <span aria-hidden="true">
                                                                →
                                                            </span>
                                                        </button>
                                                    </div>
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
