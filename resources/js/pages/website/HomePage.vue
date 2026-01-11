<script setup lang="ts">
import WebFooter from '@/components/web/WebFooter.vue';
import WebHeader from '@/components/web/WebHeader.vue';
import WebHero from '@/components/web/WebHero.vue';
import WebNewsletter from '@/components/web/WebNewsletter.vue';
import { dashboard, login, register } from '@/routes';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <Head
        title="Fibermade - Production-first software for hand-dyed yarn businesses"
    />
    <div class="flex min-h-screen flex-col bg-white">
        <WebHeader
            company-name="Fibermade"
            :navigation="[]"
            :login-link="isAuthenticated ? undefined : login().url"
            :signup-link="isAuthenticated ? undefined : register().url"
        />
        <main class="flex-1">
            <!-- Hero Section -->
            <WebHero
                variant="simple"
                title="Production-first software for hand-dyed yarn businesses"
                description="Fibermade is being built around one idea: your production reality should be the system of record, not your storefront."
                :primary-button="
                    isAuthenticated
                        ? {
                              text: 'Go to Dashboard',
                              href: dashboard().url,
                          }
                        : {
                              text: 'Join the early list',
                              href: '#newsletter',
                          }
                "
            />

            <!-- The Problem Section -->
            <section class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
                <div class="mx-auto max-w-2xl lg:text-center">
                    <h2
                        class="text-base leading-7 font-semibold text-indigo-600"
                    >
                        The problem we're obsessed with
                    </h2>
                    <p
                        class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl"
                    >
                        If you dye yarn, you've probably felt this:
                    </p>
                </div>
                <div
                    class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none"
                >
                    <ul
                        role="list"
                        class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-1 lg:gap-y-16"
                    >
                        <li
                            class="flex gap-x-6"
                            v-for="problem in [
                                'Inventory that looks correct online but doesn\'t match what\'s actually on your shelves',
                                'Wholesale orders scattered across emails, spreadsheets, and PDFs',
                                'Dye days planned from memory, notes, or last-minute math',
                                'A constant low-grade anxiety about overcommitting without meaning to',
                            ]"
                            :key="problem"
                        >
                            <div class="flex-auto">
                                <p class="text-lg leading-8 text-gray-600">
                                    {{ problem }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="mx-auto mt-16 max-w-2xl lg:mt-24">
                    <p class="text-center text-lg leading-8 text-gray-600">
                        Most tools are great at selling things.<br />
                        Very few understand how those things are actually made.
                    </p>
                </div>
            </section>

            <!-- What Fibermade Believes Section -->
            <section class="bg-gray-50 py-24 sm:py-32">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2
                            class="text-base leading-7 font-semibold text-indigo-600"
                        >
                            What Fibermade believes
                        </h2>
                        <p
                            class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl"
                        >
                            Production-first principles
                        </p>
                    </div>
                    <div
                        class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none"
                    >
                        <ul
                            role="list"
                            class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-1 lg:gap-y-16"
                        >
                            <li
                                class="flex gap-x-6"
                                v-for="belief in [
                                    'Production reality matters more than presentation',
                                    'Inventory should support planning, not just sales',
                                    'Wholesale shouldn\'t require industrial-scale systems',
                                    'Fewer decisions beats infinite flexibility',
                                    'Replacing tools should be earned, not forced',
                                ]"
                                :key="belief"
                            >
                                <div class="flex-auto">
                                    <p class="text-lg leading-8 text-gray-600">
                                        {{ belief }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                        <p
                            class="mt-16 text-center text-lg leading-8 text-gray-600"
                        >
                            These principles shape everything Fibermade is
                            becoming.
                        </p>
                    </div>
                </div>
            </section>

            <!-- What's Coming Section -->
            <section class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:px-8">
                <div class="mx-auto max-w-2xl lg:text-center">
                    <h2
                        class="text-base leading-7 font-semibold text-indigo-600"
                    >
                        What's coming
                    </h2>
                    <p
                        class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl"
                    >
                        Fibermade is being built in deliberate stages.
                    </p>
                    <p class="mt-6 text-lg leading-8 text-gray-600">
                        It starts by helping dyers:
                    </p>
                </div>
                <div
                    class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none"
                >
                    <ul
                        role="list"
                        class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-1 lg:gap-y-16"
                    >
                        <li
                            class="flex gap-x-6"
                            v-for="feature in [
                                'plan production with confidence',
                                'manage wholesale without spreadsheets',
                                'trust their inventory again',
                            ]"
                            :key="feature"
                        >
                            <div class="flex-auto">
                                <p class="text-lg leading-8 text-gray-600">
                                    {{ feature }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="mx-auto mt-16 max-w-2xl lg:mt-24">
                    <p class="text-center text-lg leading-8 text-gray-600">
                        Some sellers may use Fibermade alongside existing
                        storefronts.<br />
                        Others may eventually use it as their primary selling
                        surface.
                    </p>
                    <p class="mt-8 text-center text-lg leading-8 text-gray-600">
                        The exact shape will be earned through real use, not
                        promised upfront.
                    </p>
                </div>
            </section>

            <!-- Who This Is For Section -->
            <section class="bg-gray-50 py-24 sm:py-32">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2
                            class="text-base leading-7 font-semibold text-indigo-600"
                        >
                            Who this is for
                        </h2>
                        <p
                            class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl"
                        >
                            Fibermade is for hand-dyed yarn businesses that:
                        </p>
                    </div>
                    <div
                        class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none"
                    >
                        <div
                            class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-2 lg:gap-y-16"
                        >
                            <div>
                                <h3
                                    class="text-lg leading-8 font-semibold text-gray-900"
                                >
                                    Good fit
                                </h3>
                                <ul role="list" class="mt-6 space-y-4">
                                    <li
                                        class="flex gap-x-3"
                                        v-for="fit in [
                                            'work in small batches',
                                            'sell wholesale selectively',
                                            'care about reliability without going industrial',
                                            'want less mental load, not more features',
                                        ]"
                                        :key="fit"
                                    >
                                        <p
                                            class="text-base leading-7 text-gray-600"
                                        >
                                            {{ fit }}
                                        </p>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h3
                                    class="text-lg leading-8 font-semibold text-gray-900"
                                >
                                    Probably not for
                                </h3>
                                <ul role="list" class="mt-6 space-y-4">
                                    <li
                                        class="flex gap-x-3"
                                        v-for="notFit in [
                                            'drop-shippers',
                                            'mass-produced goods',
                                            'sellers who want endless customization over clear defaults',
                                        ]"
                                        :key="notFit"
                                    >
                                        <p
                                            class="text-base leading-7 text-gray-600"
                                        >
                                            {{ notFit }}
                                        </p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p
                            class="mt-16 text-center text-lg leading-8 text-gray-600"
                        >
                            That focus is intentional.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Newsletter Section -->
            <section id="newsletter" class="bg-white py-24 sm:py-32">
                <WebNewsletter
                    variant="stacked"
                    title="If you want updates as it takes shape — and a chance to influence what gets built first — join the early list."
                    email-placeholder="Enter your email"
                    submit-button-text="Join the early list"
                />
                <p
                    class="mx-auto mt-8 max-w-2xl text-center text-sm leading-6 text-gray-600"
                >
                    No spam. No hype. Just progress.
                </p>
            </section>
        </main>

        <WebFooter
            variant="centered"
            company-name="Fibermade"
            description="Built by people who dye yarn and live with the consequences of their tools. Independent. Production-first. Opinionated on purpose."
            :social-links="[]"
            copyright-text="All rights reserved."
        />
    </div>
</template>
