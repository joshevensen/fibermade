<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { useIcon } from '@/composables/useIcon';
import DeleteAccountDialog from './components/DeleteAccountDialog.vue';
import PasswordForm from './components/PasswordForm.vue';
import ProfileForm from './components/ProfileForm.vue';

const page = usePage();
const user = page.props.auth.user;
const { IconList } = useIcon();
</script>

<template>
    <AppLayout page-title="Settings">
        <PageHeader
            heading="Settings"
            :icon="IconList.Settings"
        />

        <div class="space-y-4">
            <ProfileForm :user="user" />
            <PasswordForm />
            <DeleteAccountDialog />
        </div>
    </AppLayout>
</template>
