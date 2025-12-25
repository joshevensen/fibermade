<script setup lang="ts">
import UiCard from '@/components/ui/UiCard.vue';
import UiSelectButton from '@/components/ui/UiSelectButton.vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const appearanceOptions = [
    { label: 'Light', value: 'light' },
    { label: 'Dark', value: 'dark' },
    { label: 'System', value: 'system' },
];
</script>

<template>
    <UiCard>
        <template #title>Appearance Settings</template>
        <template #subtitle>Update your account's appearance settings</template>
        <template #content>
            <UiSelectButton
                :model-value="appearance"
                :options="appearanceOptions"
                option-label="label"
                option-value="value"
                @update:model-value="updateAppearance"
            />
        </template>
    </UiCard>
</template>
