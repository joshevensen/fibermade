<script lang="ts">
import type { ImageTokenSections } from '@primeuix/themes/types/image';

export const imageRoot: ImageTokenSections.Root = {
    transitionDuration: '{transition.duration}'
};

export const imagePreview: ImageTokenSections.Preview = {
    icon: {
        size: '1.5rem'
    },
    mask: {
        background: '{mask.background}',
        color: '{mask.color}'
    }
};

export const imageToolbar: ImageTokenSections.Toolbar = {
    position: {
        left: 'auto',
        right: '1rem',
        top: '1rem',
        bottom: 'auto'
    },
    blur: '8px',
    background: 'rgba(255,255,255,0.1)',
    borderColor: 'rgba(255,255,255,0.2)',
    borderWidth: '1px',
    borderRadius: '30px',
    padding: '.5rem',
    gap: '0.5rem'
};

export const imageAction: ImageTokenSections.Action = {
    hoverBackground: 'rgba(255,255,255,0.1)',
    color: '{surface.50}',
    hoverColor: '{surface.0}',
    size: '3rem',
    iconSize: '1.5rem',
    borderRadius: '50%',
    focusRing: {
        width: '{focus.ring.width}',
        style: '{focus.ring.style}',
        color: '{focus.ring.color}',
        offset: '{focus.ring.offset}',
        shadow: '{focus.ring.shadow}'
    }
};
</script>

<script setup lang="ts">
import PrimeImage from 'primevue/image';
import { computed } from 'vue';

interface Props {
    src?: string | null;
    alt?: string;
    preview?: boolean;
    imageStyle?: any;
    imageClass?: any;
    indicatorIcon?: string;
    previewIcon?: string;
    zoomInDisabled?: boolean;
    zoomOutDisabled?: boolean;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    preview: false,
    placeholder: '—',
    zoomInDisabled: false,
    zoomOutDisabled: false,
});

defineOptions({
    inheritAttrs: false,
});

const hasImage = computed(() => {
    return props.src != null && props.src !== '';
});
</script>

<template>
    <div
        v-if="!hasImage"
        v-bind="$attrs"
        class="flex items-center justify-center bg-surface-200"
    >
        <slot name="placeholder">
            <span class="text-surface-400">{{ placeholder }}</span>
        </slot>
    </div>
    <PrimeImage
        v-else
        v-bind="$attrs"
        :src="src"
        :alt="alt"
        :preview="preview"
        :image-style="imageStyle"
        :image-class="imageClass"
        :indicator-icon="indicatorIcon"
        :preview-icon="previewIcon"
        :zoom-in-disabled="zoomInDisabled"
        :zoom-out-disabled="zoomOutDisabled"
    >
        <template #previewicon>
            <slot name="previewicon" />
        </template>
        <template #image>
            <slot name="image" />
        </template>
        <template #preview="slotProps">
            <slot name="preview" v-bind="slotProps" />
        </template>
    </PrimeImage>
</template>

