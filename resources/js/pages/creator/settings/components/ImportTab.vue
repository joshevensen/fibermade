<script setup lang="ts">
import ImportCustomersCard from './ImportCustomersCard.vue';
import ImportOrdersCard from './ImportOrdersCard.vue';
import ImportProductsCard from './ImportProductsCard.vue';
</script>

<template>
    <div class="space-y-4">
        <ImportProductsCard />
        <ImportOrdersCard />
        <ImportCustomersCard />
    </div>
</template>
