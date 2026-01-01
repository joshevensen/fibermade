<script setup lang="ts">
import {
    destroy as destroyColorway,
    update,
} from '@/actions/App/Http/Controllers/ColorwayController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldMultiSelect from '@/components/ui/UiFormFieldMultiSelect.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

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
    colorwayStatusOptions: Array<{ label: string; value: string }>;
    techniqueOptions: Array<{ label: string; value: string }>;
    colorOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const { requireDelete } = useConfirm();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.colorway.id),
    initialValues: {
        name: props.colorway.name || '',
        description: props.colorway.description || null,
        technique: props.colorway.technique || null,
        colors: props.colorway.colors || null,
        per_pan: props.colorway.per_pan ?? null,
        recipe: props.colorway.recipe || null,
        notes: props.colorway.notes || null,
        status: props.colorway.status || null,
    },
    successMessage: 'Colorway updated successfully.',
    onSuccess: () => {
        router.visit('/colorways');
    },
});

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
    <AppLayout page-title="Edit Colorway">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm
                        :initial-values="{
                            name: props.colorway.name || '',
                            description: props.colorway.description || null,
                            technique: props.colorway.technique || null,
                            colors: props.colorway.colors || null,
                            per_pan: props.colorway.per_pan ?? null,
                            recipe: props.colorway.recipe || null,
                            notes: props.colorway.notes || null,
                            status: props.colorway.status || null,
                        }"
                        @submit="onSubmit"
                    >
                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Colorway name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormFieldTextarea
                            name="description"
                            label="Description"
                            placeholder="Colorway description"
                            :server-error="form.errors.description"
                        />

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

                        <UiFormFieldTextarea
                            name="recipe"
                            label="Recipe"
                            placeholder="Colorway recipe"
                            :server-error="form.errors.recipe"
                        />

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
    </AppLayout>
</template>
