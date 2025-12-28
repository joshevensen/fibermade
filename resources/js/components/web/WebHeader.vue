<script setup lang="ts">
import UiDialog from '@/components/ui/UiDialog.vue';
import UiLink from '@/components/ui/UiLink.vue';
import { useIcon } from '@/composables/useIcon';
import { ref } from 'vue';

interface NavigationLink {
    name: string;
    href: string;
}

interface Props {
    variant?: 'right' | 'centered';
    logoUrlLight?: string;
    logoUrlDark?: string;
    companyName?: string;
    navigation: NavigationLink[];
    loginLink?: string;
    signupLink?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'right',
    companyName: 'Your Company',
});

const mobileMenuOpen = ref(false);
const { IconList } = useIcon();
</script>

<template>
    <header class="bg-white dark:bg-gray-900">
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
                        v-if="logoUrlLight"
                        class="h-8 w-auto dark:hidden"
                        :src="logoUrlLight"
                        alt=""
                    />
                    <img
                        v-if="logoUrlDark"
                        class="h-8 w-auto not-dark:hidden"
                        :src="logoUrlDark"
                        alt=""
                    />
                </UiLink>
                <div class="hidden lg:flex lg:gap-x-12">
                    <UiLink
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="text-sm/6 font-semibold text-gray-900 dark:text-white"
                    >
                        {{ item.name }}
                    </UiLink>
                </div>
            </div>
            <div class="flex lg:hidden">
                <button
                    type="button"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700 dark:text-gray-400 dark:hover:text-white"
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
                    class="text-sm/6 font-semibold text-gray-900 dark:text-white"
                >
                    Log in
                    <span aria-hidden="true">&rarr;</span>
                </UiLink>
                <UiLink
                    v-if="signupLink"
                    :href="signupLink"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
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
                        v-if="logoUrlLight"
                        class="h-8 w-auto dark:hidden"
                        :src="logoUrlLight"
                        alt=""
                    />
                    <img
                        v-if="logoUrlDark"
                        class="h-8 w-auto not-dark:hidden"
                        :src="logoUrlDark"
                        alt=""
                    />
                </UiLink>
            </div>
            <div class="hidden lg:flex lg:gap-x-12">
                <UiLink
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="text-sm/6 font-semibold text-gray-900 dark:text-white"
                >
                    {{ item.name }}
                </UiLink>
            </div>
            <div class="flex flex-1 items-center justify-end gap-x-6">
                <UiLink
                    v-if="loginLink"
                    :href="loginLink"
                    class="hidden text-sm/6 font-semibold text-gray-900 lg:block dark:text-white"
                >
                    Log in
                </UiLink>
                <UiLink
                    v-if="signupLink"
                    :href="signupLink"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                >
                    Sign up
                </UiLink>
            </div>
            <div class="flex lg:hidden">
                <button
                    type="button"
                    class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700 dark:text-gray-400"
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
                class="flex h-full flex-col bg-white sm:ring-1 sm:ring-gray-900/10 dark:bg-gray-900 dark:sm:ring-gray-100/10"
            >
                <!-- Header with logo and close button -->
                <div
                    :class="[
                        'flex items-center gap-x-6 p-6',
                        variant === 'centered' && signupLink
                            ? 'justify-between'
                            : 'justify-between',
                    ]"
                >
                    <UiLink href="#" class="-m-1.5 p-1.5">
                        <span class="sr-only">{{ companyName }}</span>
                        <img
                            v-if="logoUrlLight"
                            class="h-8 w-auto dark:hidden"
                            :src="logoUrlLight"
                            alt=""
                        />
                        <img
                            v-if="logoUrlDark"
                            class="h-8 w-auto not-dark:hidden"
                            :src="logoUrlDark"
                            alt=""
                        />
                    </UiLink>
                    <div class="flex items-center gap-x-4">
                        <UiLink
                            v-if="variant === 'centered' && signupLink"
                            :href="signupLink"
                            class="ml-auto rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                        >
                            Sign up
                        </UiLink>
                        <button
                            type="button"
                            class="-m-2.5 rounded-md p-2.5 text-gray-700 dark:text-gray-400"
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
                    <div
                        class="-my-6 divide-y divide-gray-500/10 dark:divide-white/10"
                    >
                        <div class="space-y-2 py-6">
                            <UiLink
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                                @click="mobileMenuOpen = false"
                            >
                                {{ item.name }}
                            </UiLink>
                        </div>
                        <div class="py-6">
                            <UiLink
                                v-if="loginLink"
                                :href="loginLink"
                                class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                                @click="mobileMenuOpen = false"
                            >
                                Log in
                            </UiLink>
                            <UiLink
                                v-if="signupLink && variant === 'right'"
                                :href="signupLink"
                                class="-mx-3 mt-2 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
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
