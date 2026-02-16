import type { InertiaForm } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import type { UrlMethodPair } from '@inertiajs/core';
import { useToast } from './useToast';

interface FormSubmissionOptions {
    route: () => { url: string; method: string };
    initialValues: Record<string, any>;
    onSuccess?: (form: InertiaForm<Record<string, any>>) => void;
    onError?: (form: InertiaForm<Record<string, any>>) => void;
    successMessage?: string;
    resetFieldsOnSuccess?: string[];
    resetFieldsOnError?: string[];
    transform?: (values: Record<string, any>) => Record<string, any>;
    preserveScroll?: boolean;
}

export function useFormSubmission(options: FormSubmissionOptions) {
    const { showSuccess } = useToast();
    const form = useForm(options.initialValues);

    function onSubmit({
        valid,
        values,
    }: {
        valid: boolean;
        values: Record<string, any>;
    }): void {
        if (!valid) {
            return;
        }

        // Apply transform if provided
        const dataToSubmit = options.transform
            ? options.transform(values)
            : values;

        // Assign values to form
        Object.assign(form, dataToSubmit);

        // Build submit options
        const submitOptions: {
            onSuccess?: () => void;
            onError?: () => void;
            preserveScroll?: boolean;
        } = {};

        // Handle success
        submitOptions.onSuccess = () => {
            // Reset specified fields on success
            if (options.resetFieldsOnSuccess) {
                options.resetFieldsOnSuccess.forEach((field) => {
                    form.reset(field);
                });
            }

            // Show success message
            if (options.successMessage) {
                showSuccess(options.successMessage);
            }

            // Call custom onSuccess callback
            if (options.onSuccess) {
                options.onSuccess(form);
            }
        };

        // Handle error
        submitOptions.onError = () => {
            // Reset specified fields on error
            if (options.resetFieldsOnError) {
                options.resetFieldsOnError.forEach((field) => {
                    form.reset(field);
                });
            }

            // Call custom onError callback
            if (options.onError) {
                options.onError(form);
            }
        };

        // Preserve scroll if specified
        if (options.preserveScroll) {
            submitOptions.preserveScroll = true;
        }

        // Submit form
        form.submit(options.route() as UrlMethodPair, submitOptions);
    }

    return {
        form,
        onSubmit,
    };
}
