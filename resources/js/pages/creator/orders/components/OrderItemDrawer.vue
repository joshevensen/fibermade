<script setup lang="ts">
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/OrderItemController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    visible: boolean;
    orderId: number;
    orderItem?: {
        id: number;
        colorway_id: number;
        base_id: number;
        quantity: number;
        unit_price?: number | null;
        line_total?: number | null;
    } | null;
    colorways: Array<{ id: number; name: string }>;
    bases: Array<{ id: number; code: string; descriptor: string }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

function closeDrawer(): void {
    emit('update:visible', false);
}

const isEditMode = computed(() => !!props.orderItem);

const colorwayOptions = computed(() =>
    props.colorways.map((colorway) => ({
        label: colorway.name,
        value: colorway.id.toString(),
    })),
);

const baseOptions = computed(() =>
    props.bases.map((base) => ({
        label: `${base.code} - ${base.descriptor}`,
        value: base.id.toString(),
    })),
);

const initialValues = computed(() => {
    if (isEditMode.value && props.orderItem) {
        return {
            order_id: props.orderId,
            colorway_id: props.orderItem.colorway_id?.toString() || null,
            base_id: props.orderItem.base_id?.toString() || null,
            quantity: props.orderItem.quantity || 1,
            unit_price: props.orderItem.unit_price || null,
            line_total: props.orderItem.line_total || null,
        };
    }

    return {
        order_id: props.orderId,
        colorway_id: null,
        base_id: null,
        quantity: 1,
        unit_price: null,
        line_total: null,
    };
});

const { form, onSubmit } = useFormSubmission({
    route: () =>
        isEditMode.value && props.orderItem
            ? update(props.orderItem.id)
            : store(),
    initialValues: initialValues.value,
    transform: (values) => {
        const transformed = { ...values };
        // Convert IDs back to integers
        if (transformed.order_id) {
            transformed.order_id = parseInt(transformed.order_id, 10);
        }
        if (transformed.colorway_id) {
            transformed.colorway_id = parseInt(transformed.colorway_id, 10);
        }
        if (transformed.base_id) {
            transformed.base_id = parseInt(transformed.base_id, 10);
        }
        if (transformed.quantity) {
            transformed.quantity = parseInt(transformed.quantity, 10);
        }
        // Convert line_total to number if it exists
        if (transformed.line_total) {
            transformed.line_total = parseFloat(transformed.line_total);
        }
        return transformed;
    },
    successMessage: isEditMode.value
        ? 'Order item updated successfully.'
        : 'Order item created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['order'] });
    },
});

// Auto-calculate line_total when quantity or unit_price changes
watch(
    () => [form.values.quantity, form.values.unit_price],
    ([quantity, unitPrice]) => {
        if (quantity && unitPrice) {
            const lineTotal = Number(quantity) * Number(unitPrice);
            form.set('line_total', parseFloat(lineTotal.toFixed(2)));
        } else {
            form.set('line_total', null);
        }
    },
    { deep: true },
);
</script>

<template>
    <UiDrawer
        :visible="visible"
        position="right"
        class="!w-[30rem]"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <h2 class="text-xl font-semibold">
                {{ isEditMode ? 'Edit Order Item' : 'Add Order Item' }}
            </h2>
        </template>

        <div class="p-4">
            <UiForm :initial-values="initialValues" @submit="onSubmit">
                <UiFormFieldSelect
                    name="colorway_id"
                    label="Colorway"
                    :options="colorwayOptions"
                    placeholder="Select colorway"
                    :server-error="form.errors.colorway_id"
                    required
                />

                <UiFormFieldSelect
                    name="base_id"
                    label="Base"
                    :options="baseOptions"
                    placeholder="Select base"
                    :server-error="form.errors.base_id"
                    required
                />

                <UiFormFieldInputNumber
                    name="quantity"
                    label="Quantity"
                    :min="1"
                    :server-error="form.errors.quantity"
                    required
                />

                <UiFormFieldInputNumber
                    name="unit_price"
                    label="Unit Price"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.unit_price"
                />

                <UiFormFieldInputNumber
                    name="line_total"
                    label="Line Total"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.line_total"
                    disabled
                />

                <UiButton type="submit" :loading="form.processing">
                    {{ isEditMode ? 'Update Item' : 'Add Item' }}
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
