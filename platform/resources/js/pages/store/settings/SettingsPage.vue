<script setup lang="ts">
import UiTabPanel from '@/components/ui/UiTabPanel.vue';
import UiTabs from '@/components/ui/UiTabs.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

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
    { value: 'account', label: 'Account' },
    { value: 'profile', label: 'Profile' },
    // { value: 'dyes', label: 'Dyes' },
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
    <StoreLayout page-title="Settings">
        <UiTabs :value="activeTab" :tabs="tabs" @update:value="handleTabChange">
            <UiTabPanel value="account">
                <div class="space-y-4">
                    <AccountForm v-if="business" :account="business" />
                    <!-- <AccountUsersCard
                        v-if="account && user.role === 'owner' && account.users"
                        :users="account.users"
                    /> -->
                    <DeleteAccountDialog />
                </div>
            </UiTabPanel>

            <UiTabPanel value="profile">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <ProfileForm :user="user" />
                    <PasswordForm />
                </div>
            </UiTabPanel>

            <!-- <UiTabPanel value="dyes">
                <div class="space-y-4">
                    <DyesTab :dyes="dyes" />
                </div>
            </UiTabPanel> -->
        </UiTabs>
    </StoreLayout>
</template>
