<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import { computed } from 'vue';

interface Props {
    visible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visible: false,
});

const emit = defineEmits<{
    'update:visible': [value: boolean];
}>();

const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});
</script>

<template>
    <UiDialog
        v-model:visible="isVisible"
        modal
        header="How to Export Customers from Shopify"
        size="large"
    >
        <div class="space-y-4">
            <div>
                <h3 class="mb-2 font-semibold text-surface-700">
                    Export Customers:
                </h3>
                <ol
                    class="list-inside list-decimal space-y-2 text-sm text-surface-600"
                >
                    <li>Log in to your Shopify admin panel</li>
                    <li>
                        Navigate to <strong>Customers</strong> in the left
                        sidebar
                    </li>
                    <li>
                        Click the <strong>Export</strong> button at the top
                        right
                    </li>
                    <li>
                        Select <strong>"Export all customers"</strong> or choose
                        specific customers
                    </li>
                    <li>
                        Click <strong>"Export customers"</strong> and wait for
                        the CSV file to download
                    </li>
                </ol>
            </div>

            <div class="rounded-lg bg-surface-100 p-4">
                <p class="text-sm text-surface-600">
                    <strong>Note:</strong> The export includes customer contact
                    information, addresses, and order history.
                </p>
            </div>
        </div>

        <template #footer>
            <UiButton severity="secondary" @click="isVisible = false">
                Close
            </UiButton>
        </template>
    </UiDialog>
</template>
