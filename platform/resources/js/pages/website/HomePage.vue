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

const placeholderScreenshot =
    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='600' viewBox='0 0 800 600'%3E%3Crect fill='%23e5e7eb' width='800' height='600'/%3E%3Ctext fill='%239ca3af' font-family='sans-serif' font-size='24' x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle'%3EProduct screenshot%3C/text%3E%3C/svg%3E";

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
            'Shopify thinks in products and variants. You think in colorways, bases, and weights \u2014 and the translation is always lossy.',
        icon: IconBox,
    },
    {
        name: 'No inventory reservation',
        description:
            'Shopify can\u2019t distinguish what\u2019s committed to wholesale from what\u2019s free for retail, so you risk overselling both.',
        icon: IconPackageOff,
    },
    {
        name: 'No wholesale catalog',
        description:
            'Stores can\u2019t browse your line and place orders the way retail customers can. They have to ask you what\u2019s available.',
        icon: IconBookOff,
    },
    {
        name: 'Duplicate product management',
        description:
            'You end up creating separate wholesale variants just to show different pricing \u2014 doubling the work to maintain your catalog.',
        icon: IconCopy,
    },
    {
        name: 'No per-store terms',
        description:
            'Every store gets the same deal because there\u2019s nowhere to manage individual discounts, minimums, or payment terms.',
        icon: IconUsers,
    },
];

const solutionFeatures = [
    {
        name: 'Wholesale ordering',
        description:
            'Stores can browse your line sheet and place orders directly — no more email tag or PDFs.',
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
        'Everything you need to run wholesale alongside your existing Shopify store. One plan, no tiers, no surprise fees.',
    features: [
        'Wholesale catalog with fiber-specific terminology',
        'Store relationship management with per-store terms',
        'Inline ordering for your wholesale customers',
        'Smart inventory reservation (wholesale vs. retail)',
        'Bi-directional Shopify sync',
        '30-day money-back guarantee',
    ],
    priceMonthly: '$39',
};

const faqs = [
    {
        id: 1,
        question: 'Do I need to leave Shopify?',
        answer: 'No. Fibermade is a Shopify app that adds wholesale capabilities to your existing store. Your retail customers see no change, and you keep everything that already works.',
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
        question: 'What if I want to cancel?',
        answer: 'Cancel anytime. If you cancel within 30 days, you get a full refund — no questions asked. We want you to have enough time to actually use the wholesale features before deciding.',
    },
    {
        id: 6,
        question: 'Why $39/month?',
        answer: 'It matches what you already pay for Shopify Basic — an easy mental anchor. Your total stack is $78/month for both Shopify and Fibermade, which is far less than the $2,000+/month Shopify Plus charges for B2B features.',
    },
];
</script>

<template>
    <Head title="Fibermade – Shopify for the fiber community" />
    <main class="flex min-h-screen flex-col bg-surface-50">
        <WebHeader
            background="surface"
            company-name="Fibermade"
            :navigation="headerNavigation"
            :login-link="isAuthenticated ? undefined : login().url"
            :signup-link="isAuthenticated ? undefined : register().url"
        />

        <WebHero
            variant="screenshotRight"
            background="white"
            title="Shopify wasn't built for yarn. Fibermade fixes that."
            description="Fibermade adds wholesale ordering to your Shopify store and turns generic variants into fiber-specific concepts — colorways, bases, and the language your business actually uses."
            :badge="{ label: 'We\'ve just launched Fibermade!', text: '' }"
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
            :screenshot-url="placeholderScreenshot"
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
                    buttonText: 'Get started',
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

        <WebFooter
            variant="centered"
            company-name="Fibermade"
            description="Shopify for the fiber community. Wholesale and fiber-specific terminology built in."
            :social-links="[]"
            copyright-text="All rights reserved."
        />
    </main>
</template>
