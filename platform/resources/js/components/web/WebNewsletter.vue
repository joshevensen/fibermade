<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';

interface Props {
    variant?: 'justified' | 'stacked';
    title: string;
    emailPlaceholder?: string;
    submitButtonText?: string;
    privacyPolicy?: {
        text: string;
        link: string;
    };
    formAction?: string;
    formMethod?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'stacked',
    emailPlaceholder: 'Enter your email',
    submitButtonText: 'Subscribe',
    formMethod: 'POST',
});

function handleSubmit(event: {
    valid: boolean;
    values: Record<string, any>;
    errors: Record<string, any>;
    states: Record<string, any>;
    reset: () => void;
}): void {
    if (!event.valid) {
        return;
    }

    // If formAction is provided, submit the form programmatically
    if (props.formAction) {
        const form = document.createElement('form');
        form.method = props.formMethod || 'POST';
        form.action = props.formAction;
        const emailInput = document.createElement('input');
        emailInput.type = 'hidden';
        emailInput.name = 'email';
        emailInput.value = event.values.email;
        form.appendChild(emailInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<template>
    <div class="py-16 sm:py-24 lg:py-32">
        <!-- Justified variant: grid layout -->
        <div
            v-if="variant === 'justified'"
            class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 lg:grid-cols-12 lg:gap-8 lg:px-8"
        >
            <h2
                class="max-w-xl text-3xl font-semibold tracking-tight text-balance text-surface-900 sm:text-4xl"
            >
                {{ title }}
            </h2>
            <div class="w-full max-w-md lg:col-span-5 lg:pt-2">
                <UiForm :initialValues="{ email: '' }" @submit="handleSubmit">
                    <div class="flex gap-x-4">
                        <label for="email" class="sr-only">Email address</label>
                        <UiFormFieldInput
                            name="email"
                            type="email"
                            autocomplete="email"
                            :placeholder="emailPlaceholder"
                            required
                            class="min-w-0 flex-auto"
                        />
                        <UiButton
                            type="submit"
                            severity="primary"
                            class="flex-none"
                        >
                            {{ submitButtonText }}
                        </UiButton>
                    </div>
                </UiForm>
                <p
                    v-if="privacyPolicy"
                    class="mt-4 text-sm/6 text-surface-900"
                >
                    {{ privacyPolicy.text }}
                    <a
                        :href="privacyPolicy.link"
                        class="font-semibold whitespace-nowrap text-primary-500 hover:text-primary-400"
                        >privacy policy</a
                    >.
                </p>
            </div>
        </div>

        <!-- Stacked variant: vertical layout -->
        <div v-else class="mx-auto max-w-7xl px-6 lg:px-8">
            <h2
                class="max-w-2xl text-3xl font-semibold tracking-tight text-balance text-surface-900 sm:text-4xl"
            >
                {{ title }}
            </h2>
            <div class="mt-10 max-w-md">
                <UiForm :initialValues="{ email: '' }" @submit="handleSubmit">
                    <div class="flex gap-x-4">
                        <label for="email" class="sr-only">Email address</label>
                        <UiFormFieldInput
                            name="email"
                            type="email"
                            autocomplete="email"
                            :placeholder="emailPlaceholder"
                            required
                            class="min-w-0 flex-auto"
                        />
                        <UiButton
                            type="submit"
                            severity="primary"
                            class="flex-none"
                        >
                            {{ submitButtonText }}
                        </UiButton>
                    </div>
                </UiForm>
                <p
                    v-if="privacyPolicy"
                    class="mt-4 text-sm/6 text-surface-900"
                >
                    {{ privacyPolicy.text }}
                    <a
                        :href="privacyPolicy.link"
                        class="font-semibold whitespace-nowrap text-primary-500 hover:text-primary-400"
                        >privacy policy</a
                    >.
                </p>
            </div>
        </div>
    </div>
</template>
