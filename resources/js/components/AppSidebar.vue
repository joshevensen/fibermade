<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/lib/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/lib/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/lib/sidebar';
import { urlIsActive } from '@/lib/utils';
import { dashboard, logout } from '@/routes';
import { edit } from '@/routes/profile';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, LayoutGrid, LogOut, Settings } from 'lucide-vue-next';
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const page = usePage();
const user = page.props.auth.user;
const { isMobile, state } = useSidebar();

const showAvatar = computed(
    () => user.avatar && user.avatar !== '',
);

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Platform</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="item in mainNavItems"
                        :key="item.title"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="urlIsActive(item.href, page.url)"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                data-test="sidebar-menu-button"
                            >
                                <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                    <AvatarImage
                                        v-if="showAvatar"
                                        :src="user.avatar!"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg text-black dark:text-white"
                                    >
                                        {{ user.initials }}
                                    </AvatarFallback>
                                </Avatar>

                                <div
                                    class="grid flex-1 text-left text-sm leading-tight"
                                >
                                    <span class="truncate font-medium">{{
                                        user.name
                                    }}</span>
                                </div>
                                <ChevronsUpDown class="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                            :side="
                                isMobile
                                    ? 'bottom'
                                    : state === 'collapsed'
                                      ? 'left'
                                      : 'bottom'
                            "
                            align="end"
                            :side-offset="4"
                        >
                            <DropdownMenuLabel class="p-0 font-normal">
                                <div
                                    class="flex items-center gap-2 px-1 py-1.5 text-left text-sm"
                                >
                                    <Avatar
                                        class="h-8 w-8 overflow-hidden rounded-lg"
                                    >
                                        <AvatarImage
                                            v-if="showAvatar"
                                            :src="user.avatar!"
                                            :alt="user.name"
                                        />
                                        <AvatarFallback
                                            class="rounded-lg text-black dark:text-white"
                                        >
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div
                                        class="grid flex-1 text-left text-sm leading-tight"
                                    >
                                        <span class="truncate font-medium">{{
                                            user.name
                                        }}</span>
                                        <span
                                            class="truncate text-xs text-muted-foreground"
                                            >{{ user.email }}</span
                                        >
                                    </div>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                <DropdownMenuItem :as-child="true">
                                    <Link
                                        class="block w-full"
                                        :href="edit()"
                                        prefetch
                                        as="button"
                                    >
                                        <Settings class="mr-2 h-4 w-4" />
                                        Settings
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem :as-child="true">
                                <Link
                                    class="block w-full"
                                    :href="logout()"
                                    @click="handleLogout"
                                    as="button"
                                    data-test="logout-button"
                                >
                                    <LogOut class="mr-2 h-4 w-4" />
                                    Log out
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
