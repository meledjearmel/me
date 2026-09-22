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

export default function TechnologyCreate() {
    return (
        <>
            <Head title="Nouvelle technologie" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Nouvelle technologie"
                    description="Ajouter une technologie à la stack"
                />

                <Form
                    {...TechnologyController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom *</Label>
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="category">Catégorie *</Label>
                                <NativeSelect
                                    id="category"
                                    name="category"
                                    required
                                    defaultValue=""
                                >
                                    <option value="" disabled>
                                        Sélectionner...
                                    </option>
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
                                <Input id="icon" name="icon" />
                                <InputError message={errors.icon} />
                            </div>

                            <Button disabled={processing}>Créer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

TechnologyCreate.layout = {
    breadcrumbs: [
        { title: 'Technologies', href: technologiesIndex() },
        { title: 'Nouvelle', href: '' },
    ],
};
