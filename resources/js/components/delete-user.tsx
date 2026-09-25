import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import PasswordInput from '@/components/password-input';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Supprimer le compte"
                description="Supprimez votre compte et toutes ses données"
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">Attention</p>
                    <p className="text-sm">
                        Cette action est définitive et ne peut pas être annulée.
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            Supprimer le compte
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>
                            Voulez-vous vraiment supprimer votre compte ?
                        </DialogTitle>
                        <DialogDescription>
                            Une fois votre compte supprimé, toutes ses données
                            seront définitivement effacées. Saisissez votre mot
                            de passe pour confirmer la suppression définitive de
                            votre compte.
                        </DialogDescription>

                        <Form
                            {...ProfileController.destroy.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            onError={() => passwordInput.current?.focus()}
                            resetOnSuccess
                            className="space-y-6"
                        >
                            {({ resetAndClearErrors, processing, errors }) => (
                                <FieldGroup>
                                    <Field data-invalid={!!errors.password}>
                                        <FieldLabel
                                            htmlFor="password"
                                            className="sr-only"
                                        >
                                            Mot de passe
                                        </FieldLabel>

                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            ref={passwordInput}
                                            placeholder="Mot de passe"
                                            autoComplete="current-password"
                                        />

                                        <FieldError>
                                            {errors.password}
                                        </FieldError>
                                    </Field>

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button
                                                variant="secondary"
                                                onClick={() =>
                                                    resetAndClearErrors()
                                                }
                                            >
                                                Annuler
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button
                                                type="submit"
                                                data-test="confirm-delete-user-button"
                                            >
                                                Supprimer le compte
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </FieldGroup>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
