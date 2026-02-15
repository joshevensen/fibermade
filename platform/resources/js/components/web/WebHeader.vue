<script setup lang="ts">
import UiDialog from '@/components/ui/UiDialog.vue';
import UiLink from '@/components/ui/UiLink.vue';
import { useIcon } from '@/composables/useIcon';
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
            return 'bg-white';
    }
});

const mobileMenuOpen = ref(false);
const { IconList } = useIcon();
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
                <UiLink href="#" class="-m-1.5 p-1.5">
                    <span class="sr-only">{{ companyName }}</span>
                    <img
                        v-if="logoUrl"
                        class="h-8 w-auto"
                        :src="logoUrl"
                        alt=""
                    />
                </UiLink>
                <div class="hidden lg:flex lg:gap-x-12">
                    <UiLink
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="text-sm/6 font-semibold text-surface-900"
                    >
                        {{ item.name }}
                    </UiLink>
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
                <UiLink
                    v-if="loginLink"
                    :href="loginLink"
                    class="text-sm/6 font-semibold text-surface-900"
                >
                    Log in
                    <span aria-hidden="true">&rarr;</span>
                </UiLink>
                <UiLink
                    v-if="signupLink"
                    :href="signupLink"
                    class="rounded-md bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-primary-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    Sign up
                </UiLink>
            </div>
        </nav>

        <!-- Centered variant -->
        <nav
            v-else
            class="mx-auto flex max-w-7xl items-center justify-between gap-x-6 p-6 lg:px-8"
            aria-label="Global"
        >
            <div class="flex lg:flex-1">
                <UiLink href="#" class="-m-1.5 p-1.5">
                    <span class="sr-only">{{ companyName }}</span>
                    <img
                        v-if="logoUrl"
                        class="h-8 w-auto"
                        :src="logoUrl"
                        alt=""
                    />
                </UiLink>
            </div>
            <div class="hidden lg:flex lg:gap-x-12">
                <UiLink
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="text-sm/6 font-semibold text-surface-900"
                >
                    {{ item.name }}
                </UiLink>
            </div>
            <div class="flex flex-1 items-center justify-end gap-x-6">
                <UiLink
                    v-if="loginLink"
                    :href="loginLink"
                    class="hidden text-sm/6 font-semibold text-surface-900 lg:block"
                >
                    Log in
                </UiLink>
                <UiLink
                    v-if="signupLink"
                    :href="signupLink"
                    class="rounded-md bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-primary-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    Sign up
                </UiLink>
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
                    <UiLink href="#" class="-m-1.5 p-1.5">
                        <span class="sr-only">{{ companyName }}</span>
                        <img
                            v-if="logoUrl"
                            class="h-8 w-auto"
                            :src="logoUrl"
                            alt=""
                        />
                    </UiLink>
                    <div class="flex items-center gap-x-4">
                        <UiLink
                            v-if="variant === 'centered' && signupLink"
                            :href="signupLink"
                            class="ml-auto rounded-md bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-primary-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                        >
                            Sign up
                        </UiLink>
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
                            <UiLink
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-surface-900 hover:bg-surface-50"
                                @click="mobileMenuOpen = false"
                            >
                                {{ item.name }}
                            </UiLink>
                        </div>
                        <div class="py-6">
                            <UiLink
                                v-if="loginLink"
                                :href="loginLink"
                                class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-surface-900 hover:bg-surface-50"
                                @click="mobileMenuOpen = false"
                            >
                                Log in
                            </UiLink>
                            <UiLink
                                v-if="signupLink && variant === 'right'"
                                :href="signupLink"
                                class="-mx-3 mt-2 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-surface-900 hover:bg-surface-50"
                                @click="mobileMenuOpen = false"
                            >
                                Sign up
                            </UiLink>
                        </div>
                    </div>
                </div>
            </div>
        </UiDialog>
    </header>
</template>
