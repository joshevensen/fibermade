<script setup lang="ts">
import {
    destroy as destroyCollection,
    update,
} from '@/actions/App/Http/Controllers/CollectionController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiCheckbox from '@/components/ui/UiCheckbox.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useToast } from '@/composables/useToast';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { update as updateColorways } from '@/routes/collections/colorways';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    collection: {
        id: number;
        name: string;
        description?: string | null;
    };
    colorways: Array<{
        id: number;
        name: string;
        status: string;
    }>;
    allColorways: Array<{
        id: number;
        name: string;
        status: string;
    }>;
}

const props = defineProps<Props>();
const { requireDelete } = useConfirm();
const { showSuccess } = useToast();

const showColorwayDialog = ref(false);
const selectedColorwayIds = ref<number[]>([]);

function openColorwayDialog(): void {
    selectedColorwayIds.value = [...props.colorways.map((cw) => cw.id)];
    showColorwayDialog.value = true;
}

function closeColorwayDialog(): void {
    showColorwayDialog.value = false;
    selectedColorwayIds.value = [];
}

function toggleColorway(colorwayId: number, checked: boolean): void {
    if (checked) {
        if (!selectedColorwayIds.value.includes(colorwayId)) {
            selectedColorwayIds.value.push(colorwayId);
        }
    } else {
        const index = selectedColorwayIds.value.indexOf(colorwayId);
        if (index > -1) {
            selectedColorwayIds.value.splice(index, 1);
        }
    }
}

function handleUpdateColorways(): void {
    router.patch(
        updateColorways.url(props.collection.id),
        { colorway_ids: selectedColorwayIds.value },
        {
            onSuccess: () => {
                showSuccess('Colorways updated successfully.');
                closeColorwayDialog();
            },
        },
    );
}

function formatEnum(value: string | null | undefined): string {
    if (!value) {
        return '';
    }
    return value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

const initialValues = {
    name: props.collection.name || '',
    description: props.collection.description || null,
};

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.collection.id),
    initialValues,
    successMessage: 'Collection updated successfully.',
    onSuccess: () => {
        router.visit('/collections');
    },
});

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.collection.name}?`,
        onAccept: () => {
            router.delete(destroyCollection.url(props.collection.id));
        },
    });
}
</script>

<template>
    <CreatorLayout page-title="Edit Collection">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm :initial-values="initialValues" @submit="onSubmit">
                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Collection name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormField
                            name="description"
                            label="Description"
                            :server-error="form.errors.description"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiEditor
                                    v-bind="fieldProps"
                                    placeholder="Collection description"
                                />
                            </template>
                        </UiFormField>

                        <UiButton type="submit" :loading="form.processing">
                            Update Collection
                        </UiButton>
                    </UiForm>
                </template>
            </UiCard>
        </template>

        <template #side>
            <div class="flex flex-col gap-4">
                <UiCard>
                    <template #title>
                        <h3 class="text-lg font-semibold">Colorways</h3>
                    </template>
                    <template #content>
                        <div v-if="props.colorways.length === 0" class="py-8">
                            <p class="text-center text-surface-500">
                                No colorways found
                            </p>
                        </div>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="colorway in props.colorways"
                                :key="colorway.id"
                                class="flex items-center justify-between gap-4 rounded-lg border border-surface-200 p-3 transition-colors hover:bg-surface-50"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="font-medium text-surface-700">
                                        {{ colorway.name }}
                                    </span>
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                colorway.status === 'active',
                                            'bg-yellow-100 text-yellow-800':
                                                colorway.status === 'idea',
                                            'bg-gray-100 text-gray-800':
                                                colorway.status === 'retired',
                                        }"
                                    >
                                        {{ formatEnum(colorway.status) }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                        <UiButton
                            type="button"
                            class="mt-4 w-full"
                            @click="openColorwayDialog"
                        >
                            Update Colorways
                        </UiButton>
                    </template>
                </UiCard>

                <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this collection will permanently
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
                                Delete Collection
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </CreatorLayout>

    <UiDialog
        v-model:visible="showColorwayDialog"
        header="Update Colorways"
        modal
        :closable="true"
        @hide="closeColorwayDialog"
    >
        <div class="space-y-4">
            <div v-if="props.allColorways.length === 0" class="py-8">
                <p class="text-center text-surface-500">
                    No colorways available
                </p>
            </div>
            <ul v-else class="max-h-96 space-y-2 overflow-y-auto">
                <li
                    v-for="colorway in props.allColorways"
                    :key="colorway.id"
                    class="flex items-center gap-3 rounded-lg border border-surface-200 p-3 transition-colors hover:bg-surface-50"
                >
                    <UiCheckbox
                        :model-value="selectedColorwayIds.includes(colorway.id)"
                        binary
                        @update:model-value="
                            toggleColorway(colorway.id, $event)
                        "
                    />
                    <div class="flex flex-1 items-center justify-between gap-3">
                        <span class="font-medium text-surface-700">
                            {{ colorway.name }}
                        </span>
                        <span
                            class="rounded-full px-2 py-1 text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-800':
                                    colorway.status === 'active',
                                'bg-yellow-100 text-yellow-800':
                                    colorway.status === 'idea',
                                'bg-gray-100 text-gray-800':
                                    colorway.status === 'retired',
                            }"
                        >
                            {{ formatEnum(colorway.status) }}
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <UiButton
                    type="button"
                    severity="secondary"
                    outlined
                    @click="closeColorwayDialog"
                >
                    Cancel
                </UiButton>
                <UiButton type="button" @click="handleUpdateColorways">
                    Update Colorways
                </UiButton>
            </div>
        </template>
    </UiDialog>
</template>
