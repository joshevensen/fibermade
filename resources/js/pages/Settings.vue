<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/ProfileController';
import PasswordController from '@/actions/App/Http/Controllers/PasswordController';
import { useAppearance } from '@/composables/useAppearance';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/lib/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/lib/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/lib/dialog';
import { Input } from '@/components/lib/input';
import { Label } from '@/components/lib/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit } from '@/routes/profile';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;

const { appearance, updateAppearance } = useAppearance();

const appearanceTabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Settings" />

        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Profile Information Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Profile Information</CardTitle>
                    <CardDescription>
                        Update your name and email address
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="ProfileController.update.form()"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                class="mt-1 block w-full"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                placeholder="Full name"
                            />
                            <InputError class="mt-2" :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError class="mt-2" :message="errors.email" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                :disabled="processing"
                                data-test="update-profile-button"
                                >Save</Button
                            >

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-neutral-600"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <!-- Password Update Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Update Password</CardTitle>
                    <CardDescription>
                        Ensure your account is using a long, random password to
                        stay secure
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="PasswordController.update.form()"
                        :options="{
                            preserveScroll: true,
                        }"
                        reset-on-success
                        :reset-on-error="[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password">Current password</Label>
                            <Input
                                id="current_password"
                                name="current_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="current-password"
                                placeholder="Current password"
                            />
                            <InputError
                                :message="errors.current_password"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                placeholder="New password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation"
                                >Confirm password</Label
                            >
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                :disabled="processing"
                                data-test="update-password-button"
                                >Save password</Button
                            >

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-neutral-600"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <!-- Appearance Settings Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Appearance Settings</CardTitle>
                    <CardDescription>
                        Update your account's appearance settings
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
                    >
                        <button
                            v-for="{ value, Icon, label } in appearanceTabs"
                            :key="value"
                            @click="updateAppearance(value)"
                            :class="[
                                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                                appearance === value
                                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                            ]"
                        >
                            <component :is="Icon" class="-ml-1 h-4 w-4" />
                            <span class="ml-1.5 text-sm">{{ label }}</span>
                        </button>
                    </div>
                </CardContent>
            </Card>

            <!-- Delete Account Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Delete Account</CardTitle>
                    <CardDescription>
                        Delete your account and all of its resources
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
                    >
                        <div
                            class="relative space-y-0.5 text-red-600 dark:text-red-100"
                        >
                            <p class="font-medium">Warning</p>
                            <p class="text-sm">
                                Please proceed with caution, this cannot be
                                undone.
                            </p>
                        </div>
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button
                                    variant="destructive"
                                    data-test="delete-user-button"
                                    >Delete account</Button
                                >
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="ProfileController.destroy.form()"
                                    reset-on-success
                                    @error="() => passwordInput?.$el?.focus()"
                                    :options="{
                                        preserveScroll: true,
                                    }"
                                    class="space-y-6"
                                    v-slot="{
                                        errors,
                                        processing,
                                        reset,
                                        clearErrors,
                                    }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Are you sure you want to delete
                                            your account?</DialogTitle
                                        >
                                        <DialogDescription>
                                            Once your account is deleted, all of
                                            its resources and data will also be
                                            permanently deleted. Please enter
                                            your password to confirm you would
                                            like to permanently delete your
                                            account.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label for="password" class="sr-only"
                                            >Password</Label
                                        >
                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            ref="passwordInput"
                                            placeholder="Password"
                                        />
                                        <InputError
                                            :message="errors.password"
                                        />
                                    </div>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button
                                                variant="secondary"
                                                @click="
                                                    () => {
                                                        clearErrors();
                                                        reset();
                                                    }
                                                "
                                            >
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                            data-test="confirm-delete-user-button"
                                        >
                                            Delete account
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

