<script setup lang="ts">
interface Feature {
    name: string;
    description: string;
    icon?: string;
}

interface ImageConfig {
    url: string;
    alt?: string;
}

interface Props {
    variant?: 'imageLeft' | 'imageRight' | 'featureList' | 'threeColumn';
    subtitle?: string;
    title: string;
    description?: string;
    features: Feature[];
    image?: ImageConfig;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'threeColumn',
});
</script>

<template>
    <!-- ImageLeft variant -->
    <div v-if="variant === 'imageLeft'" class="overflow-hidden py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div
                class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-2"
            >
                <div class="flex items-start justify-end lg:order-first">
                    <img
                        v-if="image"
                        :src="image.url"
                        :alt="image.alt || 'Product screenshot'"
                        class="w-3xl max-w-none rounded-xl shadow-xl ring-1 ring-surface-400/10 sm:w-228"
                    />
                </div>
                <div class="lg:ml-auto lg:pt-4 lg:pl-4">
                    <div class="lg:max-w-lg">
                        <h2
                            v-if="subtitle"
                            class="text-base/7 font-semibold text-primary-500"
                        >
                            {{ subtitle }}
                        </h2>
                        <p
                            class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-surface-900 sm:text-5xl"
                        >
                            {{ title }}
                        </p>
                        <p
                            v-if="description"
                            class="mt-6 text-lg/8 text-surface-600"
                        >
                            {{ description }}
                        </p>
                        <dl
                            class="mt-10 max-w-xl space-y-8 text-base/7 text-surface-600 lg:max-w-none"
                        >
                            <div
                                v-for="feature in features"
                                :key="feature.name"
                                class="relative pl-9"
                            >
                                <dt
                                    class="inline font-semibold text-surface-900"
                                >
                                    <i
                                        v-if="feature.icon"
                                        :class="[
                                            feature.icon,
                                            'absolute top-1 left-1 size-5 text-primary-500',
                                        ]"
                                        aria-hidden="true"
                                    ></i>
                                    {{ feature.name }}
                                </dt>
                                {{ ' ' }}
                                <dd class="inline">
                                    {{ feature.description }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ImageRight variant -->
    <div
        v-else-if="variant === 'imageRight'"
        class="overflow-hidden py-24 sm:py-32"
    >
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div
                class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-2"
            >
                <div class="lg:pt-4 lg:pr-8">
                    <div class="lg:max-w-lg">
                        <h2
                            v-if="subtitle"
                            class="text-base/7 font-semibold text-primary-500"
                        >
                            {{ subtitle }}
                        </h2>
                        <p
                            class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-surface-900 sm:text-5xl"
                        >
                            {{ title }}
                        </p>
                        <p
                            v-if="description"
                            class="mt-6 text-lg/8 text-surface-700"
                        >
                            {{ description }}
                        </p>
                        <dl
                            class="mt-10 max-w-xl space-y-8 text-base/7 text-surface-600 lg:max-w-none"
                        >
                            <div
                                v-for="feature in features"
                                :key="feature.name"
                                class="relative pl-9"
                            >
                                <dt
                                    class="inline font-semibold text-surface-900"
                                >
                                    <i
                                        v-if="feature.icon"
                                        :class="[
                                            feature.icon,
                                            'absolute top-1 left-1 size-5 text-primary-500',
                                        ]"
                                        aria-hidden="true"
                                    ></i>
                                    {{ feature.name }}
                                </dt>
                                {{ ' ' }}
                                <dd class="inline">
                                    {{ feature.description }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <img
                    v-if="image"
                    :src="image.url"
                    :alt="image.alt || 'Product screenshot'"
                    class="w-3xl max-w-none rounded-xl shadow-xl ring-1 ring-surface-400/10 sm:w-228 md:-ml-4 lg:-ml-0"
                />
            </div>
        </div>
    </div>

    <!-- FeatureList variant -->
    <div v-else-if="variant === 'featureList'" class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div
                class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-5"
            >
                <div class="col-span-2">
                    <h2
                        v-if="subtitle"
                        class="text-base/7 font-semibold text-primary-500"
                    >
                        {{ subtitle }}
                    </h2>
                    <p
                        class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-surface-900 sm:text-5xl"
                    >
                        {{ title }}
                    </p>
                    <p
                        v-if="description"
                        class="mt-6 text-base/7 text-surface-700"
                    >
                        {{ description }}
                    </p>
                </div>
                <dl
                    class="col-span-3 grid grid-cols-1 gap-x-8 gap-y-10 text-base/7 text-surface-600 sm:grid-cols-2 lg:gap-y-16"
                >
                    <div
                        v-for="feature in features"
                        :key="feature.name"
                        class="relative pl-9"
                    >
                        <dt class="font-semibold text-surface-900">
                            <i
                                v-if="feature.icon"
                                :class="[
                                    feature.icon,
                                    'absolute top-1 left-0 size-5 text-primary-500',
                                ]"
                                aria-hidden="true"
                            ></i>
                            {{ feature.name }}
                        </dt>
                        <dd class="mt-2">{{ feature.description }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- ThreeColumn variant -->
    <div v-else class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl lg:mx-0">
                <h2
                    class="text-4xl font-semibold tracking-tight text-pretty text-surface-900 sm:text-5xl"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-6 text-lg/8 text-surface-700"
                >
                    {{ description }}
                </p>
            </div>
            <dl
                class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 text-base/7 sm:grid-cols-2 lg:mx-0 lg:max-w-none lg:grid-cols-3"
            >
                <div v-for="feature in features" :key="feature.name">
                    <dt class="font-semibold text-surface-900">
                        {{ feature.name }}
                    </dt>
                    <dd class="mt-1 text-surface-600">
                        {{ feature.description }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>
