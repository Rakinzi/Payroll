import { useDialogStore } from '@/stores/dialog-store';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

/**
 * Global dialog provider that renders alert and confirm dialogs
 * Should be placed at the root of the application
 */
export function DialogProvider() {
    const dialogs = useDialogStore((state) => state.dialogs);
    const closeDialog = useDialogStore((state) => state.closeDialog);

    return (
        <>
            {dialogs.map((dialog) => (
                <AlertDialog key={dialog.id} open={true} onOpenChange={(open) => {
                    if (!open) {
                        if (dialog.type === 'confirm' && dialog.onCancel) {
                            dialog.onCancel();
                        } else {
                            closeDialog(dialog.id);
                        }
                    }
                }}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            {dialog.title && (
                                <AlertDialogTitle>{dialog.title}</AlertDialogTitle>
                            )}
                            <AlertDialogDescription>
                                {dialog.message}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            {dialog.type === 'confirm' ? (
                                <>
                                    <AlertDialogCancel onClick={dialog.onCancel}>
                                        {dialog.cancelText || 'Cancel'}
                                    </AlertDialogCancel>
                                    <AlertDialogAction
                                        onClick={dialog.onConfirm}
                                        className={
                                            dialog.variant === 'destructive'
                                                ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90'
                                                : ''
                                        }
                                    >
                                        {dialog.confirmText || 'Confirm'}
                                    </AlertDialogAction>
                                </>
                            ) : (
                                <AlertDialogAction onClick={() => closeDialog(dialog.id)}>
                                    {dialog.confirmText || 'OK'}
                                </AlertDialogAction>
                            )}
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            ))}
        </>
    );
}
