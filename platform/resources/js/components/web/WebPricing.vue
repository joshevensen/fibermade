<script setup lang="ts">
import UiLink from '@/components/ui/UiLink.vue';
import { useIcon } from '@/composables/useIcon';
import { ref } from 'vue';

interface PricingTier {
    id: string;
    name: string;
    href: string;
    description: string;
    features: string[];
    priceMonthly: string;
    priceAnnually?: string;
    featured?: boolean;
    mostPopular?: boolean;
}

interface DiscountedTier {
    title: string;
    description: string;
    href: string;
    buttonText?: string;
}

interface Props {
    variant?: 'single' | 'twoTier' | 'twoTierWithExtra' | 'threeTiers';
    showPricingToggle?: boolean;
    subtitle?: string;
    title: string;
    description?: string;
    tiers: PricingTier[];
    discountedTier?: DiscountedTier;
    singlePrice?: {
        price: string;
        currency?: string;
        buttonText?: string;
        includedLabel?: string;
    };
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'threeTiers',
    showPricingToggle: false,
});

const pricingFrequency = ref<'monthly' | 'annually'>('monthly');
const { IconList } = useIcon();
</script>

<template>
    <!-- SinglePrice variant -->
    <div v-if="variant === 'single'" class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-4xl sm:text-center">
                <h2
                    v-if="subtitle"
                    class="text-base/7 font-semibold text-indigo-600 dark:text-indigo-400"
                >
                    {{ subtitle }}
                </h2>
                <h2
                    class="text-5xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-6xl sm:text-balance dark:text-white"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mx-auto mt-6 max-w-2xl text-lg font-medium text-pretty text-gray-500 sm:text-xl/8 dark:text-gray-400"
                >
                    {{ description }}
                </p>
            </div>
            <div
                v-if="tiers.length > 0"
                class="mx-auto mt-16 max-w-2xl rounded-3xl ring-1 ring-gray-200 sm:mt-20 lg:mx-0 lg:flex lg:max-w-none dark:bg-gray-800/50 dark:ring-white/10"
            >
                <div class="p-8 sm:p-10 lg:flex-auto">
                    <h3
                        class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ tiers[0].name }}
                    </h3>
                    <p
                        class="mt-6 text-base/7 text-gray-600 dark:text-gray-300"
                    >
                        {{ tiers[0].description }}
                    </p>
                    <div class="mt-10 flex items-center gap-x-4">
                        <h4
                            class="flex-none text-sm/6 font-semibold text-indigo-600 dark:text-indigo-400"
                        >
                            {{
                                singlePrice?.includedLabel || "What's included"
                            }}
                        </h4>
                        <div
                            class="h-px flex-auto bg-gray-100 dark:bg-white/10"
                        ></div>
                    </div>
                    <ul
                        role="list"
                        class="mt-8 grid grid-cols-1 gap-4 text-sm/6 text-gray-600 sm:grid-cols-2 sm:gap-6 dark:text-gray-300"
                    >
                        <li
                            v-for="feature in tiers[0].features"
                            :key="feature"
                            class="flex gap-x-3"
                        >
                            <i
                                :class="[
                                    IconList.Check,
                                    'h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400',
                                ]"
                                aria-hidden="true"
                            ></i>
                            {{ feature }}
                        </li>
                    </ul>
                </div>
                <div
                    class="-mt-2 p-2 lg:mt-0 lg:w-full lg:max-w-md lg:shrink-0"
                >
                    <div
                        class="rounded-2xl bg-gray-50 py-10 text-center inset-ring inset-ring-gray-900/5 lg:flex lg:flex-col lg:justify-center lg:py-16 dark:inset-ring-white/10"
                    >
                        <div class="mx-auto max-w-xs px-8">
                            <p
                                class="text-base font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Pay once, own it forever
                            </p>
                            <p
                                class="mt-6 flex items-baseline justify-center gap-x-2"
                            >
                                <span
                                    class="text-5xl font-semibold tracking-tight text-gray-900 dark:text-white"
                                >
                                    {{
                                        singlePrice?.price ||
                                        tiers[0].priceMonthly
                                    }}
                                </span>
                                <span
                                    v-if="singlePrice?.currency"
                                    class="text-sm/6 font-semibold tracking-wide text-gray-600 dark:text-gray-400"
                                >
                                    {{ singlePrice.currency }}
                                </span>
                            </p>
                            <UiLink
                                :href="tiers[0].href"
                                class="mt-10 block w-full rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                            >
                                {{ singlePrice?.buttonText || 'Get access' }}
                            </UiLink>
                            <p
                                class="mt-6 text-xs/5 text-gray-600 dark:text-gray-400"
                            >
                                Invoices and receipts available for easy company
                                reimbursement
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TwoTier variant -->
    <div
        v-else-if="variant === 'twoTier'"
        class="relative isolate px-6 py-24 sm:py-32 lg:px-8"
    >
        <div class="mx-auto max-w-4xl text-center">
            <h2
                v-if="subtitle"
                class="text-base/7 font-semibold text-indigo-600 dark:text-indigo-400"
            >
                {{ subtitle }}
            </h2>
            <p
                class="mt-2 text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-6xl dark:text-white"
            >
                {{ title }}
            </p>
        </div>
        <p
            v-if="description"
            class="mx-auto mt-6 max-w-2xl text-center text-lg font-medium text-pretty text-gray-600 sm:text-xl/8 dark:text-gray-400"
        >
            {{ description }}
        </p>
        <div
            class="mx-auto mt-16 grid max-w-lg grid-cols-1 items-center gap-y-6 sm:mt-20 sm:gap-y-0 lg:max-w-4xl lg:grid-cols-2"
        >
            <div
                v-for="(tier, tierIdx) in tiers.slice(0, 2)"
                :key="tier.id"
                :class="[
                    tier.featured
                        ? 'relative bg-gray-900 shadow-2xl dark:bg-gray-800 dark:shadow-none'
                        : 'bg-white/60 sm:mx-8 lg:mx-0 dark:bg-white/2.5',
                    tier.featured
                        ? ''
                        : tierIdx === 0
                          ? 'rounded-t-3xl sm:rounded-b-none lg:rounded-tr-none lg:rounded-bl-3xl'
                          : 'sm:rounded-t-none lg:rounded-tr-3xl lg:rounded-bl-none',
                    'rounded-3xl p-8 ring-1 ring-gray-900/10 sm:p-10 dark:ring-white/10',
                ]"
            >
                <h3
                    :id="tier.id"
                    :class="[
                        tier.featured
                            ? 'text-indigo-400'
                            : 'text-indigo-600 dark:text-indigo-400',
                        'text-base/7 font-semibold',
                    ]"
                >
                    {{ tier.name }}
                </h3>
                <p class="mt-4 flex items-baseline gap-x-2">
                    <span
                        :class="[
                            tier.featured
                                ? 'text-white'
                                : 'text-gray-900 dark:text-white',
                            'text-5xl font-semibold tracking-tight',
                        ]"
                    >
                        {{ tier.priceMonthly }}
                    </span>
                    <span
                        :class="[
                            tier.featured
                                ? 'text-gray-400'
                                : 'text-gray-500 dark:text-gray-400',
                            'text-base',
                        ]"
                    >
                        /month
                    </span>
                </p>
                <p
                    :class="[
                        tier.featured
                            ? 'text-gray-300'
                            : 'text-gray-600 dark:text-gray-300',
                        'mt-6 text-base/7',
                    ]"
                >
                    {{ tier.description }}
                </p>
                <ul
                    role="list"
                    :class="[
                        tier.featured
                            ? 'text-gray-300'
                            : 'text-gray-600 dark:text-gray-300',
                        'mt-8 space-y-3 text-sm/6 sm:mt-10',
                    ]"
                >
                    <li
                        v-for="feature in tier.features"
                        :key="feature"
                        class="flex gap-x-3"
                    >
                        <i
                            :class="[
                                tier.featured
                                    ? 'text-indigo-400'
                                    : 'text-indigo-600 dark:text-indigo-400',
                                IconList.Check,
                                'h-6 w-5 flex-none',
                            ]"
                            aria-hidden="true"
                        ></i>
                        {{ feature }}
                    </li>
                </ul>
                <UiLink
                    :href="tier.href"
                    :aria-describedby="tier.id"
                    :class="[
                        tier.featured
                            ? 'bg-indigo-500 text-white shadow-xs hover:bg-indigo-400 focus-visible:outline-indigo-500 dark:shadow-none'
                            : 'text-indigo-600 inset-ring inset-ring-indigo-200 hover:inset-ring-indigo-300 focus-visible:outline-indigo-600 dark:bg-white/10 dark:text-white dark:inset-ring-white/5 dark:hover:bg-white/20 dark:hover:inset-ring-white/5 dark:focus-visible:outline-white/75',
                        'mt-8 block rounded-md px-3.5 py-2.5 text-center text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 sm:mt-10',
                    ]"
                >
                    Get started today
                </UiLink>
            </div>
        </div>
    </div>

    <!-- TwoTierWithExtra variant -->
    <div
        v-else-if="variant === 'twoTierWithExtra'"
        class="isolate overflow-hidden bg-gray-900"
    >
        <div
            class="mx-auto max-w-7xl px-6 pt-24 pb-96 text-center sm:pt-32 lg:px-8"
        >
            <div class="mx-auto max-w-4xl">
                <h2
                    v-if="subtitle"
                    class="text-base/7 font-semibold text-indigo-400"
                >
                    {{ subtitle }}
                </h2>
                <p
                    class="mt-2 text-5xl font-semibold tracking-tight text-balance text-white sm:text-6xl"
                >
                    {{ title }}
                </p>
            </div>
            <div class="relative mt-6">
                <p
                    v-if="description"
                    class="mx-auto max-w-2xl text-lg font-medium text-pretty text-gray-400 sm:text-xl/8"
                >
                    {{ description }}
                </p>
            </div>
        </div>
        <div class="flow-root pb-24 sm:pb-32">
            <div class="-mt-80">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div
                        class="mx-auto grid max-w-md grid-cols-1 gap-8 lg:max-w-4xl lg:grid-cols-2"
                    >
                        <div
                            v-for="tier in tiers.slice(0, 2)"
                            :key="tier.id"
                            class="flex flex-col justify-between rounded-3xl p-8 shadow-xl outline-1 outline-gray-900/10 sm:p-10 dark:bg-gray-800 dark:shadow-none dark:-outline-offset-1 dark:outline-white/10"
                        >
                            <div>
                                <h3
                                    :id="tier.id"
                                    class="text-base/7 font-semibold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ tier.name }}
                                </h3>
                                <div class="mt-4 flex items-baseline gap-x-2">
                                    <span
                                        class="text-5xl font-semibold tracking-tight text-gray-900 dark:text-white"
                                    >
                                        {{ tier.priceMonthly }}
                                    </span>
                                    <span
                                        class="text-base/7 font-semibold text-gray-600 dark:text-gray-400"
                                    >
                                        /month
                                    </span>
                                </div>
                                <p
                                    class="mt-6 text-base/7 text-gray-600 dark:text-gray-300"
                                >
                                    {{ tier.description }}
                                </p>
                                <ul
                                    role="list"
                                    class="mt-10 space-y-4 text-sm/6 text-gray-600 dark:text-gray-300"
                                >
                                    <li
                                        v-for="feature in tier.features"
                                        :key="feature"
                                        class="flex gap-x-3"
                                    >
                                        <i
                                            :class="[
                                                IconList.Check,
                                                'h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400',
                                            ]"
                                            aria-hidden="true"
                                        ></i>
                                        {{ feature }}
                                    </li>
                                </ul>
                            </div>
                            <UiLink
                                :href="tier.href"
                                :aria-describedby="tier.id"
                                class="mt-8 block rounded-md bg-indigo-600 px-3.5 py-2 text-center text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-400"
                            >
                                Get started today
                            </UiLink>
                        </div>
                        <div
                            v-if="discountedTier"
                            class="flex flex-col items-start gap-x-8 gap-y-6 rounded-3xl p-8 ring-1 ring-gray-900/10 sm:gap-y-10 sm:p-10 lg:col-span-2 lg:flex-row lg:items-center dark:bg-gray-800/20 dark:ring-white/10"
                        >
                            <div class="lg:min-w-0 lg:flex-1">
                                <h3
                                    class="text-base/7 font-semibold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ discountedTier.title }}
                                </h3>
                                <p
                                    class="mt-1 text-base/7 text-gray-600 dark:text-gray-400"
                                >
                                    {{ discountedTier.description }}
                                </p>
                            </div>
                            <UiLink
                                :href="discountedTier.href"
                                class="rounded-md px-3.5 py-2 text-sm/6 font-semibold text-indigo-600 inset-ring inset-ring-indigo-200 hover:inset-ring-indigo-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-white/10 dark:text-white dark:inset-ring-white/5 dark:hover:bg-white/20 dark:hover:inset-ring-white/5 dark:focus-visible:outline-white/75"
                            >
                                {{
                                    discountedTier.buttonText ||
                                    'Buy discounted license'
                                }}
                                <span aria-hidden="true">&rarr;</span>
                            </UiLink>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ThreeTiers variant -->
    <div v-else class="py-24 sm:py-32">
        <form class="group/tiers">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-4xl text-center">
                    <h2
                        v-if="subtitle"
                        class="text-base/7 font-semibold text-indigo-600 dark:text-indigo-400"
                    >
                        {{ subtitle }}
                    </h2>
                    <p
                        class="mt-2 text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-6xl dark:text-white"
                    >
                        {{ title }}
                    </p>
                </div>
                <p
                    v-if="description"
                    class="mx-auto mt-6 max-w-2xl text-center text-lg font-medium text-pretty text-gray-600 sm:text-xl/8 dark:text-gray-400"
                >
                    {{ description }}
                </p>
                <div v-if="showPricingToggle" class="mt-16 flex justify-center">
                    <fieldset aria-label="Payment frequency">
                        <div
                            class="grid grid-cols-2 gap-x-1 rounded-full p-1 text-center text-xs/5 font-semibold inset-ring inset-ring-gray-200 dark:inset-ring-white/10"
                        >
                            <label
                                class="group relative rounded-full px-2.5 py-1 has-checked:bg-indigo-600 dark:has-checked:bg-indigo-500"
                            >
                                <input
                                    v-model="pricingFrequency"
                                    type="radio"
                                    name="frequency"
                                    value="monthly"
                                    class="absolute inset-0 appearance-none rounded-full"
                                />
                                <span
                                    class="text-gray-500 group-has-checked:text-white dark:text-gray-400"
                                    >Monthly</span
                                >
                            </label>
                            <label
                                class="group relative rounded-full px-2.5 py-1 has-checked:bg-indigo-600 dark:has-checked:bg-indigo-500"
                            >
                                <input
                                    v-model="pricingFrequency"
                                    type="radio"
                                    name="frequency"
                                    value="annually"
                                    class="absolute inset-0 appearance-none rounded-full"
                                />
                                <span
                                    class="text-gray-500 group-has-checked:text-white dark:text-gray-400"
                                    >Annually</span
                                >
                            </label>
                        </div>
                    </fieldset>
                </div>
                <div
                    :class="[
                        'isolate mx-auto mt-10 grid max-w-md grid-cols-1 gap-8 lg:mx-0 lg:max-w-none',
                        showPricingToggle
                            ? 'lg:grid-cols-3'
                            : tiers.length === 3
                              ? 'gap-y-8 sm:mt-20 lg:grid-cols-3'
                              : 'lg:grid-cols-3',
                    ]"
                >
                    <div
                        v-for="(tier, tierIdx) in tiers.slice(0, 3)"
                        :key="tier.id"
                        :class="[
                            showPricingToggle
                                ? 'rounded-3xl p-8 ring-1 ring-gray-200 data-featured:ring-2 data-featured:ring-indigo-600 xl:p-10 dark:bg-gray-800/50 dark:ring-white/15 dark:data-featured:ring-indigo-400'
                                : tier.mostPopular
                                  ? 'lg:z-10 lg:rounded-b-none'
                                  : 'lg:mt-8',
                            !showPricingToggle && tierIdx === 0
                                ? '-mr-px lg:rounded-r-none'
                                : '',
                            !showPricingToggle && tierIdx === tiers.length - 1
                                ? '-ml-px lg:rounded-l-none'
                                : '',
                            !showPricingToggle
                                ? 'flex flex-col justify-between rounded-3xl p-8 inset-ring inset-ring-gray-200 xl:p-10 dark:bg-gray-800/50 dark:inset-ring-gray-700'
                                : '',
                            showPricingToggle ? '' : '',
                        ]"
                        :data-featured="
                            tier.mostPopular || tier.featured
                                ? 'true'
                                : undefined
                        "
                    >
                        <div>
                            <div
                                class="flex items-center justify-between gap-x-4"
                            >
                                <h3
                                    :id="`tier-${tier.id}`"
                                    :class="[
                                        showPricingToggle
                                            ? 'text-lg/8 font-semibold text-gray-900 group-data-featured/tier:text-indigo-600 dark:text-white dark:group-data-featured/tier:text-indigo-400'
                                            : tier.mostPopular
                                              ? 'text-indigo-600 dark:text-indigo-400'
                                              : 'text-gray-900 dark:text-white',
                                        showPricingToggle
                                            ? ''
                                            : 'text-lg/8 font-semibold',
                                    ]"
                                >
                                    {{ tier.name }}
                                </h3>
                                <p
                                    v-if="
                                        (tier.mostPopular || tier.featured) &&
                                        showPricingToggle
                                    "
                                    class="rounded-full bg-indigo-600/10 px-2.5 py-1 text-xs/5 font-semibold text-indigo-600 group-not-data-featured/tier:hidden dark:bg-indigo-500 dark:text-white"
                                >
                                    Most popular
                                </p>
                                <p
                                    v-else-if="tier.mostPopular"
                                    class="rounded-full bg-indigo-600/10 px-2.5 py-1 text-xs/5 font-semibold text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400"
                                >
                                    Most popular
                                </p>
                            </div>
                            <p
                                class="mt-4 text-sm/6 text-gray-600 dark:text-gray-300"
                            >
                                {{ tier.description }}
                            </p>
                            <!-- Monthly price (shown when toggle is disabled or monthly is selected) -->
                            <p
                                v-if="
                                    !showPricingToggle ||
                                    pricingFrequency === 'monthly'
                                "
                                class="mt-6 flex items-baseline gap-x-1"
                            >
                                <span
                                    class="text-4xl font-semibold tracking-tight text-gray-900 dark:text-white"
                                >
                                    {{ tier.priceMonthly }}
                                </span>
                                <span
                                    class="text-sm/6 font-semibold text-gray-600 dark:text-gray-400"
                                >
                                    /month
                                </span>
                            </p>
                            <!-- Annual price (only shown when toggle is enabled and annually is selected) -->
                            <p
                                v-if="
                                    showPricingToggle &&
                                    pricingFrequency === 'annually'
                                "
                                class="mt-6 flex items-baseline gap-x-1"
                            >
                                <span
                                    class="text-4xl font-semibold tracking-tight text-gray-900 dark:text-white"
                                >
                                    {{
                                        tier.priceAnnually || tier.priceMonthly
                                    }}
                                </span>
                                <span
                                    class="text-sm/6 font-semibold text-gray-600 dark:text-gray-400"
                                >
                                    /year
                                </span>
                            </p>
                            <ul
                                role="list"
                                class="mt-8 space-y-3 text-sm/6 text-gray-600 xl:mt-10 dark:text-gray-300"
                            >
                                <li
                                    v-for="feature in tier.features"
                                    :key="feature"
                                    class="flex gap-x-3"
                                >
                                    <i
                                        :class="[
                                            IconList.Check,
                                            'h-6 w-5 flex-none text-indigo-600 dark:text-indigo-400',
                                        ]"
                                        aria-hidden="true"
                                    ></i>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                        <UiLink
                            :href="tier.href"
                            :aria-describedby="tier.id"
                            :class="[
                                showPricingToggle
                                    ? 'mt-6 block w-full rounded-md px-3 py-2 text-center text-sm/6 font-semibold text-indigo-600 inset-ring-1 inset-ring-indigo-200 group-data-featured/tier:bg-indigo-600 group-data-featured/tier:text-white group-data-featured/tier:shadow-xs group-data-featured/tier:inset-ring-0 hover:inset-ring-indigo-300 group-data-featured/tier:hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-white/10 dark:text-white dark:inset-ring dark:inset-ring-white/5 dark:group-data-featured/tier:bg-indigo-500 dark:group-data-featured/tier:shadow-none dark:hover:bg-white/20 dark:hover:inset-ring-white/5 dark:group-data-featured/tier:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500 dark:group-not-data-featured/tier:focus-visible:outline-white/75'
                                    : tier.mostPopular
                                      ? 'bg-indigo-600 text-white shadow-xs hover:bg-indigo-500 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400'
                                      : 'text-indigo-600 inset-ring inset-ring-indigo-200 hover:inset-ring-indigo-300 dark:bg-white/10 dark:text-white dark:inset-ring-white/5 dark:hover:bg-white/20 dark:hover:inset-ring-white/5 dark:focus-visible:outline-white/75',
                                !showPricingToggle
                                    ? 'mt-8 block rounded-md px-3 py-2 text-center text-sm/6 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:focus-visible:outline-indigo-500'
                                    : '',
                            ]"
                        >
                            Buy plan
                        </UiLink>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
