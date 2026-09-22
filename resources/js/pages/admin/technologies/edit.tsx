import { Form, Head } from '@inertiajs/react';
import TechnologyController from '@/actions/App/Http/Controllers/Admin/TechnologyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { TECHNOLOGY_CATEGORIES } from '@/lib/admin-options';
import { index as technologiesIndex } from '@/routes/admin/technologies';
import type { Technology } from '@/types';

export default function TechnologyEdit({
    technology,
}: {
    technology: Technology;
}) {
    return (
        <>
            <Head title={`Modifier — ${technology.name}`} />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Modifier la technologie"
                    description={technology.name}
                />

                <Form
                    {...TechnologyController.update.form(technology.id)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={technology.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="category">Catégorie *</Label>
                                <NativeSelect
                                    id="category"
                                    name="category"
                                    required
                                    defaultValue={technology.category}
                                >
                                    {TECHNOLOGY_CATEGORIES.map((category) => (
                                        <option
                                            key={category.value}
                                            value={category.value}
                                        >
                                            {category.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.category} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="icon">Icône</Label>
                                <Input
                                    id="icon"
                                    name="icon"
                                    defaultValue={technology.icon ?? ''}
                                />
                                <InputError message={errors.icon} />
                            </div>

                            <Button disabled={processing}>Enregistrer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

TechnologyEdit.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: technologiesIndex() },
        { title: 'Modifier', href: '' },
    ],
};
