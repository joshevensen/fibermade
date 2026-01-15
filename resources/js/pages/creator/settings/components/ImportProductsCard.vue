<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProductsInstructionsModal from './ProductsInstructionsModal.vue';

const showInstructions = ref(false);
const productsFile = ref<File | null>(null);
const inventoryFile = ref<File | null>(null);
const processing = ref(false);

function handleProductsFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        productsFile.value = target.files[0];
    }
}

function handleInventoryFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        inventoryFile.value = target.files[0];
    }
}

function handleSubmit(): void {
    if (!productsFile.value || !inventoryFile.value) {
        return;
    }

    processing.value = true;

    const formData = new FormData();
    formData.append('products_file', productsFile.value);
    formData.append('inventory_file', inventoryFile.value);

    router.post('/creator/settings/import/products', formData, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            productsFile.value = null;
            inventoryFile.value = null;
            // Reset file inputs
            const productsInput = document.getElementById('products_file') as HTMLInputElement;
            const inventoryInput = document.getElementById('inventory_file') as HTMLInputElement;
            if (productsInput) {
                productsInput.value = '';
            }
            if (inventoryInput) {
                inventoryInput.value = '';
            }
        },
    });
}
</script>

<template>
    <UiCard>
        <template #title>Import Products</template>
        <template #subtitle>Upload Shopify products and inventory exports</template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <label
                        for="products_file"
                        class="mb-2 block text-sm font-medium text-surface-700"
                    >
                        Products File
                    </label>
                    <input
                        id="products_file"
                        type="file"
                        accept=".csv,.txt"
                        class="block w-full text-sm text-surface-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90"
                        @change="handleProductsFileSelect"
                    />
                    <p v-if="productsFile" class="mt-1 text-sm text-surface-500">
                        Selected: {{ productsFile.name }}
                    </p>
                </div>

                <div>
                    <label
                        for="inventory_file"
                        class="mb-2 block text-sm font-medium text-surface-700"
                    >
                        Inventory File
                    </label>
                    <input
                        id="inventory_file"
                        type="file"
                        accept=".csv,.txt"
                        class="block w-full text-sm text-surface-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90"
                        @change="handleInventoryFileSelect"
                    />
                    <p v-if="inventoryFile" class="mt-1 text-sm text-surface-500">
                        Selected: {{ inventoryFile.name }}
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
                        :disabled="!productsFile || !inventoryFile || processing"
                        @click="handleSubmit"
                    >
                        Import
                    </UiButton>
                </div>
            </div>
        </template>
    </UiCard>

    <ProductsInstructionsModal
        v-model:visible="showInstructions"
    />
</template>
