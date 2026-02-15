<script setup lang="ts">
import WebCallToAction from '@/components/web/WebCallToAction.vue';
import WebFeatures from '@/components/web/WebFeatures.vue';
import WebFooter from '@/components/web/WebFooter.vue';
import WebHeader from '@/components/web/WebHeader.vue';
import WebHero from '@/components/web/WebHero.vue';
import { dashboard, login, register } from '@/routes';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const placeholderScreenshot =
    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='600' viewBox='0 0 800 600'%3E%3Crect fill='%23e5e7eb' width='800' height='600'/%3E%3Ctext fill='%239ca3af' font-family='sans-serif' font-size='24' x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle'%3EProduct screenshot%3C/text%3E%3C/svg%3E";

const problemFeatures = [
    {
        name: 'Generic product concepts',
        description:
            "Shopify's variants don't map to how you think — colorways, bases, and weights get lost in the system.",
    },
    {
        name: 'No wholesale workflow',
        description:
            'Wholesale orders end up scattered across emails, spreadsheets, and PDFs with no single place to manage them.',
    },
    {
        name: 'Inventory that misses the point',
        description:
            "Inventory tools don't understand how dyed yarn actually works — from bases to colorways to production.",
    },
    {
        name: "You've adapted to Shopify",
        description:
            "You've learned to think in Shopify's language instead of the system adapting to how fiber businesses work.",
    },
];

const solutionFeatures = [
    {
        name: 'Wholesale ordering',
        description:
            'Stores can browse your line sheet and place orders directly — no more email tag or PDFs.',
    },
    {
        name: 'Fiber-specific terminology',
        description:
            'Colorways, yarn bases, and weights instead of generic variants. The language that fits your business.',
    },
    {
        name: 'Production-aware inventory',
        description:
            'Inventory that understands how dyed yarn works so you can plan and sell with confidence.',
    },
];
</script>

<template>
    <Head title="Fibermade – Shopify for the fiber community" />
    <div class="flex min-h-screen flex-col bg-white">
        <WebHeader
            company-name="Fibermade"
            :navigation="[]"
            :login-link="isAuthenticated ? undefined : login().url"
            :signup-link="isAuthenticated ? undefined : register().url"
        />
        <main class="flex-1">
            <WebHero
                variant="screenshotRight"
                title="Shopify wasn't built for yarn. Fibermade fixes that."
                description="Fibermade adds wholesale ordering to your Shopify store and turns generic variants into fiber-specific concepts — colorways, bases, and the language your business actually uses."
                :badge="{ label: 'Just launched', text: '' }"
                :primary-button="
                    isAuthenticated
                        ? {
                              text: 'Go to Dashboard',
                              href: dashboard().url,
                          }
                        : {
                              text: 'Get started',
                              href: register().url,
                          }
                "
                :secondary-button="
                    isAuthenticated
                        ? undefined
                        : { text: 'Learn more', href: '#features' }
                "
                :screenshot-url-light="placeholderScreenshot"
                :screenshot-url-dark="placeholderScreenshot"
            />

            <WebFeatures
                variant="threeColumn"
                title="Why Shopify falls short for fiber businesses"
                :features="problemFeatures"
            />

            <section id="features">
                <WebFeatures
                    variant="featureList"
                    title="What Fibermade adds to your Shopify store"
                    :features="solutionFeatures"
                />
            </section>

            <WebCallToAction
                variant="centered"
                title="Ready to make Shopify work for your yarn business?"
                description="Just launched — be one of the first to run your fiber business on Shopify the way it should work."
                :primary-button="
                    isAuthenticated
                        ? {
                              text: 'Go to Dashboard',
                              href: dashboard().url,
                          }
                        : {
                              text: 'Get started',
                              href: register().url,
                          }
                "
            />
        </main>

        <WebFooter
            variant="centered"
            company-name="Fibermade"
            description="Shopify for the fiber community. Wholesale and fiber-specific terminology built in."
            :social-links="[]"
            copyright-text="All rights reserved."
        />
    </div>
</template>
