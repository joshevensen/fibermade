<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CustomersInstructionsModal from './CustomersInstructionsModal.vue';

const showInstructions = ref(false);
const customersFile = ref<File | null>(null);
const processing = ref(false);

function handleCustomersFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        customersFile.value = target.files[0];
    }
}

function handleSubmit(): void {
    if (!customersFile.value) {
        return;
    }

    processing.value = true;

    const formData = new FormData();
    formData.append('customers_file', customersFile.value);

    router.post('/creator/settings/import/customers', formData, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            customersFile.value = null;
            // Reset file input
            const customersInput = document.getElementById('customers_file') as HTMLInputElement;
            if (customersInput) {
                customersInput.value = '';
            }
        },
    });
}
</script>

<template>
    <UiCard>
        <template #title>Import Customers</template>
        <template #subtitle>Upload Shopify customers export</template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <label
                        for="customers_file"
                        class="mb-2 block text-sm font-medium text-surface-700"
                    >
                        Customers File
                    </label>
                    <input
                        id="customers_file"
                        type="file"
                        accept=".csv,.txt"
                        class="block w-full text-sm text-surface-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90"
                        @change="handleCustomersFileSelect"
                    />
                    <p v-if="customersFile" class="mt-1 text-sm text-surface-500">
                        Selected: {{ customersFile.name }}
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <UiButton
                        text
                        type="button"
                        @click="showInstructions = true"
                    >
                        How to export from Shopify
                    </UiButton>

                    <UiButton
                        type="button"
                        :loading="processing"
                        :disabled="!customersFile || processing"
                        @click="handleSubmit"
                    >
                        Import
                    </UiButton>
                </div>
            </div>
        </template>
    </UiCard>

    <CustomersInstructionsModal
        v-model:visible="showInstructions"
    />
</template>
