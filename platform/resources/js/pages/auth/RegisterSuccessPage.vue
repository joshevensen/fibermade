<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiLink from '@/components/ui/UiLink.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
</script>

<template>
    <AuthLayout
        title="Account created"
        description="Your Creator account is ready. Check your email for a welcome message, or log in below to get started."
        page-title="Registration complete"
    >
        <div class="flex flex-col gap-4">
            <UiLink :href="login()">
                <UiButton type="button" class="w-full">Log in</UiButton>
            </UiLink>
        </div>

        <template #footer>
            <UiLink :href="login()" class="underline underline-offset-4">
                Back to log in
            </UiLink>
        </template>
    </AuthLayout>
</template>
