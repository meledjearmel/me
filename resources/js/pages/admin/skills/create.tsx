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
import type { Domain } from '@/types';

export default function SkillCreate({ domains }: { domains: Domain[] }) {
    return (
        <>
            <Head title="Nouvelle compétence" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Nouvelle compétence"
                    description="Rattachée à un domaine"
                />

                <Form {...SkillController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="domain_id">Domaine *</Label>
                                <NativeSelect
                                    id="domain_id"
                                    name="domain_id"
                                    required
                                    defaultValue=""
                                >
                                    <option value="" disabled>
                                        Sélectionner...
                                    </option>
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
                                errors={{
                                    fr: errors['name.fr'],
                                    en: errors['name.en'],
                                }}
                            />

                            <TranslatableField
                                name="description"
                                label="Description"
                                textarea
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
                                    defaultValue={0}
                                />
                                <InputError message={errors.sort_order} />
                            </div>

                            <Button disabled={processing}>Créer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

SkillCreate.layout = {
    breadcrumbs: [
        { title: 'Compétences', href: skillsIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
