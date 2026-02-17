<script setup lang="ts">
import WebCallToAction from '@/components/web/WebCallToAction.vue';
import WebFeatures from '@/components/web/WebFeatures.vue';
import WebFooter from '@/components/web/WebFooter.vue';
import WebFrequentlyAskedQuestions from '@/components/web/WebFrequentlyAskedQuestions.vue';
import WebHeader from '@/components/web/WebHeader.vue';
import WebHero from '@/components/web/WebHero.vue';
import WebPricing from '@/components/web/WebPricing.vue';
import { dashboard, login, register } from '@/routes';
import { Head, usePage } from '@inertiajs/vue3';
import {
    IconBookOff,
    IconBox,
    IconCopy,
    IconInbox,
    IconPackage,
    IconPackageOff,
    IconPalette,
    IconShoppingCart,
    IconUserCircle,
    IconUsers,
} from '@tabler/icons-vue';
import { computed } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const headerNavigation = [
    { name: 'Features', href: '#features' },
    { name: 'Pricing', href: '#pricing' },
    { name: 'FAQs', href: '#faqs' },
];

const dashboardScreenshotUrl = '/fibermade-dashboard.png';

const problemFeatures = [
    {
        name: 'Scattered wholesale orders',
        description:
            'Orders come in through emails, texts, and PDFs. There\u2019s no single place to track what\u2019s been requested, accepted, or fulfilled.',
        icon: IconInbox,
    },
    {
        name: 'Generic product model',
        description:
            'Shopify thinks in products and variants. You think in colorways, bases, and weights \u2014 and something always gets lost in translation.',
        icon: IconBox,
    },
    {
        name: 'Inventory that doesn\u2019t fit your workflow',
        description:
            'Shopify tracks stock counts, but it doesn\u2019t understand dyeing batches, undyed inventory, or what\u2019s in production. You end up tracking the real picture somewhere else.',
        icon: IconPackageOff,
    },
    {
        name: 'No wholesale catalog',
        description:
            'Stores can\u2019t see your full catalog and place orders the way retail customers can. They have to ask you what\u2019s available.',
        icon: IconBookOff,
    },
    {
        name: 'Too many tools, none connected',
        description:
            'You\u2019re juggling Shopify, spreadsheets, email, and maybe an invoicing app. Nothing talks to each other, so you\u2019re the integration layer.',
        icon: IconCopy,
    },
    {
        name: 'Painful catalog updates',
        description:
            'Want to change the price of a base or add a new one across all your colorways? In Shopify, that means editing every product and its variants one by one.',
        icon: IconUsers,
    },
];

const solutionFeatures = [
    {
        name: 'Wholesale ordering',
        description:
            'Stores can browse your wholesale catalog and place orders directly — no more email tag or PDFs.',
        icon: IconShoppingCart,
    },
    {
        name: 'Fiber-specific terminology',
        description:
            'Colorways, yarn bases, and weights instead of generic variants. The language that fits your business.',
        icon: IconPalette,
    },
    {
        name: 'Production-aware inventory',
        description:
            'Inventory that understands how dyed yarn works so you can plan and sell with confidence.',
        icon: IconPackage,
    },
    {
        name: 'Store relationship management',
        description:
            'Set wholesale terms per store — discounts, minimums, lead times, and payment terms — all in one place.',
        icon: IconUserCircle,
    },
];

const pricingTier = {
    id: 'fibermade',
    name: 'Fibermade for Shopify',
    href: register().url,
    description:
        'Everything you need to turn Shopify into a wholesale business. One plan, no tiers, no surprise fees.',
    features: [
        'Fiber-specific terminology',
        'Store relationship management',
        'Online wholesale catalog',
        'Smart inventory reservation',
        'Bi-directional Shopify sync',
        '30-day money-back guarantee',
    ],
    priceMonthly: '$39',
};

