<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/DyeController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldAutoComplete from '@/components/ui/UiFormFieldAutoComplete.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const manufacturerSuggestions = ref(['Dharma', 'Jacquard']);

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues: {
        name: '',
        manufacturer: null,
    },
    successMessage: 'Dye created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['dyes'] });
    },
});

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
    <UiDrawer
        :visible="visible"
        position="right"
        class="!w-[30rem]"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <h2 class="text-xl font-semibold">Create Dye</h2>
        </template>

        <div class="p-4">
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

                <UiButton type="submit" :loading="form.processing">
                    Create Dye
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
