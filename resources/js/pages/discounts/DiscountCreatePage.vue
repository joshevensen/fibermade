<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import { store } from '@/actions/App/Http/Controllers/DiscountController';
import { index } from '@/actions/App/Http/Controllers/DiscountController';
import { useIcon } from '@/composables/useIcon';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

interface Props {
    discountTypeOptions: Array<{
        label: string;
        value: string;
    }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues: {
        name: '',
        type: null,
        code: '',
        starts_at: null,
        ends_at: null,
        is_active: false,
        shopify_discount_id: null,
    },
    successMessage: 'Discount created successfully.',
    onSuccess: () => {
        router.visit(index.url());
    },
});
</script>

<template>
    <AppLayout page-title="Create Discount">
        <PageHeader
            heading="Create Discount"
            :icon="IconList.Discounts"
        />

        <div class="mt-6">
            <UiCard>
                <template #title>Discount Information</template>
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

                        <UiButton
                            type="submit"
                            :loading="form.processing"
                        >
                            Create Discount
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
