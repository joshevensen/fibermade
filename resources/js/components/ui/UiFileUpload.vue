<script setup lang="ts">
import PrimeFileUpload from 'primevue/fileupload';

interface Props {
    name?: string;
    url?: string;
    mode?: 'advanced' | 'basic';
    multiple?: boolean;
    accept?: string;
    disabled?: boolean;
    auto?: boolean;
    maxFileSize?: number;
    fileLimit?: number;
    customUpload?: boolean;
    showUploadButton?: boolean;
    showCancelButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'advanced',
    showUploadButton: true,
    showCancelButton: true,
});

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeFileUpload
        v-bind="$attrs"
        :name="name"
        :url="url"
        :mode="mode"
        :multiple="multiple"
        :accept="accept"
        :disabled="disabled"
        :auto="auto"
        :maxFileSize="maxFileSize"
        :fileLimit="fileLimit"
        :customUpload="customUpload"
        :showUploadButton="showUploadButton"
        :showCancelButton="showCancelButton"
    >
        <slot />
    </PrimeFileUpload>
</template>

