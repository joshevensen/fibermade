<script setup lang="ts">
import {
    destroy as destroyColorway,
    update,
} from '@/actions/App/Http/Controllers/ColorwayController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import { useToast } from '@/composables/useToast';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import InventoryQuantityInput from '@/pages/creator/inventory/components/InventoryQuantityInput.vue';
import { update as updateCollections } from '@/routes/colorways/collections';
import { router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

interface Props {
    colorway: {
        id: number;
        name: string;
        description?: string | null;
        technique?: string | null;
        colors?: string[] | null;
        per_pan: number;
        recipe?: string | null;
        notes?: string | null;
        status: string;
    };
    collections: Array<{
        id: number;
        name: string;
    }>;
    allCollections: Array<{
        id: number;
        name: string;
    }>;
    bases: Array<{
        id: number;
        code: string;
        descriptor: string;
        quantity: number;
        inventory_id: number | null;
    }>;
    colorwayStatusOptions: Array<{ label: string; value: string }>;
    techniqueOptions: Array<{ label: string; value: string }>;
    colorOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const { requireDelete } = useConfirm();
const { showSuccess } = useToast();

const showCollectionDialog = ref(false);
const selectedCollectionIds = ref<number[]>([]);

// Track local quantities for total calculation
const localQuantities = reactive<Record<string, number>>({});

// Initialize local quantities from props
props.bases.forEach((base) => {
    const key = `${props.colorway.id}-${base.id}`;
    localQuantities[key] = base.quantity;
});

// Watch for prop changes to sync local quantities
watch(
    () => props.bases,
    (newBases) => {
        newBases.forEach((base) => {
            const key = `${props.colorway.id}-${base.id}`;
            localQuantities[key] = base.quantity;
        });
    },
    { deep: true },
);

function getTotalQuantity(): number {
    return props.bases.reduce((total, base) => {
        const key = `${props.colorway.id}-${base.id}`;
        return total + (localQuantities[key] ?? base.quantity);
    }, 0);
}

function handleQuantityChange(baseId: number, newQuantity: number): void {
    const key = `${props.colorway.id}-${baseId}`;
    localQuantities[key] = newQuantity;
}

const initialValues = {
    name: props.colorway.name || '',
    description: props.colorway.description || null,
    technique: props.colorway.technique || null,
    colors: props.colorway.colors || null,
    per_pan: props.colorway.per_pan ?? null,
    recipe: props.colorway.recipe || null,
    notes: props.colorway.notes || null,
    status: props.colorway.status || null,
};

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.colorway.id),
    initialValues,
    successMessage: 'Colorway updated successfully.',
    onSuccess: () => {
        router.visit('/colorways');
    },
});

function openCollectionDialog(): void {
    selectedCollectionIds.value = [...props.collections.map((c) => c.id)];
    showCollectionDialog.value = true;
}

function closeCollectionDialog(): void {
    showCollectionDialog.value = false;
    selectedCollectionIds.value = [];
}

function toggleCollection(collectionId: number, checked: boolean): void {
    if (checked) {
        if (!selectedCollectionIds.value.includes(collectionId)) {
            selectedCollectionIds.value.push(collectionId);
        }
    } else {
        const index = selectedCollectionIds.value.indexOf(collectionId);
        if (index > -1) {
            selectedCollectionIds.value.splice(index, 1);
        }
    }
}

