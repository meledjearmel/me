import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import ActionTooltip from '@/components/admin/action-tooltip';
import { Button } from '@/components/ui/button';

type DeleteButtonProps = {
    href: string;
    confirmMessage: string;
};

export default function DeleteButton({
    href,
    confirmMessage,
}: DeleteButtonProps) {
    return (
        <AlertDialog>
            <ActionTooltip label="Supprimer">
                <AlertDialogTrigger asChild>
                    <Button variant="ghost" size="icon" aria-label="Supprimer">
                        <Trash2 className="text-destructive" />
                    </Button>
                </AlertDialogTrigger>
            </ActionTooltip>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Confirmer la suppression
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {confirmMessage} Cette action est définitive.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Annuler</AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        onClick={() =>
                            router.delete(href, { preserveScroll: true })
                        }
                    >
                        Supprimer
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
