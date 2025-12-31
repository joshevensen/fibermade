<script lang="ts">
import type { BadgeTokenSections } from '@primeuix/themes/types/badge';

export const badgeRoot: BadgeTokenSections.Root = {
    borderRadius: '{border.radius.md}',
    padding: '0 0.5rem',
    fontSize: '0.75rem',
    fontWeight: '700',
    minWidth: '1.5rem',
    height: '1.5rem'
};

export const badgeDot: BadgeTokenSections.Dot = {
    size: '0.5rem'
};

export const badgeSm: BadgeTokenSections.Sm = {
    fontSize: '0.625rem',
    minWidth: '1.25rem',
    height: '1.25rem'
};

export const badgeLg: BadgeTokenSections.Lg = {
    fontSize: '0.875rem',
    minWidth: '1.75rem',
    height: '1.75rem'
};

export const badgeXl: BadgeTokenSections.Xl = {
    fontSize: '1rem',
    minWidth: '2rem',
    height: '2rem'
};

export const badgeLight = {
    primary: {
        background: '{primary.color}',
        color: '{primary.contrast.color}'
    },
    secondary: {
        background: '{surface.100}',
        color: '{surface.600}'
    },
    success: {
        background: '{green.500}',
        color: '{surface.0}'
    },
    info: {
        background: '{sky.500}',
        color: '{surface.0}'
    },
    warn: {
        background: '{orange.500}',
        color: '{surface.0}'
    },
    danger: {
        background: '{red.500}',
        color: '{surface.0}'
    },
    contrast: {
        background: '{surface.950}',
        color: '{surface.0}'
    }
};

export const badgeDark = {
    primary: {
        background: '{primary.color}',
        color: '{primary.contrast.color}'
    },
    secondary: {
        background: '{surface.800}',
        color: '{surface.300}'
    },
    success: {
        background: '{green.400}',
        color: '{green.950}'
    },
    info: {
        background: '{sky.400}',
        color: '{sky.950}'
    },
    warn: {
        background: '{orange.400}',
        color: '{orange.950}'
    },
    danger: {
        background: '{red.400}',
        color: '{red.950}'
    },
    contrast: {
        background: '{surface.0}',
        color: '{surface.950}'
    }
};
</script>

<script setup lang="ts">
import PrimeBadge from 'primevue/badge';
import PrimeOverlayBadge from 'primevue/overlaybadge';

interface Props {
    variant?: 'badge' | 'overlay';
    value?: string | number;
    severity?: 'secondary' | 'info' | 'success' | 'warn' | 'danger' | 'contrast';
    size?: 'small' | 'large' | 'xlarge';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'badge',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeBadge
        v-if="variant === 'badge'"
        v-bind="$attrs"
        :value="value"
        :severity="severity"
        :size="size"
    >
        <slot />
    </PrimeBadge>
    <PrimeOverlayBadge
        v-else
        v-bind="$attrs"
        :value="value"
        :severity="severity"
        :size="size"
    >
        <slot />
    </PrimeOverlayBadge>
</template>

