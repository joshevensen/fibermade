<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/StoreController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDivider from '@/components/ui/UiDivider.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldAddress from '@/components/ui/UiFormFieldAddress.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { router } from '@inertiajs/vue3';

// Enum cases - these match the PHP enum
const storeVendorStatusCases = [
    { name: 'Active', value: 'active' },
    { name: 'Paused', value: 'paused' },
    { name: 'Ended', value: 'ended' },
];

const statusOptions = storeVendorStatusCases.map((item) => ({
    label: item.name,
    value: item.value,
}));

interface Props {
    visible: boolean;
    statusOptions?: Array<{ label: string; value: string }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

function closeDrawer(): void {
    emit('update:visible', false);
}

const initialValues = {
    name: '',
    email: '',
    owner_name: null,
    address_line1: '',
    address_line2: null,
    city: '',
    state_region: '',
    postal_code: '',
    country_code: '',
    status: 'active',
};

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues,
    successMessage: 'Store created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['stores'] });
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
            <h2 class="text-xl font-semibold">Create Store</h2>
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
                    label="Store Name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldInput
                    name="email"
                    label="Email"
                    type="email"
                    :server-error="form.errors.email"
                    required
                />

                <UiFormFieldInput
                    name="owner_name"
                    label="Owner Name"
                    :server-error="form.errors.owner_name"
                />

                <UiDivider />

                <UiFormFieldAddress
                    :show-line2="true"
                    :show-country="true"
                    :errors="form.errors"
                />

                <UiButton type="submit" :loading="form.processing">
                    Create Store
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
