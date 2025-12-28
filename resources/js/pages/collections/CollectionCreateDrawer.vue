<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/CollectionController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

interface Props {
    visible: boolean;
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
        slug: '',
        description: null,
    },
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
            <UiForm @submit="onSubmit">
                <UiFormFieldInput
                    name="name"
                    label="Name"
                    placeholder="Collection name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldInput
                    name="slug"
                    label="Slug"
                    placeholder="collection-slug"
                    :server-error="form.errors.slug"
                    required
                />

                <UiFormFieldTextarea
                    name="description"
                    label="Description"
                    placeholder="Collection description"
                    :server-error="form.errors.description"
                />

                <UiButton type="submit" :loading="form.processing">
                    Create Collection
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
