import { useToast as usePrimeToast } from 'primevue/usetoast';

export function useToast() {
    const toast = usePrimeToast();

    function showSuccess(message: string, summary?: string): void {
        toast.add({
            severity: 'success',
            summary: summary || 'Success',
            detail: message,
            life: 3000,
        });
    }

    function showError(message: string, summary?: string): void {
        toast.add({
            severity: 'error',
            summary: summary || 'Error',
            detail: message,
            life: 5000,
        });
    }

    function showInfo(message: string, summary?: string): void {
        toast.add({
            severity: 'info',
            summary: summary || 'Information',
            detail: message,
            life: 3000,
        });
    }

    function showWarn(message: string, summary?: string): void {
        toast.add({
            severity: 'warn',
            summary: summary || 'Warning',
            detail: message,
            life: 4000,
        });
    }

    return {
        toast,
        showSuccess,
        showError,
        showInfo,
        showWarn,
    };
}
