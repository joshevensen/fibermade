<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/CollectionController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { enumToOptions } from '@/utils/enumOptions';
import { router } from '@inertiajs/vue3';

const statusOptions = enumToOptions([
    { name: 'Active', value: 'active' },
    { name: 'Retired', value: 'retired' },
]);

interface Props {
    visible: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

function closeDrawer(): void {
    emit('update:visible', false);
}

const initialValues = {
    name: '',
    description: null,
    status: 'active',
};

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues,
    successMessage: 'Collection created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['collections'] });
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
            <h2 class="text-xl font-semibold">Create Collection</h2>
        </template>

        <div class="p-4">
            <UiForm :initial-values="initialValues" @submit="onSubmit">
                <UiFormField
                    name="status"
                    label="Status"
                    :server-error="form.errors.status"
                >
                    <template #default="{ props: fieldProps }">
                        <UiSelectButton
                            v-bind="fieldProps"
                            :options="statusOptions"
                            size="small"
                            fluid
                        />
                    </template>
                </UiFormField>

                <UiFormFieldInput
                    name="name"
                    label="Name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormField
                    name="description"
                    label="Description"
                    :server-error="form.errors.description"
                >
                    <template #default="{ props: fieldProps }">
                        <UiEditor v-bind="fieldProps" />
                    </template>
                </UiFormField>

                <UiButton type="submit" :loading="form.processing">
                    Create Collection
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
