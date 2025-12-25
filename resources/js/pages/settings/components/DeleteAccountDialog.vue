<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import UiCard from '@/components/ui/UiCard.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiPassword from '@/components/ui/UiPassword.vue';
import UiButton from '@/components/ui/UiButton.vue';
import { useFormSubmission } from '@/composables/useFormSubmission';
import { focusPasswordInput } from '@/utils/focusPasswordInput';

const passwordInput = useTemplateRef<{ $el: HTMLElement }>('passwordInput');
const dialogVisible = ref(false);

const { form, onSubmit } = useFormSubmission({
    route: ProfileController.destroy,
    initialValues: {
        password: '',
    },
    preserveScroll: true,
    onSuccess: () => {
        dialogVisible.value = false;
    },
    onError: async () => {
        await focusPasswordInput(passwordInput);
    },
});

function handleCancel(): void {
    form.clearErrors();
    form.reset();
    dialogVisible.value = false;
}

function openDialog(): void {
    dialogVisible.value = true;
}
</script>

<template>
    <UiCard>
        <template #title>Delete Account</template>
        <template #subtitle>Delete your account and all of its resources</template>
        <template #content>
            <div
                class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
            >
                <div
                    class="relative space-y-0.5 text-red-600 dark:text-red-100"
                >
                    <p class="font-medium">Warning</p>
                    <p class="text-sm">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>
                <UiButton
                    variant="destructive"
                    data-test="delete-user-button"
                    @click="openDialog"
                >
                    Delete account
                </UiButton>
            </div>
        </template>
    </UiCard>

    <UiDialog
        :visible="dialogVisible"
        modal
        header="Are you sure you want to delete your account?"
        :closable="true"
        :close-on-escape="true"
        @update:visible="(value: boolean) => dialogVisible = value"
    >
        <UiForm
            :initialValues="{ password: '' }"
            @submit="onSubmit"
        >
            <p class="mb-6 text-neutral-600 dark:text-neutral-400">
                Once your account is deleted, all of its resources and data will
                also be permanently deleted. Please enter your password to
                confirm you would like to permanently delete your account.
            </p>

            <UiFormField
                name="password"
                label="Password"
                :serverError="form.errors.password"
            >
                <template #default="{ props: fieldProps, id }">
                    <UiPassword
                        v-bind="fieldProps"
                        :id="id"
                        ref="passwordInput"
                        placeholder="Password"
                    />
                </template>
            </UiFormField>

            <div class="mt-6 flex gap-2 justify-end">
                <UiButton
                    variant="secondary"
                    type="button"
                    @click="handleCancel"
                >
                    Cancel
                </UiButton>

                <UiButton
                    type="submit"
                    variant="destructive"
                    :loading="form.processing"
                    data-test="confirm-delete-user-button"
                >
                    Delete account
                </UiButton>
            </div>
        </UiForm>
    </UiDialog>
</template>

