<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/CustomerController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiTextarea from '@/components/ui/UiTextarea.vue';
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
        email: null,
        phone: null,
        address: null,
        city: null,
        state: null,
        zip: null,
        notes: null,
    },
    successMessage: 'Customer created successfully.',
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['customers'] });
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
            <h2 class="text-xl font-semibold">Create Customer</h2>
        </template>

        <div class="p-4">
            <UiForm @submit="onSubmit">
                <UiFormFieldInput
                    name="name"
                    label="Name"
                    placeholder="Customer name"
                    :server-error="form.errors.name"
                    required
                />

                <UiFormFieldInput
                    name="email"
                    label="Email"
                    type="email"
                    placeholder="customer@example.com"
                    :server-error="form.errors.email"
                />

                <UiFormFieldInput
                    name="phone"
                    label="Phone"
                    placeholder="Phone number"
                    :server-error="form.errors.phone"
                />

                <UiFormFieldInput
                    name="address"
                    label="Address"
                    placeholder="Street address"
                    :server-error="form.errors.address"
                />

                <div class="grid grid-cols-2 gap-4">
                    <UiFormFieldInput
                        name="city"
                        label="City"
                        placeholder="City"
                        :server-error="form.errors.city"
                    />

                    <UiFormFieldInput
                        name="state"
                        label="State"
                        placeholder="State"
                        :server-error="form.errors.state"
                    />
                </div>

                <UiFormFieldInput
                    name="zip"
                    label="ZIP Code"
                    placeholder="ZIP code"
                    :server-error="form.errors.zip"
                />

                <UiFormField
                    name="notes"
                    label="Notes"
                    :server-error="form.errors.notes"
                >
                    <template #default="{ props: fieldProps }">
                        <UiTextarea
                            v-bind="fieldProps"
                            placeholder="Additional notes"
                            rows="4"
                        />
                    </template>
                </UiFormField>

                <UiButton type="submit" :loading="form.processing">
                    Create Customer
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
