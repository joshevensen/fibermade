<script setup lang="ts">
import { updateQuantity } from '@/actions/App/Http/Controllers/InventoryController';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';

interface Props {
    colorwayId: number;
    baseId: number;
    baseName: string;
    initialQuantity: number;
    size?: 'small' | 'large';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'small',
});
const emit = defineEmits<{
    quantityChanged: [quantity: number];
}>();

const toast = useToast();

const quantity = ref<number>(props.initialQuantity);
const isSaving = ref<boolean>(false);
const isProcessing = ref<boolean>(false);

// Sync with prop changes
watch(
    () => props.initialQuantity,
    (newQuantity) => {
        if (!isProcessing.value && !isSaving.value) {
            quantity.value = newQuantity;
        }
    },
);

function handleUpdate(newQuantity: number | null): void {
    const newValue = newQuantity ?? 0;

    // Prevent any processing if already processing or saving
    if (isProcessing.value || isSaving.value) {
        return;
    }

    // Don't update if quantity hasn't changed
    if (newValue === quantity.value) {
        return;
    }

    // Mark as processing immediately - this prevents rapid-fire calls
    isProcessing.value = true;
    const previousQuantity = quantity.value;

    // Update local value optimistically
    quantity.value = newValue;
    isSaving.value = true;

    // Emit event for parent to update totals
    emit('quantityChanged', newValue);

    // Save to backend
    router.patch(
        updateQuantity.url(),
        {
            colorway_id: props.colorwayId,
            base_id: props.baseId,
            quantity: newValue,
        },
        {
            preserveScroll: true,
            only: ['colorways'],
            onSuccess: () => {
                isSaving.value = false;
                isProcessing.value = false;
            },
            onError: () => {
                // Revert on error
                quantity.value = previousQuantity;
                emit('quantityChanged', previousQuantity);
                isSaving.value = false;
                isProcessing.value = false;
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to update inventory',
                    life: 3000,
                });
            },
        },
    );
}
</script>

<template>
    <div
        v-if="props.size === 'small'"
        class="flex flex-shrink-0 flex-col gap-1"
    >
        <label class="text-xs font-medium text-surface-600">
            {{ baseName }}
        </label>
        <UiInputNumber
            :model-value="quantity"
            :min="0"
            show-buttons
            button-layout="horizontal"
            size="small"
            :readonly="isSaving || isProcessing"
            class="inventory-input-number"
            @update:model-value="handleUpdate($event)"
        />
    </div>
    <div v-else class="flex items-center justify-between gap-4">
        <label class="text-sm font-medium text-surface-700">
            {{ baseName }}
        </label>
        <UiInputNumber
            :model-value="quantity"
            :min="0"
            show-buttons
            button-layout="horizontal"
            :readonly="isSaving || isProcessing"
            class="inventory-input-number w-32"
            @update:model-value="handleUpdate($event)"
        />
    </div>
</template>
