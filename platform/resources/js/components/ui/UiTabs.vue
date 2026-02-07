<script lang="ts">
import type { TabsTokenSections } from '@primeuix/themes/types/tabs';

export const tabsRoot: TabsTokenSections.Root = {
    transitionDuration: '{transition.duration}'
};

export const tabsTablist: TabsTokenSections.Tablist = {
    borderWidth: '0 0 1px 0',
    background: '{content.background}',
    borderColor: '{content.border.color}'
};

export const tabsTab: TabsTokenSections.Tab = {
    background: 'transparent',
    hoverBackground: 'transparent',
    activeBackground: 'transparent',
    borderWidth: '0 0 1px 0',
    borderColor: '{content.border.color}',
    hoverBorderColor: '{content.border.color}',
    activeBorderColor: '{primary.color}',
    color: '{text.muted.color}',
    hoverColor: '{text.color}',
    activeColor: '{primary.color}',
    padding: '1rem 1.125rem',
    fontWeight: '600',
    margin: '0 0 -1px 0',
    gap: '0.5rem',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '-1px',
        shadow: '{focus.ring.shadow}'
    }
};

export const tabsTabpanel: TabsTokenSections.Tabpanel = {
    background: '{content.background}',
    color: '{content.color}',
    padding: '0.875rem 1.125rem 1.125rem 1.125rem',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: 'inset {focus.ring.shadow}'
    }
};

export const tabsNavButton: TabsTokenSections.NavButton = {
    background: '{content.background}',
    color: '{text.muted.color}',
    hoverColor: '{text.color}',
    width: '2.5rem',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '-1px',
        shadow: '{focus.ring.shadow}'
    }
};

export const tabsActiveBar: TabsTokenSections.ActiveBar = {
    height: '1px',
    bottom: '-1px',
    background: '{primary.color}'
};

export const tabsLight = {
    navButton: {
        shadow: '0px 0px 10px 50px rgba(255, 255, 255, 0.6)'
    }
};

export const tabsDark = {
    navButton: {
        shadow: '0px 0px 10px 50px color-mix(in srgb, {content.background}, transparent 50%)'
    }
};
</script>

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

