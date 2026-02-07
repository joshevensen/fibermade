<script lang="ts">
import type { CardTokenSections } from '@primeuix/themes/types/card';

export const cardRoot: CardTokenSections.Root = {
    background: '{content.background}',
    borderRadius: '{border.radius.xl}',
    color: '{content.color}',
    shadow: '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)'
};

export const cardBody: CardTokenSections.Body = {
    padding: '1rem',
    gap: '0.5rem'
};

export const cardCaption: CardTokenSections.Caption = {
    gap: '0'
};

export const cardTitle: CardTokenSections.Title = {
    fontSize: '1.25rem',
    fontWeight: '500'
};

export const cardSubtitle: CardTokenSections.Subtitle = {
    color: '{text.muted.color}'
};
</script>

<script setup lang="ts">
import PrimeCard from 'primevue/card';
import { computed } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const pt = computed(() => ({
    root: {
        class: 'bg-surface-0! border border-surface-200 shadow-none!',
    },
}));
</script>

<template>
    <PrimeCard v-bind="$attrs" :pt="pt">
        <template #title>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <slot name="title" />
                </div>
                <div v-if="$slots['header-right']">
                    <slot name="header-right" />
                </div>
            </div>
        </template>
        <template #subtitle>
            <slot name="subtitle" />
        </template>
        <template #content>
            <slot name="content" />
        </template>
        <template #footer>
            <slot name="footer" />
        </template>
        <slot />
    </PrimeCard>
</template>

