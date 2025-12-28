<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/StoreController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiInputGroup from '@/components/ui/UiInputGroup.vue';
import UiInputGroupAddon from '@/components/ui/UiInputGroupAddon.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import UiTextarea from '@/components/ui/UiTextarea.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

// Enum cases - these match the PHP enum
const storeVendorStatusCases = [
    { name: 'Active', value: 'active' },
    { name: 'Paused', value: 'paused' },
    { name: 'Ended', value: 'ended' },
];

const statusOptions = storeVendorStatusCases.map((item) => ({
    label: item.name,
    value: item.value,
}));

interface Props {
    visible: boolean;
    statusOptions?: Array<{ label: string; value: string }>;
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
        email: '',
        owner_name: null,
        address_line_1: '',
        address_line_2: null,
        city: '',
        state: '',
        zip: '',
        country: '',
        discount_rate: null,
        minimum_order_quantity: null,
        minimum_order_value: null,
        payment_terms: null,
        lead_time_days: null,
        allows_preorders: false,
        status: null,
        notes: null,
    },
    successMessage: 'Store created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['stores'] });
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
            <h2 class="text-xl font-semibold">Create Store</h2>
        </template>

        <div class="p-4">
            <UiForm @submit="onSubmit">
                <UiFormFieldInput
                    name="name"
                    label="Store Name"
                    placeholder="Store name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldInput
                    name="email"
                    label="Email"
                    type="email"
                    placeholder="store@example.com"
                    :server-error="form.errors.email"
                    required
                />

                <UiFormFieldInput
                    name="owner_name"
                    label="Owner Name"
                    placeholder="Owner name"
                    :server-error="form.errors.owner_name"
                />

                <UiFormFieldInput
                    name="address_line_1"
                    label="Address Line 1"
                    placeholder="Street address"
                    :server-error="form.errors.address_line_1"
                    required
                />

                <UiFormFieldInput
                    name="address_line_2"
                    label="Address Line 2"
                    placeholder="Apartment, suite, etc."
                    :server-error="form.errors.address_line_2"
                />

                <div class="grid grid-cols-2 gap-4">
                    <UiFormFieldInput
                        name="city"
                        label="City"
                        placeholder="City"
                        :server-error="form.errors.city"
                        required
                    />

                    <UiFormFieldInput
                        name="state"
                        label="State"
                        placeholder="State"
                        :server-error="form.errors.state"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <UiFormFieldInput
                        name="zip"
                        label="ZIP Code"
                        placeholder="ZIP code"
                        :server-error="form.errors.zip"
                        required
                    />

                    <UiFormFieldInput
                        name="country"
                        label="Country"
                        placeholder="Country"
                        :server-error="form.errors.country"
                        required
                    />
                </div>

                <UiFormField
                    name="status"
                    label="Status"
                    :server-error="form.errors.status"
                >
                    <template #default="{ props: fieldProps }">
                        <UiSelectButton
                            v-bind="fieldProps"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            size="small"
                            fluid
                        />
                    </template>
                </UiFormField>

                <UiFormField
                    name="discount_rate"
                    label="Discount Rate (%)"
                    :server-error="form.errors.discount_rate"
                >
                    <template #default="{ props: fieldProps }">
                        <UiInputGroup>
                            <UiInputNumber
                                v-bind="fieldProps"
                                :min="0"
                                :max="100"
                                :step="0.01"
                            />
                            <UiInputGroupAddon>%</UiInputGroupAddon>
                        </UiInputGroup>
                    </template>
                </UiFormField>

                <UiFormFieldInputNumber
                    name="minimum_order_quantity"
                    label="Minimum Order Quantity"
                    :min="1"
                    :server-error="form.errors.minimum_order_quantity"
                />

                <UiFormField
                    name="minimum_order_value"
                    label="Minimum Order Value"
                    :server-error="form.errors.minimum_order_value"
                >
                    <template #default="{ props: fieldProps }">
                        <UiInputGroup>
                            <UiInputGroupAddon>$</UiInputGroupAddon>
                            <UiInputNumber
                                v-bind="fieldProps"
                                :min="0"
                                :max="99999999.99"
                                :step="0.01"
                            />
                        </UiInputGroup>
                    </template>
                </UiFormField>

                <UiFormFieldInput
                    name="payment_terms"
                    label="Payment Terms"
                    placeholder="e.g., Net 30, Net 60"
                    :server-error="form.errors.payment_terms"
                />

                <UiFormFieldInputNumber
                    name="lead_time_days"
                    label="Lead Time (Days)"
                    :min="0"
                    :server-error="form.errors.lead_time_days"
                />

                <UiFormField
                    name="allows_preorders"
                    label="Allows Preorders"
                    :server-error="form.errors.allows_preorders"
                >
                    <template #default="{ props: fieldProps }">
                        <UiSelectButton
                            v-bind="fieldProps"
                            :options="[
                                { label: 'Yes', value: true },
                                { label: 'No', value: false },
                            ]"
                            option-label="label"
                            option-value="value"
                            size="small"
                            fluid
                        />
                    </template>
                </UiFormField>

                <UiFormField
                    name="notes"
                    label="Notes"
                    :server-error="form.errors.notes"
                >
                    <template #default="{ props: fieldProps }">
                        <UiTextarea
                            v-bind="fieldProps"
                            placeholder="Additional notes"
                            rows="4"
                        />
                    </template>
                </UiFormField>

                <UiButton type="submit" :loading="form.processing">
                    Create Store
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
