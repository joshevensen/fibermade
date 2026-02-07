<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiCard from '@/components/ui/UiCard.vue';
import UiFormFieldFileInput from '@/components/ui/UiFormFieldFileInput.vue';
import UiMessage from '@/components/ui/UiMessage.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProductsInstructionsModal from './ProductsInstructionsModal.vue';

const page = usePage();
const showInstructions = ref(false);
const productsFile = ref<File | null>(null);
const collectionsFile = ref<File | null>(null);
const processing = ref(false);

const errors = computed(() => page.props.errors || {});
const successMessage = computed(
    () =>
        (page.props.flash as { success?: string } | undefined)?.success ||
        (page.props.success as string | undefined),
);

function handleProductsFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        productsFile.value = target.files[0];
    }
}

function handleCollectionsFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        collectionsFile.value = target.files[0];
    }
}

function handleSubmit(): void {
    if (!productsFile.value || !collectionsFile.value) {
        return;
    }

    processing.value = true;

    const data: Record<string, File> = {
        products_file: productsFile.value,
        collections_file: collectionsFile.value,
    };

    router.post('/creator/settings/import/products', data, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            productsFile.value = null;
            collectionsFile.value = null;
            // Reset file inputs
            const productsInput = document.getElementById(
                'products_file',
            ) as HTMLInputElement;
            const collectionsInput = document.getElementById(
                'collections_file',
            ) as HTMLInputElement;
            if (productsInput) {
                productsInput.value = '';
            }
            if (collectionsInput) {
                collectionsInput.value = '';
            }
        },
    });
}
</script>

<template>
    <UiCard>
        <template #title>Import Shopify Products</template>
        <template #header-right>
            <UiButton text type="button" @click="showInstructions = true">
                How to export from Shopify
            </UiButton>
        </template>
        <template #content>
            <div class="space-y-4">
                <UiMessage
                    v-if="successMessage"
                    severity="success"
                    size="small"
                >
                    {{ successMessage }}
                </UiMessage>

                <UiMessage
                    v-if="
                        errors.products_file ||
                        errors.collections_file ||
                        errors.error
                    "
                    severity="error"
                    size="small"
                >
                    <div v-if="errors.error">{{ errors.error }}</div>
                    <div v-if="errors.products_file">
                        Products file: {{ errors.products_file }}
                    </div>
                    <div v-if="errors.collections_file">
                        Collections file: {{ errors.collections_file }}
                    </div>
                </UiMessage>

                <UiMessage severity="info" size="small">
                    Products and Collections files are required. Bases will be
                    created automatically from the products file.
                </UiMessage>

                <div class="space-y-4">
                    <UiFormFieldFileInput
                        id="products_file"
                        name="products_file"
                        label="Products File"
                        :required="true"
                        :server-error="errors.products_file"
                        accept=".csv,.txt"
                        @change="handleProductsFileSelect"
                    />

                    <UiFormFieldFileInput
                        id="collections_file"
                        name="collections_file"
                        label="Collections File"
                        :required="true"
                        :server-error="errors.collections_file"
                        accept=".csv,.txt"
                        @change="handleCollectionsFileSelect"
                    />
                </div>

                <div class="flex items-center justify-end">
                    <UiButton
                        type="button"
                        :loading="processing"
                        @click="handleSubmit"
                    >
                        Import
                    </UiButton>
                </div>
            </div>
        </template>
    </UiCard>

    <ProductsInstructionsModal v-model:visible="showInstructions" />
</template>
