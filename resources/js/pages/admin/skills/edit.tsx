import { Form, Head } from '@inertiajs/react';
import SkillController from '@/actions/App/Http/Controllers/Admin/SkillController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { index as skillsIndex } from '@/routes/admin/skills';
import type { Domain, Skill } from '@/types';

export default function SkillEdit({
    skill,
    domains,
}: {
    skill: Skill;
    domains: Domain[];
}) {
    return (
        <>
            <Head title={`Modifier — ${skill.name.fr}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier la compétence"
                    description={skill.name.fr}
                />

                <Form
                    {...SkillController.update.form(skill.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="domain_id">Domaine *</Label>
                                <NativeSelect
                                    id="domain_id"
                                    name="domain_id"
                                    required
                                    defaultValue={skill.domain_id}
                                >
                                    {domains.map((domain) => (
                                        <option
                                            key={domain.id}
                                            value={domain.id}
                                        >
                                            {domain.label.fr}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.domain_id} />
                            </div>

                            <TranslatableField
                                name="name"
                                label="Nom"
                                required
                                defaultValue={skill.name}
                                errors={{
                                    fr: errors['name.fr'],
                                    en: errors['name.en'],
                                }}
                            />

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
                                defaultValue={skill.description ?? undefined}
                                errors={{
                                    fr: errors['description.fr'],
                                    en: errors['description.en'],
                                }}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">Ordre</Label>
                                <Input
                                    id="sort_order"
                                    name="sort_order"
                                    type="number"
                                    defaultValue={skill.sort_order}
                                />
                                <InputError message={errors.sort_order} />
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

SkillEdit.layout = {
    breadcrumbs: [
        { title: 'Compétences', href: skillsIndex() },
        { title: 'Modifier', href: '' },
    ],
};
