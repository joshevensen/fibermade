<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/DiscountController';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    discount: {
        id: number;
        name: string;
        type: string;
        code: string;
        parameters?: Record<string, any> | null;
        starts_at?: string | null;
        ends_at?: string | null;
        is_active: boolean;
        shopify_discount_id?: string | null;
    };
    discountTypeOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { BusinessIconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.discount.id),
    initialValues: {
        name: props.discount.name || '',
        type: props.discount.type || null,
        code: props.discount.code || '',
        parameters: props.discount.parameters
            ? JSON.stringify(props.discount.parameters, null, 2)
            : null,
        starts_at: props.discount.starts_at || null,
        ends_at: props.discount.ends_at || null,
        is_active: props.discount.is_active ?? false,
        shopify_discount_id: props.discount.shopify_discount_id || null,
    },
    transform: (values) => {
        const transformed = { ...values };
        // Try to parse parameters as JSON if it's a string
        if (
            typeof transformed.parameters === 'string' &&
            transformed.parameters.trim()
        ) {
            try {
                transformed.parameters = JSON.parse(transformed.parameters);
            } catch {
                // If parsing fails, keep as string or set to null
                transformed.parameters = null;
            }
        }
        return transformed;
    },
    successMessage: 'Discount updated successfully.',
    onSuccess: () => {
        router.visit('/discounts');
    },
});
</script>

<template>
    <AppLayout page-title="Edit Discount">
        <PageHeader
            heading="Edit Discount"
            :business-icon="BusinessIconList.Discounts"
        />

        <div class="mt-6 max-w-2xl">
            <UiCard>
                <template #content>
                    <UiForm @submit="onSubmit">
                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Discount name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormFieldSelect
                            name="type"
                            label="Type"
                            :options="discountTypeOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Select discount type"
                            :server-error="form.errors.type"
                            required
                        />

                        <UiFormFieldInput
                            name="code"
                            label="Code"
                            placeholder="Discount code"
                            :server-error="form.errors.code"
                            required
                        />

                        <UiFormFieldTextarea
                            name="parameters"
                            label="Parameters"
                            placeholder='JSON object, e.g., {"threshold": 100}'
                            :server-error="form.errors.parameters"
                            rows="4"
                        />

                        <UiFormFieldDatePicker
                            name="starts_at"
                            label="Starts At"
                            placeholder="Select start date"
                            :server-error="form.errors.starts_at"
                            show-icon
                        />

                        <UiFormFieldDatePicker
                            name="ends_at"
                            label="Ends At"
                            placeholder="Select end date"
                            :server-error="form.errors.ends_at"
                            show-icon
                        />

                        <UiFormFieldCheckbox
                            name="is_active"
                            label="Is Active"
                            :server-error="form.errors.is_active"
                            binary
                        />

                        <UiFormFieldInput
                            name="shopify_discount_id"
                            label="Shopify Discount ID"
                            placeholder="Shopify discount ID"
                            :server-error="form.errors.shopify_discount_id"
                        />

                        <div class="flex gap-4">
                            <UiButton type="submit" :loading="form.processing">
                                Update Discount
                            </UiButton>
                            <UiButton
                                type="button"
                                severity="secondary"
                                @click="router.visit('/discounts')"
                            >
                                Cancel
                            </UiButton>
                        </div>
                    </UiForm>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
