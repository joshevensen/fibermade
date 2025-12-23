<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/lib/breadcrumb';
import { SidebarInset, SidebarProvider } from '@/components/lib/sidebar';
import { SidebarTrigger } from '@/components/lib/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <SidebarProvider :default-open="isOpen">
        <AppSidebar />
        <SidebarInset class="overflow-x-hidden">
            <header
                class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
            >
                <div class="flex items-center gap-2">
                    <SidebarTrigger class="-ml-1" />
                    <template
                        v-if="breadcrumbs && breadcrumbs.length > 0"
                    >
                        <Breadcrumb>
                            <BreadcrumbList>
                                <template
                                    v-for="(item, index) in breadcrumbs"
                                    :key="index"
                                >
                                    <BreadcrumbItem>
                                        <template
                                            v-if="index === breadcrumbs.length - 1"
                                        >
                                            <BreadcrumbPage>{{
                                                item.title
                                            }}</BreadcrumbPage>
                                        </template>
                                        <template v-else>
                                            <BreadcrumbLink as-child>
                                                <Link :href="item.href ?? '#'">{{
                                                    item.title
                                                }}</Link>
                                            </BreadcrumbLink>
                                        </template>
                                    </BreadcrumbItem>
                                    <BreadcrumbSeparator
                                        v-if="index !== breadcrumbs.length - 1"
                                    />
                                </template>
                            </BreadcrumbList>
                        </Breadcrumb>
                    </template>
                </div>
            </header>
            <slot />
        </SidebarInset>
    </SidebarProvider>
</template>
