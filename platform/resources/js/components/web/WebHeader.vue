<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import UiDialog from '@/components/ui/UiDialog.vue';
import { useIcon } from '@/composables/useIcon';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface NavigationLink {
    name: string;
    href: string;
}

interface Props {
    variant?: 'right' | 'centered';
    background?: 'white' | 'surface' | 'primary';
    logoUrl?: string;
    companyName?: string;
    navigation: NavigationLink[];
    loginLink?: string;
    signupLink?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'right',
    background: 'white',
    companyName: 'Your Company',
});

const backgroundClass = computed(() => {
    switch (props.background) {
        case 'surface':
            return 'bg-surface-200';
        case 'primary':
            return 'bg-primary-500';
        default:
            return 'bg-surface-50';
    }
});

const mobileMenuOpen = ref(false);
const { IconList } = useIcon();

function navigateTo(href: string) {
    mobileMenuOpen.value = false;
    if (href.startsWith('#')) {
        const el = document.querySelector(href);
        el?.scrollIntoView({ behavior: 'smooth' });
    } else {
        router.visit(href);
    }
}
</script>

<template>
    <header :class="backgroundClass">
        <!-- Right variant -->
        <nav
            v-if="variant === 'right'"
            class="mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8"
            aria-label="Global"
        >
            <div class="flex items-center gap-x-12">
                <UiButton
                    type="button"
                    text
                    class="-m-1.5 p-1.5"
                    @click="router.visit('/')"
                >
                    <span class="sr-only">{{ companyName }}</span>
                    <img
                        v-if="logoUrl"
                        class="h-8 w-auto"
                        :src="logoUrl"
                        :alt="companyName"
                    />
                    <span
                        v-else
                        class="text-xl font-semibold tracking-tight text-surface-900"
                    >
                        {{ companyName }}
                    </span>
                </UiButton>
                <div class="hidden lg:flex lg:gap-x-6">
                    <UiButton
                        v-for="item in navigation"
                        :key="item.name"
                        type="button"
                        text
                        class="text-sm/6 font-semibold text-surface-900"
                        @click="navigateTo(item.href)"
                    >
                        {{ item.name }}
                    </UiButton>
                </div>
            </div>
            <div class="flex lg:hidden">
                <button
                    type="button"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-surface-700"
                    @click="mobileMenuOpen = true"
                >
                    <span class="sr-only">Open main menu</span>
                    <i
                        :class="[IconList.Menu, 'size-6']"
                        aria-hidden="true"
                    ></i>
                </button>
            </div>
            <div class="hidden lg:flex lg:items-center lg:gap-x-4">
                <UiButton
                    v-if="loginLink"
                    type="button"
                    text
                    class="text-sm/6 font-semibold text-surface-900"
                    @click="router.visit(loginLink)"
                >
                    Log in
                    <span aria-hidden="true">&rarr;</span>
                </UiButton>
                <UiButton
                    v-if="signupLink"
                    type="button"
                    severity="primary"
                    @click="router.visit(signupLink)"
                >
                    Sign up
                </UiButton>
            </div>
        </nav>

        <!-- Centered variant -->
        <nav
            v-else
            class="mx-auto flex max-w-7xl items-center justify-between gap-x-6 p-6 lg:px-8"
            aria-label="Global"
        >
            <div class="flex lg:flex-1">
                <UiButton
                    type="button"
                    text
                    class="-m-1.5 p-1.5"
                    @click="router.visit('/')"
                >
                    <span class="sr-only">{{ companyName }}</span>
                    <img
                        v-if="logoUrl"
                        class="h-8 w-auto"
                        :src="logoUrl"
                        :alt="companyName"
                    />
                    <span
                        v-else
                        class="text-xl font-semibold tracking-tight text-surface-900"
                    >
                        {{ companyName }}
                    </span>
                </UiButton>
            </div>
            <div class="hidden lg:flex lg:gap-x-6">
                <UiButton
                    v-for="item in navigation"
                    :key="item.name"
                    type="button"
                    text
                    class="text-sm/6 font-semibold text-surface-900"
                    @click="navigateTo(item.href)"
                >
                    {{ item.name }}
                </UiButton>
            </div>
            <div class="flex flex-1 items-center justify-end gap-x-6">
                <UiButton
                    v-if="loginLink"
                    type="button"
                    text
                    class="hidden text-sm/6 font-semibold text-surface-900 lg:block"
                    @click="router.visit(loginLink)"
                >
                    Log in
                </UiButton>
                <UiButton
                    v-if="signupLink"
                    type="button"
                    severity="primary"
                    @click="router.visit(signupLink)"
                >
                    Sign up
                </UiButton>
            </div>
            <div class="flex lg:hidden">
                <button
                    type="button"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-surface-700"
                    @click="mobileMenuOpen = true"
                >
                    <span class="sr-only">Open main menu</span>
                    <i
                        :class="[IconList.Menu, 'size-6']"
                        aria-hidden="true"
                    ></i>
                </button>
            </div>
        </nav>

        <!-- Mobile Menu Dialog -->
        <UiDialog
            :visible="mobileMenuOpen"
            :modal="true"
            :closable="false"
            position="right"
            :show-header="false"
            class="lg:hidden"
            @update:visible="(value: boolean) => (mobileMenuOpen = value)"
        >
            <div
                class="flex h-full flex-col bg-surface-0 sm:ring-1 sm:ring-surface-900/10"
            >
                <!-- Header with logo and close button -->
                <div class="flex items-center justify-between gap-x-6 p-6">
                    <UiButton
                        type="button"
                        text
                        class="-m-1.5 p-1.5"
                        @click="
                            mobileMenuOpen = false;
                            router.visit('/');
                        "
                    >
                        <span class="sr-only">{{ companyName }}</span>
                        <img
                            v-if="logoUrl"
                            class="h-8 w-auto"
                            :src="logoUrl"
                            :alt="companyName"
                        />
                        <span
                            v-else
                            class="text-xl font-semibold tracking-tight text-surface-900"
                        >
                            {{ companyName }}
                        </span>
                    </UiButton>
                    <div class="flex items-center gap-x-4">
                        <UiButton
                            v-if="variant === 'centered' && signupLink"
                            type="button"
                            severity="primary"
                            class="ml-auto"
                            @click="
                                mobileMenuOpen = false;
                                router.visit(signupLink);
                            "
                        >
                            Sign up
                        </UiButton>
                        <button
                            type="button"
                            class="-m-2.5 rounded-md p-2.5 text-surface-700"
                            @click="mobileMenuOpen = false"
                        >
                            <span class="sr-only">Close menu</span>
                            <i
                                :class="[IconList.Close, 'size-6']"
                                aria-hidden="true"
                            ></i>
                        </button>
                    </div>
                </div>
                <!-- Navigation and auth links -->
                <div class="flex-1 overflow-y-auto px-6 pb-6">
                    <div class="-my-6 divide-y divide-surface-500/10">
                        <div class="space-y-2 py-6">
                            <UiButton
                                v-for="item in navigation"
                                :key="item.name"
                                type="button"
                                text
                                class="-mx-3 block w-full rounded-lg px-3 py-2 text-left text-base/7 font-semibold text-surface-900 hover:bg-surface-50"
                                @click="navigateTo(item.href)"
                            >
                                {{ item.name }}
                            </UiButton>
                        </div>
                        <div class="py-6">
                            <UiButton
                                v-if="loginLink"
                                type="button"
                                text
                                class="-mx-3 block w-full rounded-lg px-3 py-2.5 text-left text-base/7 font-semibold text-surface-900 hover:bg-surface-50"
                                @click="
                                    mobileMenuOpen = false;
                                    router.visit(loginLink);
                                "
                            >
                                Log in
                            </UiButton>
                            <UiButton
                                v-if="signupLink && variant === 'right'"
                                type="button"
                                text
                                class="-mx-3 mt-2 block w-full rounded-lg px-3 py-2.5 text-left text-base/7 font-semibold text-surface-900 hover:bg-surface-50"
                                @click="
                                    mobileMenuOpen = false;
                                    router.visit(signupLink);
                                "
                            >
                                Sign up
                            </UiButton>
                        </div>
                    </div>
                </div>
            </div>
        </UiDialog>
    </header>
</template>
