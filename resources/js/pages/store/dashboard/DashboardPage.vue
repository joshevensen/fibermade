<script setup lang="ts">
import StoreLayout from '@/layouts/StoreLayout.vue';

interface Props {
    // Store dashboard props will be defined later
}

const props = defineProps<Props>();
</script>

<template>
    <StoreLayout page-title="Dashboard">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Store Dashboard Content -->
            <div class="lg:col-span-3">
                <p class="text-surface-600">Store Dashboard</p>
            </div>
        </div>
    </StoreLayout>
</template>
