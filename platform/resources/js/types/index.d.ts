import { InertiaLinkProps } from '@inertiajs/vue3';
import type { Component } from 'vue';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: Component;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    initials: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface ExternalIdentifier {
    integration_type: string;
    external_type: string;
    external_id: string;
    data?: Record<string, any>;
}
