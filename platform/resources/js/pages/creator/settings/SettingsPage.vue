<script setup lang="ts">
import UiTabPanel from '@/components/ui/UiTabPanel.vue';
import UiTabs from '@/components/ui/UiTabs.vue';
import CreatorLayout from '@/layouts/CreatorLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AccountForm from './components/AccountForm.vue';
import BillingCard from './components/BillingCard.vue';
import DeleteAccountDialog from './components/DeleteAccountDialog.vue';
import PasswordForm from './components/PasswordForm.vue';
import ProfileForm from './components/ProfileForm.vue';

const page = usePage();
const user = page.props.auth.user as {
    id: number;
    name: string;
    email: string;
    role?: string;
    account_id?: number | null;
};
const business = page.props.business as
    | {
          id: number;
          name: string;
          email?: string | null;
          phone?: string | null;
          address_line1?: string | null;
          address_line2?: string | null;
          city?: string | null;
          state_region?: string | null;
          postal_code?: string | null;
      }
    | null
    | undefined;

const tabs = [
    { value: 'profile', label: 'Profile' },
    { value: 'account', label: 'Account' },
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
    <CreatorLayout page-title="Settings">
        <UiTabs :value="activeTab" :tabs="tabs" @update:value="handleTabChange">
            <UiTabPanel value="profile">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <ProfileForm :user="user" />
                    <PasswordForm />
                </div>
            </UiTabPanel>

            <UiTabPanel value="account">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <AccountForm v-if="business" :account="business" />
                    </div>
                    <div class="space-y-4">
                        <BillingCard />
                        <DeleteAccountDialog />
                    </div>
                </div>
            </UiTabPanel>
        </UiTabs>
    </CreatorLayout>
</template>
