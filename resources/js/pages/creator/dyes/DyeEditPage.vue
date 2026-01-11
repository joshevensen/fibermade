<script setup lang="ts">
import {
    destroy as destroyDye,
    update,
} from '@/actions/App/Http/Controllers/DyeController';
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiEditor from '@/components/ui/UiEditor.vue';
import UiForm from '@/components/ui/UiForm.vue';
import UiFormField from '@/components/ui/UiFormField.vue';
import UiFormFieldInput from '@/components/ui/UiFormFieldInput.vue';
import UiMessage from '@/components/ui/UiMessage.vue';
import UiToggleSwitch from '@/components/ui/UiToggleSwitch.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFormSubmission } from '@/composables/useFormSubmission';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
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
const { requireDelete } = useConfirm();

const initialValues = {
    name: props.dye.name || '',
    notes: props.dye.notes || null,
    does_bleed: props.dye.does_bleed ?? false,
    do_like: props.dye.do_like ?? false,
};

const { form, onSubmit } = useFormSubmission({
    route: () => update(props.dye.id),
    initialValues,
    successMessage: 'Dye updated successfully.',
    onSuccess: () => {
        router.visit('/dyes');
    },
});

function handleDelete(event: Event): void {
    requireDelete({
        target: event.currentTarget as HTMLElement,
        message: `Are you sure you want to delete ${props.dye.name}?`,
        onAccept: () => {
            router.delete(destroyDye.url(props.dye.id));
        },
    });
}
</script>

<template>
    <CreatorLayout page-title="Edit Dye">
        <template #default>
            <UiCard>
                <template #content>
                    <UiForm :initial-values="initialValues" @submit="onSubmit">
                        <UiFormFieldInput
                            name="name"
                            label="Name"
                            placeholder="Dye name"
                            :server-error="form.errors.name"
                            required
                        />

                        <UiFormField
                            name="notes"
                            label="Notes"
                            :server-error="form.errors.notes"
                        >
                            <template #default="{ props: fieldProps }">
                                <UiEditor
                                    v-bind="fieldProps"
                                    placeholder="Dye notes"
                                />
                            </template>
                        </UiFormField>

                        <UiButton type="submit" :loading="form.processing">
                            Update Dye
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
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <label
                                    for="does_bleed"
                                    class="text-sm font-medium"
                                >
                                    This Dye Bleeds
                                </label>
                                <UiToggleSwitch
                                    id="does_bleed"
                                    v-model="form.does_bleed"
                                    :invalid="!!form.errors.does_bleed"
                                />
                            </div>
                            <UiMessage
                                v-if="form.errors.does_bleed"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.does_bleed }}
                            </UiMessage>

                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <label
                                    for="do_like"
                                    class="text-sm font-medium"
                                >
                                    I Like this Dye
                                </label>
                                <UiToggleSwitch
                                    id="do_like"
                                    v-model="form.do_like"
                                    :invalid="!!form.errors.do_like"
                                />
                            </div>
                            <UiMessage
                                v-if="form.errors.do_like"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.do_like }}
                            </UiMessage>
                        </div>
                    </template>
                </UiCard>

                <UiCard>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-surface-600">
                                    Deleting this dye will permanently remove
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
                                Delete Dye
                            </UiButton>
                        </div>
                    </template>
                </UiCard>
            </div>
        </template>
    </CreatorLayout>
</template>