const faqs = [
    {
        id: 1,
        question: 'Do I need to leave Shopify?',
        answer: 'No. Fibermade is a Shopify app that adds fiber-specific capabilities to your existing store. Your retail customers see no change, and you keep everything that already works.',
    },
    {
        id: 2,
        question: 'How does inventory sync work?',
        answer: 'Fibermade syncs inventory bi-directionally with Shopify. When a wholesale order is accepted, inventory reserves automatically. Retail sales in Shopify sync back to Fibermade so you always know what\u2019s available.',
    },
    {
        id: 3,
        question: 'How do stores place wholesale orders?',
        answer: 'You invite stores to Fibermade where they can browse your catalog, see wholesale pricing, and place orders directly — no more email tag or spreadsheets.',
    },
    {
        id: 4,
        question: 'Does Fibermade handle payments?',
        answer: 'Wholesale payments are handled outside Fibermade — check, Venmo, wire transfer, however you and your stores already work. You mark orders paid in the system when payment arrives.',
    },
    {
        id: 5,
        question: 'How does the 30-day guarantee work?',
        answer: 'If Fibermade isn\u2019t the right fit, cancel within 30 days for a full automatic refund \u2014 no questions asked. We want you to have enough time to actually use the wholesale features before deciding.',
    },
    {
        id: 6,
        question: 'What happens to my Shopify store if I cancel?',
        answer: 'Your Shopify store keeps running as normal. Any changes Fibermade synced to Shopify \u2014 like inventory updates \u2014 stay in place, but no further syncs will happen. Your retail storefront, customers, and orders are unaffected.',
    },
];
</script>

<template>
    <Head title="Fibermade – Shopify for the fiber community" />
    <main class="flex min-h-screen flex-col bg-surface-50">
        <WebHeader
            background="surface"
            company-name="Fibermade"
            logo-url="/logo.png"
            :navigation="headerNavigation"
            :login-link="isAuthenticated ? undefined : login().url"
            :signup-link="isAuthenticated ? undefined : register().url"
        />

        <WebHero
            variant="screenshotRight"
            background="white"
            title="Shopify wasn't built for yarn. Fibermade fixes that."
            description="Fibermade adds wholesale ordering to your Shopify store and turns a generic ecommerce tool into something fiber-specific — colorways, bases, and the language your business actually uses."
            :badge="{ label: 'We\'ve just launched Fibermade!', text: '' }"
            :primary-button="
                isAuthenticated
                    ? {
                          text: 'Go to Dashboard',
                          href: dashboard().url,
                      }
                    : {
                          text: 'Fiberize Your Shopify Store',
                          href: register().url,
                      }
            "
            :secondary-button="
                isAuthenticated
                    ? undefined
                    : { text: 'Learn more', href: '#features' }
            "
            :screenshot-url="dashboardScreenshotUrl"
        />

        <section id="features">
            <WebFeatures
                variant="threeColumn"
                background="surface"
                title="Why Shopify falls short for fiber businesses"
                :features="problemFeatures"
            />

            <WebFeatures
                variant="featureList"
                title="What Fibermade adds to your Shopify store"
                :features="solutionFeatures"
            />
        </section>

        <section id="pricing">
            <WebPricing
                variant="single"
                background="surface"
                subtitle="Pricing"
                title="Simple pricing, no surprises"
                description="One plan that includes everything. No feature gates, no per-store fees, no hidden costs."
                :tiers="[pricingTier]"
                :single-price="{
                    price: '$39',
                    currency: '/month',
                    buttonText: 'Fiberize Your Shopify Store',
                }"
            />
        </section>

        <section id="faqs">
            <WebFrequentlyAskedQuestions
                title="Frequently asked questions"
                description="Have a different question? Reach out by "
                support-email-link="mailto:hello@fibermade.app"
                :faqs="faqs"
            />
        </section>

        <WebCallToAction
            variant="centered"
            background="primary"
            title="Ready to make Shopify work for your yarn business?"
            description="Just launched — be one of the first to Fiberize your Shopify store. Manage colorways, track dyeing batches, and wholesale with ease."
            :primary-button="
                isAuthenticated
                    ? {
                          text: 'Go to Dashboard',
                          href: dashboard().url,
                      }
                    : {
                          text: 'Fiberize Your Shopify Store',
                          href: register().url,
                      }
            "
        />

        <WebFooter
            variant="centered"
            company-name="Fibermade"
            description="Shopify for the fiber community. Wholesale and fiber-specific terminology built in."
            :main-links="[
                { name: 'Terms', href: '/terms' },
                { name: 'Privacy', href: '/privacy' },
            ]"
            :social-links="[]"
            copyright-text="All rights reserved."
        />
    </main>
</template>
