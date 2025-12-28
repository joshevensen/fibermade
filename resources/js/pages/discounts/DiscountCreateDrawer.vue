<script setup lang="ts">
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldDatePicker from '@/components/ui/UiFormFieldDatePicker.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import { store } from '@/actions/App/Http/Controllers/DiscountController';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { enumToOptions } from '@/utils/enumOptions';
import { router } from '@inertiajs/vue3';

// Enum cases - these match the PHP enums
const discountTypeCases = [
    { name: 'OrderThresholdFreeShipping', value: 'order_threshold_free_shipping' },
    { name: 'QuantityPerSkein', value: 'quantity_per_skein' },
    { name: 'Percentage', value: 'percentage' },
    { name: 'ManualFreeShipping', value: 'manual_free_shipping' },
    { name: 'TimeBoxed', value: 'time_boxed' },
];

const discountTypeOptions = enumToOptions(discountTypeCases);

interface Props {
    visible: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

function closeDrawer(): void {
    emit('update:visible', false);
}

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
        closeDrawer();
        router.reload({ only: ['discounts'] });
    },
});
</script>

<template>
    <UiDrawer
        :visible="visible"
        position="right"
        class="!w-[30rem]"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <h2 class="text-xl font-semibold">Create Discount</h2>
        </template>

        <div class="p-4">
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
        </div>
    </UiDrawer>
</template>

