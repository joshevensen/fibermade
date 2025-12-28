<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import UiTabPanel from '@/components/ui/UiTabPanel.vue';
import UiTabs from '@/components/ui/UiTabs.vue';
import { useIcon } from '@/composables/useIcon';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DeleteAccountDialog from './components/DeleteAccountDialog.vue';
import PasswordForm from './components/PasswordForm.vue';
import ProfileForm from './components/ProfileForm.vue';

const page = usePage();
const user = page.props.auth.user;
const { IconList } = useIcon();

const tabs = [
    { value: 'account', label: 'Account' },
    { value: 'profile', label: 'Profile' },
    { value: 'discounts', label: 'Discounts' },
    { value: 'shopify', label: 'Shopify' },
];

function getTabFromUrl(): string {
    const urlParts = page.url.split('?');
    if (urlParts.length > 1) {
        const params = new URLSearchParams(urlParts[1]);
        return params.get('tab') || 'profile';
    }
    return 'profile';
}

const activeTab = ref<string>(getTabFromUrl());

function handleTabChange(value: string | number): void {
    activeTab.value = String(value);
    const urlParts = page.url.split('?');
    const pathname = urlParts[0];
    const params = new URLSearchParams(urlParts[1] || '');
    params.set('tab', activeTab.value);
    router.get(
        `${pathname}?${params.toString()}`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

watch(
    () => page.url,
    () => {
        const tabParam = getTabFromUrl();
        if (tabParam !== activeTab.value) {
            activeTab.value = tabParam;
        }
    },
);
</script>

<template>
    <AppLayout page-title="Settings">
        <PageHeader heading="Settings" :icon="IconList.Settings" />

        <UiTabs :value="activeTab" :tabs="tabs" @update:value="handleTabChange">
            <UiTabPanel value="account">
                <div class="space-y-4">
                    <DeleteAccountDialog />
                </div>
            </UiTabPanel>

            <UiTabPanel value="profile">
                <div class="space-y-4">
                    <ProfileForm :user="user" />
                    <PasswordForm />
                </div>
            </UiTabPanel>

            <UiTabPanel value="discounts">
                <div class="space-y-4">
                    <p class="text-surface-600 dark:text-surface-400">
                        Discounts settings coming soon.
                    </p>
                </div>
            </UiTabPanel>

            <UiTabPanel value="shopify">
                <div class="space-y-4">
                    <p class="text-surface-600 dark:text-surface-400">
                        Shopify integration settings coming soon.
                    </p>
                </div>
            </UiTabPanel>
        </UiTabs>
    </AppLayout>
</template>
