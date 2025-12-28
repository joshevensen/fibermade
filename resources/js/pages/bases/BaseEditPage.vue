<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldTextarea from '@/components/ui/UiFormFieldTextarea.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import { update } from '@/actions/App/Http/Controllers/BaseController';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';

interface Props {
    base: {
        id: number;
        name: string;
        slug: string;
        description?: string | null;
        status: string;
        weight?: string | null;
        descriptor?: string | null;
        size?: number | null;
        cost?: number | null;
        retail_price?: number | null;
        wool_percent?: number | null;
        nylon_percent?: number | null;
        alpaca_percent?: number | null;
        yak_percent?: number | null;
        camel_percent?: number | null;
        cotton_percent?: number | null;
        bamboo_percent?: number | null;
    };
    baseStatusOptions: Array<{ label: string; value: string }>;
    weightOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { BusinessIconList } = useIcon();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.base.id),
    initialValues: {
        name: props.base.name || '',
        slug: props.base.slug || '',
        description: props.base.description || null,
        status: props.base.status || null,
        weight: props.base.weight || null,
        descriptor: props.base.descriptor || null,
        size: props.base.size || null,
        cost: props.base.cost || null,
        retail_price: props.base.retail_price || null,
        wool_percent: props.base.wool_percent || null,
        nylon_percent: props.base.nylon_percent || null,
        alpaca_percent: props.base.alpaca_percent || null,
        yak_percent: props.base.yak_percent || null,
        camel_percent: props.base.camel_percent || null,
        cotton_percent: props.base.cotton_percent || null,
        bamboo_percent: props.base.bamboo_percent || null,
    },
    successMessage: 'Base updated successfully.',
    onSuccess: () => {
        router.visit('/bases');
    },
});
</script>

<template>
    <AppLayout page-title="Edit Base">
        <PageHeader
            heading="Edit Base"
            :business-icon="BusinessIconList.Bases"
        />

        <div class="mt-6 max-w-2xl">
            <UiCard>
                <template #content>
                    <UiForm @submit="onSubmit">
                <UiFormFieldInput
                    name="name"
                    label="Name"
                    placeholder="Base name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldInput
                    name="slug"
                    label="Slug"
                    placeholder="base-slug"
                    :server-error="form.errors.slug"
                    required
                />

                <UiFormFieldTextarea
                    name="description"
                    label="Description"
                    placeholder="Base description"
                    :server-error="form.errors.description"
                />

                <UiFormFieldSelect
                    name="status"
                    label="Status"
                    :options="baseStatusOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select status"
                    :server-error="form.errors.status"
                    required
                />

                <UiFormFieldSelect
                    name="weight"
                    label="Weight"
                    :options="weightOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select weight"
                    :server-error="form.errors.weight"
                    show-clear
                />

                <UiFormFieldInput
                    name="descriptor"
                    label="Descriptor"
                    placeholder="Descriptor"
                    :server-error="form.errors.descriptor"
                />

                <UiFormFieldInputNumber
                    name="size"
                    label="Size"
                    :min="0"
                    :server-error="form.errors.size"
                />

                <UiFormFieldInputNumber
                    name="cost"
                    label="Cost"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.cost"
                />

                <UiFormFieldInputNumber
                    name="retail_price"
                    label="Retail Price"
                    :min="0"
                    :max="99999999.99"
                    :server-error="form.errors.retail_price"
                />

                <UiFormFieldInputNumber
                    name="wool_percent"
                    label="Wool %"
                    :min="0"
                    :max="100"
                    :server-error="form.errors.wool_percent"
                />

                <UiFormFieldInputNumber
                    name="nylon_percent"
                    label="Nylon %"
                    :min="0"
                    :max="100"
                    :server-error="form.errors.nylon_percent"
                />

                <UiFormFieldInputNumber
                    name="alpaca_percent"
                    label="Alpaca %"
                    :min="0"
                    :max="100"
                    :server-error="form.errors.alpaca_percent"
                />

                <UiFormFieldInputNumber
                    name="yak_percent"
                    label="Yak %"
                    :min="0"
                    :max="100"
                    :server-error="form.errors.yak_percent"
                />

                <UiFormFieldInputNumber
                    name="camel_percent"
                    label="Camel %"
                    :min="0"
                    :max="100"
                    :server-error="form.errors.camel_percent"
                />

                <UiFormFieldInputNumber
                    name="cotton_percent"
                    label="Cotton %"
                    :min="0"
                    :max="100"
                    :server-error="form.errors.cotton_percent"
                />

                <UiFormFieldInputNumber
                    name="bamboo_percent"
                    label="Bamboo %"
                    :min="0"
                    :max="100"
                    :server-error="form.errors.bamboo_percent"
                />

                <div class="flex gap-4">
                    <UiButton
                        type="submit"
                        :loading="form.processing"
                    >
                        Update Base
                    </UiButton>
                    <UiButton
                        type="button"
                        severity="secondary"
                        @click="router.visit('/bases')"
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