function handleUpdateCollections(): void {
    router.patch(
        updateCollections.url(props.colorway.id),
        { collection_ids: selectedCollectionIds.value },
        {
            onSuccess: () => {
                showSuccess('Collections updated successfully.');
                closeCollectionDialog();
            },
        },
    );
}

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.colorway.name}?`,
        onAccept: () => {
            router.delete(destroyColorway.url(props.colorway.id));
        },
    });
}
</script>

<template>
    <CreatorLayout page-title="Edit Colorway">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm :initial-values="initialValues" @submit="onSubmit">
                        <UiFormField
                            name="status"
                            label="Status"
                            :server-error="form.errors.status"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiSelectButton
                                    v-bind="fieldProps"
                                    :options="colorwayStatusOptions"
                                    fluid
                                />
                            </template>
                        </UiFormField>

                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Colorway name"
                            :server-error="form.errors.name"
                            required
                        />

                        <div
                            class="grid w-full gap-4"
                            style="grid-template-columns: auto 1fr auto"
                        >
                            <UiFormFieldSelect
                                name="technique"
                                label="Technique"
                                :options="techniqueOptions"
                                placeholder="Select technique"
                                :server-error="form.errors.technique"
                                show-clear
                            />

                            <UiFormFieldMultiSelect
                                name="colors"
                                label="Colors"
                                :options="colorOptions"
                                placeholder="Select colors"
                                :server-error="form.errors.colors"
                            />

                            <UiFormFieldInputNumber
                                name="per_pan"
                                label="Per Pan"
                                placeholder="1-6"
                                :min="1"
                                :max="6"
                                :server-error="form.errors.per_pan"
                                required
                            />
                        </div>

                        <UiFormField
                            name="description"
                            label="Description"
                            :server-error="form.errors.description"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiEditor
                                    v-bind="fieldProps"
                                    placeholder="Colorway description"
                                />
                            </template>
                        </UiFormField>

                        <UiFormField
                            name="recipe"
                            label="Recipe"
                            :server-error="form.errors.recipe"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiEditor
                                    v-bind="fieldProps"
                                    placeholder="Colorway recipe"
                                />
                            </template>
                        </UiFormField>

                        <UiFormField
                            name="notes"
                            label="Notes"
                            :server-error="form.errors.notes"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiEditor
                                    v-bind="fieldProps"
                                    placeholder="Additional notes"
                                />
                            </template>
                        </UiFormField>

                        <UiButton type="submit" :loading="form.processing">
                            Update Colorway
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #title>
                        <h3 class="text-lg font-semibold">Inventory</h3>
                    </template>
                    <template #content>
                        <div v-if="props.bases.length === 0" class="py-8">
                            <p class="text-center text-surface-500">
                                No bases available
                            </p>
                        </div>
                        <div v-else class="space-y-4">
                            <div class="text-sm text-surface-600">
                                Total:
                                <span class="font-semibold">{{
                                    getTotalQuantity()
                                }}</span>
                            </div>
                            <div class="space-y-3">
                                <InventoryQuantityInput
                                    v-for="base in props.bases"
                                    :key="base.id"
                                    :colorway-id="props.colorway.id"
                                    :base-id="base.id"
                                    :base-name="base.descriptor"
                                    :initial-quantity="base.quantity"
                                    size="large"
                                    @quantity-changed="
                                        handleQuantityChange(base.id, $event)
                                    "
                                />
                            </div>
                        </div>
                    </template>
                </UiCard>

                <UiCard>
                    <template #title>
                        <h3 class="text-lg font-semibold">Collections</h3>
                    </template>
                    <template #content>
                        <div v-if="props.collections.length === 0" class="py-8">
                            <p class="text-center text-surface-500">
                                No collections found
                            </p>
                        </div>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="collection in props.collections"
                                :key="collection.id"
                                class="flex items-center justify-between gap-4 rounded-lg border border-surface-200 p-3 transition-colors hover:bg-surface-50"
                            >
                                <span class="font-medium text-surface-700">
                                    {{ collection.name }}
                                </span>
                            </li>
                        </ul>
                        <UiButton
                            type="button"
                            class="mt-4 w-full"
                            @click="openCollectionDialog"
                        >
                            Update Collections
                        </UiButton>
                    </template>
                </UiCard>

                <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this colorway will permanently
                                    remove all associated data. This action
                                    cannot be undone.
                                </p>
                            </div>
                            <UiButton
                                type="button"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="handleDelete($event)"
                            >
                                Delete Colorway
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </CreatorLayout>

    <UiDialog
        v-model:visible="showCollectionDialog"
        header="Update Collections"
        modal
        :closable="true"
        @hide="closeCollectionDialog"
    >
        <div class="space-y-4">
            <div v-if="props.allCollections.length === 0" class="py-8">
                <p class="text-center text-surface-500">
                    No collections available
                </p>
            </div>
            <ul v-else class="max-h-96 space-y-2 overflow-y-auto">
                <li
                    v-for="collection in props.allCollections"
                    :key="collection.id"
                    class="flex items-center gap-3 rounded-lg border border-surface-200 p-3 transition-colors hover:bg-surface-50"
                >
                    <UiCheckbox
                        :model-value="
                            selectedCollectionIds.includes(collection.id)
                        "
                        binary
                        @update:model-value="
                            toggleCollection(collection.id, $event)
                        "
                    />
                    <span class="font-medium text-surface-700">
                        {{ collection.name }}
                    </span>
                </li>
            </ul>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <UiButton
                    type="button"
                    severity="secondary"
                    outlined
                    @click="closeCollectionDialog"
                >
                    Cancel
                </UiButton>
                <UiButton type="button" @click="handleUpdateCollections">
                    Update Collections
                </UiButton>
            </div>
        </template>
    </UiDialog>
</template>
