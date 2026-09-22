import { Form, Head } from '@inertiajs/react';
import DomainController from '@/actions/App/Http/Controllers/Admin/DomainController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TranslatableField from '@/components/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as domainsIndex } from '@/routes/admin/domains';

export default function DomainCreate() {
    return (
        <>
            <Head title="Nouveau domaine" />

            <div className="max-w-xl space-y-6 p-4">
                <Heading
                    title="Nouveau domaine"
                    description="Ajouter un domaine de compétence"
                />

                <Form {...DomainController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="key">Clé *</Label>
                                <Input id="key" name="key" required />
                                <InputError message={errors.key} />
                            </div>

                            <TranslatableField
                                name="label"
                                label="Libellé"
                                required
                                errors={{
                                    fr: errors['label.fr'],
                                    en: errors['label.en'],
                                }}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="color">Couleur (hex) *</Label>
                                <Input
                                    id="color"
                                    name="color"
                                    placeholder="#6366F1"
                                    required
                                />
                                <InputError message={errors.color} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="icon">Icône *</Label>
                                <Input id="icon" name="icon" required />
                                <InputError message={errors.icon} />
                            </div>

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

DomainCreate.layout = {
    breadcrumbs: [
        { title: 'Domaines', href: domainsIndex() },
        { title: 'Nouveau', href: '' },
    ],
};
