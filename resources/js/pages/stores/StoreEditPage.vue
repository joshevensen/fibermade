<script setup lang="ts">
import {
    destroy as destroyStore,
    update,
} from '@/actions/App/Http/Controllers/StoreController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiInputGroup from '@/components/ui/UiInputGroup.vue';
import UiInputGroupAddon from '@/components/ui/UiInputGroupAddon.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import UiTextarea from '@/components/ui/UiTextarea.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    store: {
        id: number;
        name: string;
        email: string;
        owner_name?: string | null;
        address_line_1: string;
        address_line_2?: string | null;
        city: string;
        state: string;
        zip: string;
        country: string;
        discount_rate?: number | null;
        minimum_order_quantity?: number | null;
        minimum_order_value?: number | null;
        payment_terms?: string | null;
        lead_time_days?: number | null;
        allows_preorders: boolean;
        status: string;
        notes?: string | null;
    };
    statusOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const { requireDelete } = useConfirm();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.store.id),
    initialValues: {
        name: props.store.name || '',
        email: props.store.email || '',
        owner_name: props.store.owner_name || null,
        address_line_1: props.store.address_line_1 || '',
        address_line_2: props.store.address_line_2 || null,
        city: props.store.city || '',
        state: props.store.state || '',
        zip: props.store.zip || '',
        country: props.store.country || '',
        discount_rate: props.store.discount_rate || null,
        minimum_order_quantity: props.store.minimum_order_quantity || null,
        minimum_order_value: props.store.minimum_order_value || null,
        payment_terms: props.store.payment_terms || null,
        lead_time_days: props.store.lead_time_days || null,
        allows_preorders: props.store.allows_preorders || false,
        status: props.store.status || null,
        notes: props.store.notes || null,
    },
    successMessage: 'Store updated successfully.',
    onSuccess: () => {
        router.visit('/stores');
    },
});

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.store.name}?`,
        onAccept: () => {
            router.delete(destroyStore.url(props.store.id));
        },
    });
}
</script>

<template>
    <AppLayout page-title="Edit Store">
        <div class="mt-6 max-w-2xl">
            <UiCard>
                <template #content>
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

                        <UiDivider />

                        <h3 class="mb-4 text-lg font-semibold">Location</h3>

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

                        <UiDivider />

                        <h3 class="mb-4 text-lg font-semibold">
                            Vendor Settings
                        </h3>

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

                        <UiDivider />

                        <div class="flex gap-4">
                            <UiButton type="submit" :loading="form.processing">
                                Update Store
                            </UiButton>
                            <UiButton
                                type="button"
                                severity="secondary"
                                @click="router.visit('/stores')"
                            >
                                Cancel
                            </UiButton>
                        </div>

                        <UiDivider />

                        <UiButton
                            type="button"
                            severity="danger"
                            outlined
                            @click="handleDelete($event)"
                        >
                            Delete Store
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
