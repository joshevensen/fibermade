<script setup lang="ts">
import {
    destroy as destroyBase,
    update,
} from '@/actions/App/Http/Controllers/BaseController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldInputNumber from '@/components/ui/UiFormFieldInputNumber.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiInputGroup from '@/components/ui/UiInputGroup.vue';
import UiInputGroupAddon from '@/components/ui/UiInputGroupAddon.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

interface Props {
    base: {
        id: number;
        description?: string | null;
        status: string;
        weight?: string | null;
        descriptor: string;
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
const { IconList } = useIcon();
const { requireDelete } = useConfirm();

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.base.id),
    initialValues: {
        description: props.base.description || null,
        status: props.base.status || null,
        weight: props.base.weight || null,
        descriptor: props.base.descriptor || '',
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

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.base.descriptor}?`,
        onAccept: () => {
            router.delete(destroyBase.url(props.base.id));
        },
    });
}
</script>

<template>
    <AppLayout page-title="Edit Base">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm @submit="onSubmit">
                        <UiFormFieldInput
                            name="descriptor"
                            label="Descriptor"
                            placeholder="Base descriptor"
                            :server-error="form.errors.descriptor"
                            required
                        />

                        <UiFormField
                            name="description"
                            label="Description"
                            :server-error="form.errors.description"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiEditor
                                    v-bind="fieldProps"
                                    placeholder="Base description"
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
                                    :options="baseStatusOptions"
                                    size="small"
                                    fluid
                                />
                            </template>
                        </UiFormField>

                        <UiFormFieldSelect
                            name="weight"
                            label="Weight"
                            :options="weightOptions"
                            placeholder="Select weight"
                            :server-error="form.errors.weight"
                            show-clear
                        />

                        <UiFormField
                            name="size"
                            label="Size"
                            :server-error="form.errors.size"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiInputGroup>
                                    <UiInputNumber
                                        v-bind="fieldProps"
                                        :min="0"
                                    />
                                    <UiInputGroupAddon>grams</UiInputGroupAddon>
                                </UiInputGroup>
                            </template>
                        </UiFormField>

                        <UiFormField
                            name="cost"
                            label="Cost"
                            :server-error="form.errors.cost"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiInputGroup>
                                    <UiInputGroupAddon>$</UiInputGroupAddon>
                                    <UiInputNumber
                                        v-bind="fieldProps"
                                        :min="0"
                                        :max="99999999.99"
                                        :step="0.01"
                                    />
                                </UiInputGroup>
                            </template>
                        </UiFormField>

                        <UiFormField
                            name="retail_price"
                            label="Retail Price"
                            :server-error="form.errors.retail_price"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiInputGroup>
                                    <UiInputGroupAddon>$</UiInputGroupAddon>
                                    <UiInputNumber
                                        v-bind="fieldProps"
                                        :min="0"
                                        :max="99999999.99"
                                        :step="0.01"
                                    />
                                </UiInputGroup>
                            </template>
                        </UiFormField>

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

                        <UiButton type="submit" :loading="form.processing">
                            Update Base
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
                                    Deleting this base will permanently remove
                                    all associated data. This action cannot be
                                    undone.
                                </p>
                            </div>
                            <UiButton
                                type="button"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="handleDelete($event)"
                            >
                                Delete Base
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </AppLayout>
</template>
