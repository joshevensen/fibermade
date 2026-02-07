<script lang="ts">
import type { EditorTokenSections } from '@primeuix/themes/types/editor';

export const editorToolbar: EditorTokenSections.Toolbar = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    borderRadius: '{content.border.radius}'
};

export const editorToolbarItem: EditorTokenSections.ToolbarItem = {
    color: '{text.muted.color}',
    hoverColor: '{text.color}',
    activeColor: '{primary.color}'
};

export const editorOverlay: EditorTokenSections.Overlay = {
    background: '{overlay.select.background}',
    borderColor: '{overlay.select.border.color}',
    borderRadius: '{overlay.select.border.radius}',
    color: '{overlay.select.color}',
    shadow: '{overlay.select.shadow}',
    padding: '{list.padding}'
};

export const editorOverlayOption: EditorTokenSections.OverlayOption = {
    focusBackground: '{list.option.focus.background}',
    color: '{list.option.color}',
    focusColor: '{list.option.focus.color}',
    padding: '{list.option.padding}',
    borderRadius: '{list.option.border.radius}'
};

export const editorContent: EditorTokenSections.Content = {
    background: '{content.background}',
    borderColor: '{content.border.color}',
    color: '{content.color}',
    borderRadius: '{content.border.radius}'
};
</script>

<script setup lang="ts">
import PrimeEditor from 'primevue/editor';

interface Props {
    modelValue?: string;
    placeholder?: string;
    readonly?: boolean;
    invalid?: boolean;
    formats?: any[];
    modules?: any;
    editorStyle?: any;
}

defineProps<Props>();

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PrimeEditor
        v-bind="$attrs"
        :modelValue="modelValue"
        :placeholder="placeholder"
        :readonly="readonly"
        :invalid="invalid"
        :formats="formats"
        :modules="modules"
        :editorStyle="{ minHeight: '200px', ...editorStyle }"
    >
        <slot />
    </PrimeEditor>
</template>

