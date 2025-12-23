<script setup lang="ts">
import PrimeDrawer from 'primevue/drawer';

interface Props {
    visible?: boolean;
    position?: 'left' | 'right' | 'top' | 'bottom' | 'full';
    header?: string;
    dismissable?: boolean;
    showCloseIcon?: boolean;
    modal?: boolean;
    blockScroll?: boolean;
    closeOnEscape?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    visible: false,
    position: 'left',
    dismissable: true,
    showCloseIcon: true,
    modal: true,
    closeOnEscape: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeDrawer
        v-bind="$attrs"
        :visible="visible"
        :position="position"
        :header="header"
        :dismissable="dismissable"
        :showCloseIcon="showCloseIcon"
        :modal="modal"
        :blockScroll="blockScroll"
        :closeOnEscape="closeOnEscape"
    >
        <template #header>
            <slot name="header" />
        </template>
        <template #footer>
            <slot name="footer" />
        </template>
        <slot />
    </PrimeDrawer>
</template>

