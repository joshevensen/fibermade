<script lang="ts">
import type { AvatarTokenSections } from '@primeuix/themes/types/avatar';

export const avatarRoot: AvatarTokenSections.Root = {
    width: '2rem',
    height: '2rem',
    fontSize: '1rem',
    background: '{content.border.color}',
    color: '{content.color}',
    borderRadius: '{content.border.radius}'
};

export const avatarIcon: AvatarTokenSections.Icon = {
    size: '1rem'
};

export const avatarGroup: AvatarTokenSections.Group = {
    borderColor: '{content.background}',
    offset: '-0.75rem'
};

export const avatarLg: AvatarTokenSections.Lg = {
    width: '3rem',
    height: '3rem',
    fontSize: '1.5rem',
    icon: {
        size: '1.5rem'
    },
    group: {
        offset: '-1rem'
    }
};

export const avatarXl: AvatarTokenSections.Xl = {
    width: '4rem',
    height: '4rem',
    fontSize: '2rem',
    icon: {
        size: '2rem'
    },
    group: {
        offset: '-1.5rem'
    }
};
</script>

<script setup lang="ts">
import PrimeAvatar from 'primevue/avatar';

interface Props {
    label?: string;
    icon?: string;
    image?: string;
    size?: 'large' | 'normal' | 'xlarge';
    shape?: 'square' | 'circle';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'normal',
    shape: 'square',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeAvatar
        v-bind="$attrs"
        :label="label"
        :icon="icon"
        :image="image"
        :size="size"
        :shape="shape"
    >
        <slot />
    </PrimeAvatar>
</template>

