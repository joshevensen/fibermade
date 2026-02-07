<script setup lang="ts">
import {
    destroy as destroyBase,
    index as indexBase,
    update,
} from '@/actions/App/Http/Controllers/BaseController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiFormFieldSelect from '@/components/ui/UiFormFieldSelect.vue';
import UiInputGroup from '@/components/ui/UiInputGroup.vue';
import UiInputGroupAddon from '@/components/ui/UiInputGroupAddon.vue';
import UiInputNumber from '@/components/ui/UiInputNumber.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { useIcon } from '@/composables/useIcon';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
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
        silk_percent?: number | null;
        linen_percent?: number | null;
    };
    baseStatusOptions: Array<{ label: string; value: string }>;
    weightOptions: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();
const { IconList } = useIcon();
const { requireDelete } = useConfirm();

const initialValues = {
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
    silk_percent: props.base.silk_percent || null,
    linen_percent: props.base.linen_percent || null,
};

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.base.id),
    initialValues,
    successMessage: 'Base updated successfully.',
    onSuccess: () => {
        router.visit(indexBase.url());
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
    <CreatorLayout page-title="Edit Base">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm :initial-values="initialValues" @submit="onSubmit">
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

                        <div class="grid grid-cols-2 gap-4">
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
                                        <UiInputGroupAddon
                                            >grams</UiInputGroupAddon
                                        >
                                    </UiInputGroup>
                                </template>
                            </UiFormField>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
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
                                            :minFractionDigits="2"
                                            :maxFractionDigits="2"
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
                                            :minFractionDigits="2"
                                            :maxFractionDigits="2"
                                        />
                                    </UiInputGroup>
                                </template>
                            </UiFormField>
                        </div>

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
                    <template #title>Material</template>
                    <template #content>
                        <UiForm
                            :initial-values="initialValues"
                            @submit="onSubmit"
                        >
                            <div class="grid grid-cols-3 gap-4">
                                <UiFormField
                                    name="wool_percent"
                                    label="Wool"
                                    :server-error="form.errors.wool_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="nylon_percent"
                                    label="Nylon"
                                    :server-error="form.errors.nylon_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="alpaca_percent"
                                    label="Alpaca"
                                    :server-error="form.errors.alpaca_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="yak_percent"
                                    label="Yak"
                                    :server-error="form.errors.yak_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="camel_percent"
                                    label="Camel"
                                    :server-error="form.errors.camel_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="silk_percent"
                                    label="Silk"
                                    :server-error="form.errors.silk_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="cotton_percent"
                                    label="Cotton"
                                    :server-error="form.errors.cotton_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="bamboo_percent"
                                    label="Bamboo"
                                    :server-error="form.errors.bamboo_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>

                                <UiFormField
                                    name="linen_percent"
                                    label="Linen"
                                    :server-error="form.errors.linen_percent"
                                >
                                    <template #default="{ props: fieldProps }">
                                        <UiInputGroup>
                                            <UiInputNumber
                                                v-bind="fieldProps"
                                                :min="0"
                                                :max="100"
                                            />
                                            <UiInputGroupAddon
                                                >%</UiInputGroupAddon
                                            >
                                        </UiInputGroup>
                                    </template>
                                </UiFormField>
                            </div>
                        </UiForm>
                    </template>
                </UiCard>

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
    </CreatorLayout>
</template>
