import { Form } from '@inertiajs/react';
import AppointmentController from '@/actions/App/Http/Controllers/Admin/AppointmentController';
import ShowPage, { formatDate } from '@/components/admin/show-page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import {
    APPOINTMENT_LOCATIONS,
    APPOINTMENT_STATUSES,
    APPOINTMENT_STATUS_VARIANT as STATUS_VARIANT,
    formatSlot,
    optionLabel,
} from '@/lib/admin-options';
import { index as appointmentsIndex } from '@/routes/admin/appointments';
import type { Appointment } from '@/types';

export default function AppointmentShow({
    appointment: row,
    defaultVideoLink,
    videoProvider,
}: {
    appointment: Appointment;
    defaultVideoLink: string | null;
    videoProvider: 'jitsi' | 'link';
}) {
    const isOpen = row.status === 'pending' || row.status === 'confirmed';
    const meetingUrl = /^https?:\/\//.test(row.meeting_details ?? '')
        ? row.meeting_details
        : null;

    return (
        <ShowPage
            title={row.name}
            description={formatSlot(row.starts_at, row.ends_at)}
            badge={
                <Badge variant={STATUS_VARIANT[row.status]}>
                    {optionLabel(APPOINTMENT_STATUSES, row.status)}
                </Badge>
            }
            backHref={appointmentsIndex()}
            backLabel="Rendez-vous"
            sections={[
                {
                    title: 'Rendez-vous',
                    items: [
                        {
                            label: 'Type',
                            value: row.appointment_type
                                ? `${row.appointment_type.name.fr} (${row.appointment_type.duration_minutes} min)`
                                : null,
                        },
                        {
                            label: 'Quand (heure d’Abidjan)',
                            value: formatSlot(row.starts_at, row.ends_at),
                        },
                        {
                            label: 'Fuseau du visiteur',
                            value: row.timezone,
                        },
                        {
                            label: 'Lieu',
                            value: optionLabel(
                                APPOINTMENT_LOCATIONS,
                                row.location,
                            ),
                        },
                        {
                            label: 'Détails envoyés',
                            value: meetingUrl ? (
                                <span className="flex flex-wrap items-center gap-3">
                                    <span className="break-all">
                                        {meetingUrl}
                                    </span>
                                    <Button size="sm" asChild>
                                        <a
                                            href={meetingUrl}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Rejoindre la visio
                                        </a>
                                    </Button>
                                </span>
                            ) : (
                                row.meeting_details
                            ),
                            wide: true,
                        },
                        {
                            label: 'Motif du refus',
                            value: row.decline_reason,
                            wide: true,
                        },
                        { label: 'Message', value: row.message, wide: true },
                        {
                            label: 'Langue',
                            value: row.locale === 'fr' ? 'Français' : 'Anglais',
                        },
                        {
                            label: 'Demandé le',
                            value: formatDate(row.created_at),
                        },
                    ],
                },
                {
                    title: 'Contact',
                    items: [
                        { label: 'Nom', value: row.name },
                        {
                            label: 'Email',
                            value: (
                                <a
                                    href={`mailto:${row.email}`}
                                    className="underline"
                                >
                                    {row.email}
                                </a>
                            ),
                        },
                        {
                            label: 'Téléphone',
                            value: row.phone && (
                                <a
                                    href={`tel:${row.phone}`}
                                    className="underline"
                                >
                                    {row.phone}
                                </a>
                            ),
                        },
                        { label: 'Société', value: row.company },
                    ],
                },
                ...(isOpen
                    ? [
                          {
                              title: 'Décision',
                              content: (
                                  <div className="grid gap-6 md:grid-cols-2">
                                      {row.status === 'pending' && (
                                          <Form
                                              {...AppointmentController.confirm.form(
                                                  row.id,
                                              )}
                                              options={{ preserveScroll: true }}
                                              className="space-y-3 rounded-lg border p-4"
                                          >
                                              {({ processing, errors }) => (
                                                  <>
                                                      <Field
                                                          data-invalid={
                                                              !!errors.meeting_details
                                                          }
                                                      >
                                                          <FieldLabel htmlFor="meeting_details">
                                                              Lien visio,
                                                              adresse ou note
                                                          </FieldLabel>
                                                          <Textarea
                                                              id="meeting_details"
                                                              name="meeting_details"
                                                              rows={3}
                                                              defaultValue={
                                                                  row.location ===
                                                                  'video'
                                                                      ? (defaultVideoLink ??
                                                                        '')
                                                                      : ''
                                                              }
                                                          />
                                                          <FieldDescription>
                                                              Envoyé au visiteur
                                                              avec la
                                                              confirmation.
                                                              {row.location ===
                                                                  'video' &&
                                                                  videoProvider ===
                                                                      'jitsi' &&
                                                                  ' Laissé vide, un lien Jitsi unique est créé pour ce rendez-vous.'}
                                                          </FieldDescription>
                                                          <FieldError>
                                                              {
                                                                  errors.meeting_details
                                                              }
                                                          </FieldError>
                                                      </Field>
                                                      <Button
                                                          disabled={processing}
                                                      >
                                                          Confirmer
                                                      </Button>
                                                  </>
                                              )}
                                          </Form>
                                      )}

                                      <Form
                                          {...AppointmentController.decline.form(
                                              row.id,
                                          )}
                                          options={{ preserveScroll: true }}
                                          className="space-y-3 rounded-lg border p-4"
                                      >
                                          {({ processing, errors }) => (
                                              <>
                                                  <Field
                                                      data-invalid={
                                                          !!errors.decline_reason
                                                      }
                                                  >
                                                      <FieldLabel htmlFor="decline_reason">
                                                          Motif du refus
                                                          (facultatif)
                                                      </FieldLabel>
                                                      <Textarea
                                                          id="decline_reason"
                                                          name="decline_reason"
                                                          rows={3}
                                                      />
                                                      <FieldDescription>
                                                          Le créneau sera
                                                          libéré.
                                                      </FieldDescription>
                                                      <FieldError>
                                                          {
                                                              errors.decline_reason
                                                          }
                                                      </FieldError>
                                                  </Field>
                                                  <Button
                                                      variant="outline"
                                                      disabled={processing}
                                                  >
                                                      {row.status === 'pending'
                                                          ? 'Refuser'
                                                          : 'Annuler le rendez-vous'}
                                                  </Button>
                                              </>
                                          )}
                                      </Form>
                                  </div>
                              ),
                          },
                      ]
                    : []),
            ]}
        />
    );
}

AppointmentShow.layout = {
    breadcrumbs: [
        { title: 'Rendez-vous', href: appointmentsIndex() },
        { title: 'Détail', href: '' },
    ],
};
