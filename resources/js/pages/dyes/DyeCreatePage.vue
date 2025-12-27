<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import { store } from '@/actions/App/Http/Controllers/DyeController';
import { index } from '@/actions/App/Http/Controllers/DyeController';
import { useIcon } from '@/composables/useIcon';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

const props = defineProps();
const { IconList } = useIcon();

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
        router.visit(index.url());
    },
});
</script>

<template>
    <AppLayout page-title="Create Dye">
        <PageHeader
            heading="Create Dye"
            :icon="IconList.Dyes"
        />

        <div class="mt-6">
            <UiCard>
                <template #title>Dye Information</template>
                <template #content>
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
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
