<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldCheckbox from '@/components/ui/UiFormFieldCheckbox.vue';
import { update } from '@/actions/App/Http/Controllers/DyeController';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

interface Props {
    dye: {
        id: number;
        name: string;
        notes?: string | null;
        does_bleed: boolean;
        do_like: boolean;
    };
}

const props = defineProps<Props>();
const { BusinessIconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.dye.id),
    initialValues: {
        name: props.dye.name || '',
        notes: props.dye.notes || null,
        does_bleed: props.dye.does_bleed ?? false,
        do_like: props.dye.do_like ?? false,
    },
    successMessage: 'Dye updated successfully.',
    onSuccess: () => {
        router.visit('/dyes');
    },
});
</script>

<template>
    <AppLayout page-title="Edit Dye">
        <PageHeader
            heading="Edit Dye"
            :business-icon="BusinessIconList.Dyes"
        />

        <div class="mt-6 max-w-2xl">
            <UiCard>
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

                <div class="flex gap-4">
                    <UiButton
                        type="submit"
                        :loading="form.processing"
                    >
                        Update Dye
                    </UiButton>
                    <UiButton
                        type="button"
                        severity="secondary"
                        @click="router.visit('/dyes')"
                    >
                        Cancel
                    </UiButton>
                </div>
                    </UiForm>
                </template>
            </UiCard>
        </div>
    </AppLayout>
</template>
