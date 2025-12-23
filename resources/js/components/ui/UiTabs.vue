<script setup lang="ts">
import PrimeTabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import { Link } from '@inertiajs/vue3';
import type { LinkComponentBaseProps } from '@inertiajs/core';

interface TabDefinition {
    value: string | number;
    label: string;
    disabled?: boolean;
    icon?: string;
    href?: LinkComponentBaseProps['href'];
}

interface Props {
    value: string | number;
    lazy?: boolean;
    scrollable?: boolean;
    showNavigators?: boolean;
    selectOnFocus?: boolean;
    variant?: 'default' | 'menu';
    tabs?: TabDefinition[];
}

const props = withDefaults(defineProps<Props>(), {
    showNavigators: true,
    variant: 'default',
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeTabs
        v-bind="$attrs"
        :value="value"
        :lazy="lazy"
        :scrollable="scrollable"
        :showNavigators="showNavigators"
        :selectOnFocus="selectOnFocus"
    >
        <TabList v-if="tabs && tabs.length > 0">
            <Tab
                v-for="tab in tabs"
                :key="tab.value"
                :value="tab.value"
                :disabled="tab.disabled"
            >
                <Link
                    v-if="variant === 'menu' && tab.href"
                    :href="tab.href"
                    class="flex items-center gap-2 text-inherit"
                >
                    <i v-if="tab.icon" :class="tab.icon" />
                    <span>{{ tab.label }}</span>
                </Link>
                <template v-else>
                    <i v-if="tab.icon" :class="tab.icon" />
                    {{ tab.label }}
                </template>
            </Tab>
        </TabList>
        <TabPanels v-if="variant !== 'menu'">
            <slot />
        </TabPanels>
    </PrimeTabs>
</template>

