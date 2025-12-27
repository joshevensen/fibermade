<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UiButton from '@/components/ui/UiButton.vue';
import UiInputText from '@/components/ui/UiInputText.vue';
import { edit as profileEdit } from '@/routes/user';
import { router } from '@inertiajs/vue3';

const emit = defineEmits<{
    'toggle-mobile-drawer': [];
}>();
</script>

<template>
    <header
        class="flex h-12 w-full items-center justify-between bg-surface-100 px-4"
    >
        <!-- Mobile: Menu toggle + Logo -->
        <div class="flex items-center gap-4 lg:hidden">
            <UiButton
                icon="pi pi-bars"
                text
                @click="emit('toggle-mobile-drawer')"
            />
            <AppLogo variant="full" />
        </div>

        <!-- Desktop: Search Input -->
        <div class="hidden lg:flex gap-3 lg:flex-1 lg:max-w-md">
            <UiInputText
                placeholder="Search"
                icon="pi pi-search"
                icon-pos="start"
                :fluid="false"
            />

            <UiButton
                label="Dye List"
                outlined
            />
        </div>

        <!-- Icon Buttons (all screens) -->
        <div class="flex items-center gap-2">
            <UiButton
                icon="pi pi-question-circle"
                text
                aria-label="Support"
            />
            <UiButton
                icon="pi pi-bell"
                text
                aria-label="Notifications"
            />
            <UiButton
                icon="pi pi-cog"
                text
                aria-label="Settings"
                @click="router.visit(profileEdit.url())"
            />
        </div>
    </header>
</template>
