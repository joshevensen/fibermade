<script lang="ts">
import type { FileUploadTokenSections } from '@primeuix/themes/types/fileupload';

export const fileUploadRoot: FileUploadTokenSections.Root = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    color: '{content.color}',
    borderRadius: '{content.border.radius}',
    transitionDuration: '{transition.duration}'
};

export const fileUploadHeader: FileUploadTokenSections.Header = {
    background: 'transparent',
    color: '{text.color}',
    padding: '1.125rem',
    borderColor: 'unset',
    borderWidth: '0',
    borderRadius: '0',
    gap: '0.5rem'
};

export const fileUploadContent: FileUploadTokenSections.Content = {
    highlightBorderColor: '{primary.color}',
    padding: '0 1.125rem 1.125rem 1.125rem',
    gap: '1rem'
};

export const fileUploadFile: FileUploadTokenSections.File = {
    padding: '1rem',
    gap: '1rem',
    borderColor: '{content.border.color}',
    info: {
        gap: '0.5rem'
    }
};

export const fileUploadFileList: FileUploadTokenSections.FileList = {
    gap: '0.5rem'
};

export const fileUploadProgressbar: FileUploadTokenSections.Progressbar = {
    height: '0.25rem'
};

export const fileUploadBasic: FileUploadTokenSections.Basic = {
    gap: '0.5rem'
};
</script>

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

