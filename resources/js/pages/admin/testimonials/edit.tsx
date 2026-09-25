import { Form, Head } from '@inertiajs/react';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import Heading from '@/components/heading';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import FormSelect from '@/components/admin/form-select';
import { TESTIMONIAL_STATUSES } from '@/lib/admin-options';
import { index as testimonialsIndex } from '@/routes/admin/testimonials';
import type { Project, Testimonial } from '@/types';

export default function TestimonialEdit({
    testimonial,
    projects,
}: {
    testimonial: Testimonial;
    projects: Project[];
}) {
    return (
        <>
            <Head title={`Avis de ${testimonial.author_name}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modérer l'avis"
                    description={`${testimonial.author_name} — ${testimonial.author_email}`}
                />

                <div className="rounded-md border bg-muted/30 p-4 text-sm">
                    <p>{testimonial.content.fr}</p>
                </div>

                <Form
                    {...TestimonialController.update.form(testimonial.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <FieldGroup>
                            <Field data-invalid={!!errors.status}>
                                <FieldLabel htmlFor="status">
                                    Statut *
                                </FieldLabel>
                                <FormSelect
                                    id="status"
                                    name="status"
                                    required
                                    defaultValue={testimonial.status}
                                >
                                    {TESTIMONIAL_STATUSES.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.status}</FieldError>
                            </Field>

                            <div className="grid gap-2">
                                <Field orientation="horizontal">
                                    <input
                                        type="hidden"
                                        name="is_featured"
                                        value="0"
                                    />
                                    <Checkbox
                                        id="is_featured"
                                        name="is_featured"
                                        value="1"
                                        defaultChecked={testimonial.is_featured}
                                    />
                                    <FieldLabel htmlFor="is_featured">
                                        À la une sur l'accueil (3 au maximum)
                                    </FieldLabel>
                                </Field>
                                <p className="text-sm text-muted-foreground">
                                    Seuls les avis approuvés s'affichent. Sans
                                    aucun avis choisi, trois avis approuvés sont
                                    tirés au hasard ; avec un seul choisi, il
                                    reste seul.
                                </p>
                                <FieldError>{errors.is_featured}</FieldError>
                            </div>

                            <Field data-invalid={!!errors.project_id}>
                                <FieldLabel htmlFor="project_id">
                                    Projet lié
                                </FieldLabel>
                                <FormSelect
                                    id="project_id"
                                    name="project_id"
                                    defaultValue={testimonial.project_id ?? ''}
                                >
                                    <option value="">Général</option>
                                    {projects.map((project) => (
                                        <option
                                            key={project.id}
                                            value={project.id}
                                        >
                                            {project.title.fr}
                                        </option>
                                    ))}
                                </FormSelect>
                                <FieldError>{errors.project_id}</FieldError>
                            </Field>

                            <Button disabled={processing}>Enregistrer</Button>
                        </FieldGroup>
                    )}
                </Form>
            </div>
        </>
    );
}

TestimonialEdit.layout = {
    breadcrumbs: [
        { title: 'Avis', href: testimonialsIndex() },
        { title: 'Modérer', href: '' },
    ],
};
