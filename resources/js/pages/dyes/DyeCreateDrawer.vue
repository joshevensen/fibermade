<script setup lang="ts">
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldToggleSwitch from '@/components/ui/UiFormFieldToggleSwitch.vue';
import UiFormFieldAutoComplete from '@/components/ui/UiFormFieldAutoComplete.vue';
import { store } from '@/actions/App/Http/Controllers/DyeController';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const manufacturerSuggestions = ref(['Dharma', 'Jacquard']);

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues: {
        name: '',
        manufacturer: null,
        notes: null,
        does_bleed: false,
        do_like: false,
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
        m.toLowerCase().includes(query)
    );
    manufacturerSuggestions.value = filtered.length > 0 ? filtered : ['Dharma', 'Jacquard'];
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
                            placeholder="Dye name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormFieldAutoComplete
                            name="manufacturer"
                            label="Manufacturer"
                            placeholder="Select or type manufacturer"
                            :suggestions="manufacturerSuggestions"
                            :server-error="form.errors.manufacturer"
                            @complete="searchManufacturer"
                        />

                        <UiFormFieldTextarea
                            name="notes"
                            label="Notes"
                            placeholder="Dye notes"
                            :server-error="form.errors.notes"
                        />

                        <UiFormFieldToggleSwitch
                            name="does_bleed"
                            label="Does Bleed"
                            :server-error="form.errors.does_bleed"
                        />

                        <UiFormFieldToggleSwitch
                            name="do_like"
                            label="Do Like"
                            :server-error="form.errors.do_like"
                        />

                        <UiButton
                            type="submit"
                            :loading="form.processing"
                        >
                            Create Dye
                        </UiButton>
                    </UiForm>
        </div>
    </UiDrawer>
</template>

