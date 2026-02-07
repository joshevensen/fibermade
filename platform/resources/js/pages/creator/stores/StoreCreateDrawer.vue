<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/InviteController';
import UiButton from '@/components/ui/UiButton.vue';
import UiDrawer from '@/components/ui/UiDrawer.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
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

const initialValues = {
    type: 'store' as const,
    email: '',
    store_name: '',
    owner_name: '',
};

const { form, onSubmit } = useFormSubmission({
    route: store,
    initialValues,
    successMessage: 'Invite sent.',
    transform: (values) => ({ ...values, type: 'store' as const }),
    onSuccess: () => {
        closeDrawer();
        router.reload({ only: ['stores', 'totalStores', 'filteredCount'] });
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
            <h2 class="text-xl font-semibold">Invite Store</h2>
        </template>

        <div class="p-4">
            <UiForm :initial-values="initialValues" @submit="onSubmit">
                <UiFormFieldInput
                    name="email"
                    label="Email"
                    type="email"
                    :server-error="form.errors.email"
                    required
                />

                <UiFormFieldInput
                    name="store_name"
                    label="Store Name"
                    :server-error="form.errors.store_name"
                />

                <UiFormFieldInput
                    name="owner_name"
                    label="Owner Name"
                    :server-error="form.errors.owner_name"
                />

                <UiButton type="submit" :loading="form.processing">
                    Send invite
                </UiButton>
            </UiForm>
        </div>
    </UiDrawer>
</template>
