import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm';
import { useIcon } from './useIcon';

export interface ConfirmOptions {
    target: HTMLElement | undefined;
    message?: string;
    header?: string;
    icon?: string;
    accept?: () => void;
    reject?: () => void;
    acceptLabel?: string;
    rejectLabel?: string;
    acceptSeverity?:
        | 'primary'
        | 'secondary'
        | 'success'
        | 'info'
        | 'warning'
        | 'danger'
        | 'help'
        | 'contrast';
    rejectSeverity?:
        | 'primary'
        | 'secondary'
        | 'success'
        | 'info'
        | 'warning'
        | 'danger'
        | 'help'
        | 'contrast';
    group?: string;
}

export function useConfirm() {
    const confirm = usePrimeConfirm();
    const { IconList } = useIcon();

    function requireDelete(options: {
        target: HTMLElement | undefined;
        message?: string;
        onAccept: () => void;
        onReject?: () => void;
    }): void {
        confirm.require({
            target: options.target,
            message:
                options.message || 'Are you sure you want to delete this item?',
            icon: IconList.ExclamationTriangle,
            header: 'Confirm Deletion',
            rejectProps: {
                label: 'Cancel',
                severity: 'secondary',
                outlined: true,
            },
            acceptProps: {
                label: 'Delete',
                severity: 'danger',
            },
            accept: options.onAccept,
            reject: options.onReject,
        });
    }

    function require(options: ConfirmOptions): void {
        confirm.require({
            target: options.target,
            message: options.message || 'Are you sure you want to proceed?',
            header: options.header,
            icon: options.icon || IconList.ExclamationTriangle,
            rejectProps: {
                label: options.rejectLabel || 'Cancel',
                severity: options.rejectSeverity || 'secondary',
                outlined: true,
            },
            acceptProps: {
                label: options.acceptLabel || 'Confirm',
                severity: options.acceptSeverity || 'primary',
            },
            accept: options.accept,
            reject: options.reject,
            group: options.group,
        });
    }

    return {
        confirm,
        requireDelete,
        require,
    };
}
