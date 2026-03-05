<script lang="ts">
import type { InputNumberTokenSections } from '@primeuix/themes/types/inputnumber';

export const inputNumberRoot: InputNumberTokenSections.Root = {
    transitionDuration: '{transition.duration}'
};

export const inputNumberButton: InputNumberTokenSections.Button = {
    width: '2.5rem',
    borderRadius: '{form.field.border.radius}',
    verticalPadding: '{form.field.padding.y}'
};

export const inputNumberLight = {
    button: {
        background: 'transparent',
        hoverBackground: '{surface.100}',
        activeBackground: '{surface.200}',
        borderColor: '{form.field.border.color}',
        hoverBorderColor: '{form.field.border.color}',
        activeBorderColor: '{form.field.border.color}',
        color: '{surface.400}',
        hoverColor: '{surface.500}',
        activeColor: '{surface.600}'
    }
};

export const inputNumberDark = {
    button: {
        background: 'transparent',
        hoverBackground: '{surface.800}',
        activeBackground: '{surface.700}',
        borderColor: '{form.field.border.color}',
        hoverBorderColor: '{form.field.border.color}',
        activeBorderColor: '{form.field.border.color}',
        color: '{surface.400}',
        hoverColor: '{surface.300}',
        activeColor: '{surface.200}'
    }
};
</script>

<script setup lang="ts">
import PrimeInputNumber from 'primevue/inputnumber';
import type { InputNumberProps } from 'primevue/inputnumber';
import { computed } from 'vue';

interface Props {
    modelValue?: number | null;
    showButtons?: boolean;
    buttonLayout?: 'horizontal' | 'vertical' | 'stacked';
    min?: number;
    max?: number;
    step?: number;
    size?: 'small' | 'large';
    invalid?: boolean;
    disabled?: boolean;
    variant?: 'outlined' | 'filled';
    readonly?: boolean;
    placeholder?: string;
    showClear?: boolean;
    fluid?: boolean;
    pt?: InputNumberProps['pt'];
}

const props = withDefaults(defineProps<Props>(), {
    fluid: true,
    step: 1,
});

defineOptions({
    inheritAttrs: false,
});

const overflowFixPt = computed(() => ({
    root: { class: props.fluid ? 'min-w-0 w-full' : 'min-w-0' },
}));

type PtRoot = { root?: { class?: string }; [key: string]: unknown };

const mergePt = computed(() => {
    const base = overflowFixPt.value;
    const user = props.pt as PtRoot | undefined;
    if (!user) return base;
    const userRoot = user.root;
    const rootClass = [base.root?.class, userRoot?.class].filter(Boolean);
    return {
        ...user,
        root: {
            ...userRoot,
            class: rootClass,
        },
    };
});
</script>

<template>
    <div :class="['min-w-0 max-w-full', fluid && 'w-full']">
        <PrimeInputNumber
            v-bind="$attrs"
            :modelValue="modelValue"
            :showButtons="showButtons"
            :buttonLayout="buttonLayout"
            :min="min"
            :max="max"
            :step="step"
            :size="size"
            :invalid="invalid"
            :disabled="disabled"
            :variant="variant"
            :readonly="readonly"
            :placeholder="placeholder"
            :showClear="showClear"
            :fluid="fluid"
            :pt="mergePt"
        >
            <slot />
        </PrimeInputNumber>
    </div>
</template>

