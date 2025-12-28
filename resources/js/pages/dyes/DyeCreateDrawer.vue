<script setup lang="ts">
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import { store } from '@/actions/App/Http/Controllers/DyeController';
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

                        <UiFormFieldTextarea
                            name="notes"
                            label="Notes"
                            placeholder="Dye notes"
                            :server-error="form.errors.notes"
                        />

                        <UiFormFieldCheckbox
                            name="does_bleed"
                            label="Does Bleed"
                            :server-error="form.errors.does_bleed"
                            binary
                        />

                        <UiFormFieldCheckbox
                            name="do_like"
                            label="Do Like"
                            :server-error="form.errors.do_like"
                            binary
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

