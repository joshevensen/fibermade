<script setup lang="ts">
import UiLink from '@/components/ui/UiLink.vue';

interface FAQItem {
    id: number | string;
    question: string;
    answer: string;
}

interface Props {
    variant?: 'twoColumns' | 'threeColumns';
    title: string;
    description: string;
    supportEmailLink: string;
    faqs: FAQItem[];
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'twoColumns',
});
</script>

<template>
    <div class="mx-auto max-w-7xl px-6 py-16 sm:py-24 lg:px-8">
        <h2
            class="text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl dark:text-white"
        >
            {{ title }}
        </h2>
        <p class="mt-6 max-w-2xl text-base/7 text-gray-600 dark:text-gray-400">
            {{ description }}
            <UiLink
                :href="supportEmailLink"
                class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                sending us an email
            </UiLink>
        </p>
        <div class="mt-20">
            <dl
                :class="[
                    'space-y-16 sm:grid sm:space-y-0 sm:gap-x-6 sm:gap-y-16',
                    variant === 'twoColumns'
                        ? 'sm:grid-cols-2 lg:gap-x-10'
                        : 'sm:grid-cols-2 lg:grid-cols-3 lg:gap-x-10',
                ]"
            >
                <div v-for="faq in faqs" :key="faq.id">
                    <dt
                        class="text-base/7 font-semibold text-gray-900 dark:text-white"
                    >
                        {{ faq.question }}
                    </dt>
                    <dd
                        class="mt-2 text-base/7 text-gray-600 dark:text-gray-400"
                    >
                        {{ faq.answer }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>
