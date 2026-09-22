import { Form, Head } from '@inertiajs/react';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
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
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Statut *</Label>
                                <NativeSelect
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
                                </NativeSelect>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="project_id">Projet lié</Label>
                                <NativeSelect
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
                                </NativeSelect>
                                <InputError message={errors.project_id} />
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
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
