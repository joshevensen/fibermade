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

