<script setup lang="ts">
import PrimeMenu from 'primevue/menu';
import { ref } from 'vue';

interface Props {
    model?: any[];
    popup?: boolean;
    appendTo?: HTMLElement | 'body' | 'self';
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});

const menuRef = ref<InstanceType<typeof PrimeMenu>>();

function toggle(event: Event): void {
    menuRef.value?.toggle(event);
}

defineExpose({
    toggle,
});
</script>

<template>
    <PrimeMenu
        ref="menuRef"
        v-bind="$attrs"
        :model="model"
        :popup="popup"
        :appendTo="appendTo"
    >
        <slot />
    </PrimeMenu>
</template>

