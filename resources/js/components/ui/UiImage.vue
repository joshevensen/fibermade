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

