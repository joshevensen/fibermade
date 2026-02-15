<script setup lang="ts">
import { computed } from 'vue';

interface TestimonialAuthor {
    name: string;
    handle?: string;
    role?: string;
    imageUrl: string;
}

interface Testimonial {
    body: string;
    author: TestimonialAuthor;
}

interface Props {
    variant?: 'grid' | 'simple' | 'twoColumn';
    background?: 'white' | 'surface' | 'primary';
    testimonials: Testimonial[];
    logoUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'simple',
    background: 'white',
});

const backgroundClass = computed(() => {
    switch (props.background) {
        case 'surface':
            return 'bg-surface-200';
        case 'primary':
            return 'bg-primary-500';
        default:
            return 'bg-white';
    }
});
</script>

<template>
    <!-- Grid variant -->
    <div v-if="variant === 'grid'" :class="[backgroundClass, 'py-24 sm:py-32']">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div
                class="mx-auto flow-root max-w-2xl sm:mt-20 lg:mx-0 lg:max-w-none"
            >
                <div
                    class="-mt-8 sm:-mx-4 sm:columns-2 sm:text-[0] lg:columns-3"
                >
                    <div
                        v-for="testimonial in testimonials"
                        :key="
                            testimonial.author.handle || testimonial.author.name
                        "
                        class="pt-8 sm:inline-block sm:w-full sm:px-4"
                    >
                        <figure class="rounded-2xl bg-surface-50 p-8 text-sm/6">
                            <blockquote class="text-surface-900">
                                <p>{{ `"${testimonial.body}"` }}</p>
                            </blockquote>
                            <figcaption class="mt-6 flex items-center gap-x-4">
                                <img
                                    class="size-10 rounded-full bg-surface-50"
                                    :src="testimonial.author.imageUrl"
                                    alt=""
                                />
                                <div>
                                    <div class="font-semibold text-surface-900">
                                        {{ testimonial.author.name }}
                                    </div>
                                    <div
                                        v-if="testimonial.author.handle"
                                        class="text-surface-600"
                                    >
                                        {{ `@${testimonial.author.handle}` }}
                                    </div>
                                </div>
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple variant -->
    <section
        v-else-if="variant === 'simple'"
        :class="[
            backgroundClass,
            'relative isolate overflow-hidden px-6 py-24 sm:py-32 lg:px-8',
        ]"
    >
        <div class="mx-auto max-w-2xl lg:max-w-4xl">
            <img v-if="logoUrl" class="mx-auto h-12" :src="logoUrl" alt="" />
            <figure v-if="testimonials.length > 0" class="mt-10">
                <blockquote
                    class="text-center text-xl/8 font-semibold text-surface-900 sm:text-2xl/9"
                >
                    <p>"{{ testimonials[0].body }}"</p>
                </blockquote>
                <figcaption class="mt-10">
                    <img
                        class="mx-auto size-10 rounded-full"
                        :src="testimonials[0].author.imageUrl"
                        alt=""
                    />
                    <div
                        class="mt-4 flex items-center justify-center space-x-3 text-base"
                    >
                        <div class="font-semibold text-surface-900">
                            {{ testimonials[0].author.name }}
                        </div>
                        <svg
                            viewBox="0 0 2 2"
                            width="3"
                            height="3"
                            aria-hidden="true"
                            class="fill-surface-900"
                        >
                            <circle cx="1" cy="1" r="1" />
                        </svg>
                        <div
                            v-if="testimonials[0].author.role"
                            class="text-surface-600"
                        >
                            {{ testimonials[0].author.role }}
                        </div>
                    </div>
                </figcaption>
            </figure>
        </div>
    </section>

    <!-- TwoColumn variant -->
    <section v-else :class="[backgroundClass, 'py-24 sm:py-32']">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div
                class="mx-auto grid max-w-2xl grid-cols-1 lg:mx-0 lg:max-w-none lg:grid-cols-2"
            >
                <template
                    v-for="(testimonial, index) in testimonials.slice(0, 2)"
                    :key="testimonial.author.name"
                >
                    <div
                        :class="[
                            'flex flex-col pb-10 sm:pb-16 lg:pr-8 lg:pb-0 xl:pr-20',
                            index === 1
                                ? 'border-t border-surface-900/10 pt-10 sm:pt-16 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8 xl:pl-20'
                                : '',
                        ]"
                    >
                        <img
                            v-if="logoUrl"
                            class="h-12 self-start"
                            :src="logoUrl"
                            alt=""
                        />
                        <figure
                            class="mt-10 flex flex-auto flex-col justify-between"
                        >
                            <blockquote class="text-lg/8 text-surface-900">
                                <p>"{{ testimonial.body }}"</p>
                            </blockquote>
                            <figcaption class="mt-10 flex items-center gap-x-6">
                                <img
                                    class="size-14 rounded-full bg-surface-50"
                                    :src="testimonial.author.imageUrl"
                                    alt=""
                                />
                                <div class="text-base">
                                    <div class="font-semibold text-surface-900">
                                        {{ testimonial.author.name }}
                                    </div>
                                    <div
                                        v-if="testimonial.author.role"
                                        class="mt-1 text-surface-500"
                                    >
                                        {{ testimonial.author.role }}
                                    </div>
                                </div>
                            </figcaption>
                        </figure>
                    </div>
                </template>
            </div>
        </div>
    </section>
</template>
