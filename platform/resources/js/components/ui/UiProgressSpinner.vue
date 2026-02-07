<script lang="ts">
import type { ProgressSpinnerTokenSections } from '@primeuix/themes/types/progressspinner';

export const progressSpinnerLight = {
    root: {
        colorOne: '{red.500}',
        colorTwo: '{blue.500}',
        colorThree: '{green.500}',
        colorFour: '{yellow.500}'
    }
};

export const progressSpinnerDark = {
    root: {
        colorOne: '{red.400}',
        colorTwo: '{blue.400}',
        colorThree: '{green.400}',
        colorFour: '{yellow.400}'
    }
};
</script>

<script setup lang="ts">
import PrimeProgressSpinner from 'primevue/progressspinner';

interface Props {
    strokeWidth?: string;
    fill?: string;
    animationDuration?: string;
}

const props = withDefaults(defineProps<Props>(), {
    strokeWidth: '2',
    animationDuration: '2s',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeProgressSpinner
        v-bind="$attrs"
        :strokeWidth="strokeWidth"
        :fill="fill"
        :animationDuration="animationDuration"
    >
        <slot />
    </PrimeProgressSpinner>
</template>

