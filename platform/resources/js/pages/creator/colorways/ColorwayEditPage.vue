<script setup lang="ts">
import {
    destroy as destroyColorway,
    destroyMedia,
    index as colorwaysIndex,
    pushToShopify,
    storeMedia,
    update,
} from '@/actions/App/Http/Controllers/ColorwayController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiFileUpload from '@/components/ui/UiFileUpload.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiImage from '@/components/ui/UiImage.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
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
    has_shopify: boolean;
    media: Array<{ id: number; url: string; file_name: string }>;
}

const props = defineProps<Props>();
const { requireDelete } = useConfirm();
const { showSuccess, showError } = useToast();

const pushingToShopify = ref(false);
const uploadingMedia = ref(false);

async function handleMediaUpload(event: { files: File[] }): Promise<void> {
    uploadingMedia.value = true;
    try {
        const formData = new FormData();
        event.files.forEach((file) => formData.append('images[]', file));

        const response = await fetch(storeMedia.url(props.colorway.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            body: formData,
        });
        if (!response.ok) {
            throw new Error('Upload failed.');
        }
        router.reload({ only: ['media'] });
    } catch {
        showError('Could not upload image(s). Please try again.');
    } finally {
        uploadingMedia.value = false;
    }
}

function handleDeleteMedia(event: Event, mediaId: number): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: 'Are you sure you want to delete this image?',
        onAccept: () => {
            router.delete(
                destroyMedia.url({
                    colorway: props.colorway.id,
                    media: mediaId,
                }),
                { only: ['media'] },
            );
        },
    });
}

async function handlePushToShopify(): Promise<void> {
    pushingToShopify.value = true;
    try {
        const response = await fetch(pushToShopify.url(props.colorway.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
        });
        if (!response.ok) {
            throw new Error('Push failed.');
        }
        showSuccess('Colorway queued for push to Shopify.');
    } catch {
        showError('Could not push to Shopify. Please try again.');
    } finally {
        pushingToShopify.value = false;
    }
}

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
        router.visit(colorwaysIndex.url());
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
        <template #header-actions>
            <UiButton
                v-if="has_shopify"
                type="button"
                outlined
                :loading="pushingToShopify"
                @click="handlePushToShopify"
            >
                Push to Shopify
            </UiButton>
            <UiButton
                type="submit"
                form="colorway-form"
                :loading="form.processing"
            >
                Update Colorway
            </UiButton>
        </template>

        <template #default>
            <!-- Two-column layout -->
            <div class="flex flex-col gap-4 lg:flex-row lg:pr-4">
                <!-- Left: form -->
                <div class="flex-[0_0_60%]">
                    <UiCard>
                        <template #content>
                            <UiForm
                                id="colorway-form"
                                :initial-values="initialValues"
                                @submit="onSubmit"
                            >
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
                                    style="
                                        grid-template-columns: auto 1fr auto;
                                    "
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
                                    <template
                                        #default="{ props: fieldProps }"
                                    >
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
                                    <template
                                        #default="{ props: fieldProps }"
                                    >
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
                                    <template
                                        #default="{ props: fieldProps }"
                                    >
                                        <UiEditor
                                            v-bind="fieldProps"
                                            placeholder="Additional notes"
                                        />
                                    </template>
                                </UiFormField>
                            </UiForm>
                        </template>
                    </UiCard>
                </div>

                <!-- Right: sidebar cards -->
                <div class="flex flex-[0_0_40%] flex-col gap-4">
                    <!-- Media card -->
                    <UiCard>
                        <template #title>
                            <h3 class="text-lg font-semibold">Media</h3>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div
                                    v-if="props.media.length > 0"
                                    class="grid grid-cols-2 gap-3"
                                >
                                    <div
                                        v-for="item in props.media"
                                        :key="item.id"
                                        class="flex flex-col items-center gap-1"
                                    >
                                        <UiImage
                                            :src="item.url"
                                            :alt="item.file_name"
                                            preview
                                            class="aspect-square w-full overflow-hidden rounded-lg"
                                            image-class="h-full w-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            class="text-xs text-red-500 hover:text-red-700"
                                            @click="
                                                handleDeleteMedia($event, item.id)
                                            "
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <p
                                    v-else
                                    class="py-4 text-center text-sm text-surface-500"
                                >
                                    No images yet
                                </p>
                                <div class="media-upload">
                                    <UiFileUpload
                                        name="images[]"
                                        accept="image/*"
                                        :multiple="true"
                                        :custom-upload="true"
                                        :auto="true"
                                        :show-upload-button="false"
                                        :show-cancel-button="false"
                                        :disabled="uploadingMedia"
                                        choose-label="Upload Images"
                                        :choose-button-props="{ outlined: true }"
                                        class="w-full"
                                        @uploader="handleMediaUpload"
                                    >
                                        <template #content />
                                    </UiFileUpload>
                                </div>
                            </div>
                        </template>
                    </UiCard>

                    <UiCard>
                        <template #title>
                            <h3 class="text-lg font-semibold">Inventory</h3>
                        </template>
                        <template #content>
                            <div
                                v-if="props.bases.length === 0"
                                class="py-8"
                            >
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
                                            handleQuantityChange(
                                                base.id,
                                                $event,
                                            )
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
                            <div
                                v-if="props.collections.length === 0"
                                class="py-8"
                            >
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
                                outlined
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
                                <p class="text-sm text-surface-600">
                                    Deleting this colorway will permanently
                                    remove all associated data. This action
                                    cannot be undone.
                                </p>
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

<style scoped>
.media-upload :deep(.p-fileupload-content) {
    display: none;
}
</style>
