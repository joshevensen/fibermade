<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ButtonConfig {
    text: string;
    href: string;
}

interface Props {
    variant?: 'justified' | 'centered' | 'stacked';
    background?: 'white' | 'surface' | 'primary';
    title: string;
    description?: string;
    primaryButton: ButtonConfig;
    secondaryButton?: ButtonConfig;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'centered',
    background: 'white',
});

const backgroundClass = computed(() => {
    switch (props.background) {
        case 'surface':
            return 'bg-surface-200';
        case 'primary':
            return 'bg-primary-500';
        default:
            return 'bg-surface-50';
    }
});
</script>

<template>
    <!-- Justified variant: side-by-side layout -->
    <div
        v-if="variant === 'justified'"
        :class="[
            backgroundClass,
            'mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:flex lg:items-center lg:justify-between lg:px-8',
        ]"
    >
        <h2
            class="max-w-2xl text-4xl font-semibold tracking-tight text-surface-900 sm:text-5xl"
        >
            {{ title }}
        </h2>
        <div class="mt-10 flex items-center gap-x-6 lg:mt-0 lg:shrink-0">
            <UiButton
                type="button"
                severity="primary"
                @click="router.visit(primaryButton.href)"
            >
                {{ primaryButton.text }}
            </UiButton>
            <UiButton
                v-if="secondaryButton"
                type="button"
                text
                class="text-sm/6 font-semibold text-surface-900 hover:opacity-80"
                @click="router.visit(secondaryButton.href)"
            >
                {{ secondaryButton.text }}
                <span aria-hidden="true">→</span>
            </UiButton>
        </div>
    </div>

    <!-- Centered variant: everything centered with description -->
    <div
        v-else-if="variant === 'centered'"
        :class="[backgroundClass, 'px-6 py-12 sm:py-16 lg:px-8']"
    >
        <div class="mx-auto max-w-2xl text-center">
            <h2
                class="text-4xl font-semibold tracking-tight text-balance text-surface-50 sm:text-5xl"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="mx-auto mt-6 max-w-xl text-lg/8 text-pretty text-surface-50"
            >
                {{ description }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <UiButton
                    type="button"
                    severity="secondary"
                    size="large"
                    @click="router.visit(primaryButton.href)"
                >
                    {{ primaryButton.text }}
                </UiButton>
                <UiButton
                    v-if="secondaryButton"
                    type="button"
                    text
                    class="text-sm/6 font-semibold text-surface-50 hover:opacity-90"
                    @click="router.visit(secondaryButton.href)"
                >
                    {{ secondaryButton.text }}
                    <span aria-hidden="true">→</span>
                </UiButton>
            </div>
        </div>
    </div>

    <!-- Stacked variant: title and buttons stacked -->
    <div
        v-else
        :class="[
            backgroundClass,
            'mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8',
        ]"
    >
        <h2
            class="max-w-2xl text-4xl font-semibold tracking-tight text-balance text-surface-900 sm:text-5xl"
        >
            {{ title }}
        </h2>
        <div class="mt-10 flex items-center gap-x-6">
            <UiButton
                type="button"
                severity="primary"
                @click="router.visit(primaryButton.href)"
            >
                {{ primaryButton.text }}
            </UiButton>
            <UiButton
                v-if="secondaryButton"
                type="button"
                text
                class="text-sm/6 font-semibold text-surface-900"
                @click="router.visit(secondaryButton.href)"
            >
                {{ secondaryButton.text }}
                <span aria-hidden="true">→</span>
            </UiButton>
        </div>
    </div>
</template>
