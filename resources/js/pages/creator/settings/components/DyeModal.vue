<script setup lang="ts">
import { store, update } from '@/actions/App/Http/Controllers/DyeController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldAutoComplete from '@/components/ui/UiFormFieldAutoComplete.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Dye {
    id: number;
    name: string;
    manufacturer?: string | null;
}

interface Props {
    visible: boolean;
    dye?: Dye | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

const isEditMode = computed(() => !!props.dye);

const manufacturerSuggestions = ref(['Dharma', 'Jacquard']);

const initialValues = computed(() => {
    if (isEditMode.value && props.dye) {
        return {
            name: props.dye.name || '',
            manufacturer: props.dye.manufacturer || null,
        };
    }
    return {
        name: '',
        manufacturer: null,
    };
});

const { form, onSubmit } = useFormSubmission({
    route: () => {
        if (isEditMode.value && props.dye) {
            return update(props.dye.id);
        }
        return store;
    },
    initialValues: initialValues.value,
    successMessage: isEditMode.value
        ? 'Dye updated successfully.'
        : 'Dye created successfully.',
    onSuccess: () => {
        closeModal();
        router.reload({ only: ['dyes'] });
    },
});

watch(
    () => props.visible,
    (newValue) => {
        if (newValue && isEditMode.value && props.dye) {
            form.setValues({
                name: props.dye.name || '',
                manufacturer: props.dye.manufacturer || null,
            });
        } else if (newValue && !isEditMode.value) {
            form.setValues({
                name: '',
                manufacturer: null,
            });
        }
    },
);

function closeModal(): void {
    emit('update:visible', false);
    form.reset();
    form.clearErrors();
}

function searchManufacturer(event: { query: string }): void {
    const query = event.query.toLowerCase();
    const filtered = ['Dharma', 'Jacquard'].filter((m) =>
        m.toLowerCase().includes(query),
    );
    manufacturerSuggestions.value =
        filtered.length > 0 ? filtered : ['Dharma', 'Jacquard'];
}
</script>

<template>
    <UiDialog
        :visible="visible"
        modal
        :header="isEditMode ? 'Edit Dye' : 'Create Dye'"
        :closable="true"
        :close-on-escape="true"
        size="medium"
        @update:visible="emit('update:visible', $event)"
    >
        <UiForm @submit="onSubmit">
            <UiFormFieldInput
                name="name"
                label="Name"
                :server-error="form.errors.name"
                required
            />

            <UiFormFieldAutoComplete
                name="manufacturer"
                label="Manufacturer"
                :suggestions="manufacturerSuggestions"
                :server-error="form.errors.manufacturer"
                @complete="searchManufacturer"
            />

            <div class="mt-6 flex justify-end gap-2">
                <UiButton variant="secondary" type="button" @click="closeModal">
                    Cancel
                </UiButton>
                <UiButton type="submit" :loading="form.processing">
                    {{ isEditMode ? 'Update Dye' : 'Create Dye' }}
                </UiButton>
            </div>
        </UiForm>
    </UiDialog>
</template>
