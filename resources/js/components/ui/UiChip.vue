<script lang="ts">
import type { ChipTokenSections } from '@primeuix/themes/types/chip';

export const chipRoot: ChipTokenSections.Root = {
    borderRadius: '16px',
    paddingX: '0.75rem',
    paddingY: '0.5rem',
    gap: '0.5rem',
    transitionDuration: '{transition.duration}'
};

export const chipImage: ChipTokenSections.Image = {
    width: '2rem',
    height: '2rem'
};

export const chipIcon: ChipTokenSections.Icon = {
    size: '1rem'
};

export const chipRemoveIcon: ChipTokenSections.RemoveIcon = {
    size: '1rem',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{form.field.focus.ring.shadow}'
    }
};

export const chipLight = {
    root: {
        background: '{surface.100}',
        color: '{surface.800}'
    },
    icon: {
        color: '{surface.800}'
    },
    removeIcon: {
        color: '{surface.800}'
    }
};

export const chipDark = {
    root: {
        background: '{surface.800}',
        color: '{surface.0}'
    },
    icon: {
        color: '{surface.0}'
    },
    removeIcon: {
        color: '{surface.0}'
    }
};
</script>

<script setup lang="ts">
import PrimeChip from 'primevue/chip';

interface Props {
    label?: string | number;
    icon?: string;
    image?: string;
    removable?: boolean;
    removeIcon?: string;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeChip
        v-bind="$attrs"
        :label="label"
        :icon="icon"
        :image="image"
        :removable="removable"
        :removeIcon="removeIcon"
    >
        <slot />
    </PrimeChip>
</template>

