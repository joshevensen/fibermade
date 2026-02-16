<script setup lang="ts">
import WebFooter from '@/components/web/WebFooter.vue';
import WebHeader from '@/components/web/WebHeader.vue';
import { home, login, register } from '@/routes';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const headerNavigation = [{ name: 'Home', href: home().url }];
</script>

<template>
    <Head title="Privacy Policy – Fibermade" />
    <main class="flex min-h-screen flex-col bg-surface-50">
        <WebHeader
            background="surface"
            company-name="Fibermade"
            logo-url="/logo.png"
            :navigation="headerNavigation"
            :login-link="isAuthenticated ? undefined : login().url"
            :signup-link="isAuthenticated ? undefined : register().url"
        />

        <div class="mx-auto w-full max-w-3xl px-6 py-16 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-surface-900">
                Privacy Policy
            </h1>
            <p class="mt-2 text-sm text-surface-500">
                Last updated: February 16, 2026
            </p>

            <div class="mt-10 space-y-10 text-base/7 text-surface-700">
                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        The short version
                    </h2>
                    <p class="mt-3">
                        We collect only what we need to run Fibermade. We don't
                        sell your data to anyone — ever. We don't track you
                        across the web. We use cookies only to keep you logged
                        in.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Who we are
                    </h2>
                    <p class="mt-3">
                        Fibermade is operated by Fibermade LLC, a Texas limited
                        liability company. When we say "we", "us", or "our" in
                        this policy, we mean Fibermade LLC.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        What we collect and why
                    </h2>
                    <p class="mt-3">
                        Here's a plain list of the information we collect, along
                        with why we need it:
                    </p>

                    <h3 class="mt-6 font-semibold text-surface-900">
                        When you sign up
                    </h3>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            <strong>Name and email</strong> — so we can create
                            your account and contact you about your service.
                        </li>
                        <li>
                            <strong>Business name</strong> — to label your
                            account and show your business to wholesale
                            customers.
                        </li>
                        <li>
                            <strong>Password</strong> — stored as a secure,
                            one-way hash. We never see or store your actual
                            password.
                        </li>
                    </ul>

                    <h3 class="mt-6 font-semibold text-surface-900">
                        When you use the app
                    </h3>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            <strong>Business details</strong> (phone, address) —
                            optional fields you can fill in for your account
                            profile.
                        </li>
                        <li>
                            <strong>Product and inventory data</strong>
                            (colorways, bases, collections, quantities) — the
                            core of what Fibermade manages for you.
                        </li>
                        <li>
                            <strong>Order and customer data</strong> (wholesale
                            orders, store contacts, customer names and
                            addresses) — so you can manage your wholesale
                            relationships.
                        </li>
                        <li>
                            <strong>Shopify connection data</strong> (shop
                            domain, API tokens) — to sync products and inventory
                            between Fibermade and your Shopify store.
                        </li>
                    </ul>

                    <h3 class="mt-6 font-semibold text-surface-900">
                        Payment information
                    </h3>
                    <p class="mt-3">
                        We use <strong>Stripe</strong> to process payments. Your
                        credit card number goes directly to Stripe — we never
                        see or store it. We only keep a record of your payment
                        method type (e.g., Visa) and the last four digits, so
                        you can identify your card in settings.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        What we don't do
                    </h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            <strong>We don't sell your data.</strong> Not to
                            advertisers, not to data brokers, not to anyone.
                        </li>
                        <li>
                            <strong>We don't run ads.</strong> Our only revenue
                            is your subscription.
                        </li>
                        <li>
                            <strong>We don't track you across the web.</strong>
                            No third-party tracking pixels, no behavioral
                            analytics, no fingerprinting.
                        </li>
                        <li>
                            <strong>We don't share your data</strong> with
                            anyone, except as described below (service providers
                            and legal requirements).
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Third-party services
                    </h2>
                    <p class="mt-3">
                        We use a small number of third-party services to run
                        Fibermade. These services only access what they need:
                    </p>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            <strong>Stripe</strong> — processes your
                            subscription payments. Stripe has its own
                            <a
                                href="https://stripe.com/privacy"
                                target="_blank"
                                rel="noopener"
                                class="text-primary-600 underline underline-offset-2 hover:text-primary-500"
                                >privacy policy</a
                            >.
                        </li>
                        <li>
                            <strong>Shopify</strong> — we sync product and
                            inventory data with your Shopify store when you
                            connect it. Shopify has its own
                            <a
                                href="https://www.shopify.com/legal/privacy"
                                target="_blank"
                                rel="noopener"
                                class="text-primary-600 underline underline-offset-2 hover:text-primary-500"
                                >privacy policy</a
                            >.
                        </li>
                    </ul>
                    <p class="mt-3">
                        We don't use any advertising networks, analytics
                        platforms, or social media tracking tools.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Cookies
                    </h2>
                    <p class="mt-3">
                        We use a single session cookie to keep you logged in.
                        That's it. No tracking cookies, no advertising cookies,
                        no third-party cookies. You won't see a cookie banner on
                        our site because we don't do anything that requires one.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Data security
                    </h2>
                    <p class="mt-3">
                        We take reasonable steps to protect your data:
                    </p>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>All connections use HTTPS encryption.</li>
                        <li>
                            Passwords are hashed using industry-standard
                            algorithms.
                        </li>
                        <li>
                            Payment data is handled by Stripe, a PCI-compliant
                            payment processor.
                        </li>
                        <li>
                            Shopify API connections are verified using HMAC
                            signatures.
                        </li>
                    </ul>
                    <p class="mt-3">
                        No system is perfectly secure. If we ever discover a
                        breach that affects your data, we'll notify you
                        promptly.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Data retention
                    </h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            <strong>Active accounts</strong> — we keep your data
                            as long as your account is active.
                        </li>
                        <li>
                            <strong>Cancelled accounts</strong> — we retain your
                            data so you can resubscribe and pick up where you
                            left off. If you want your data deleted, just ask.
                        </li>
                        <li>
                            <strong>Deleted accounts</strong> — when you request
                            account deletion, we remove your personal data
                            within 30 days. Some data may persist in encrypted
                            backups for up to 90 days.
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Your rights
                    </h2>
                    <p class="mt-3">You can:</p>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            <strong>Access your data</strong> — everything you
                            put into Fibermade is visible in your account.
                        </li>
                        <li>
                            <strong>Update your data</strong> — edit your
                            profile and business details anytime in settings.
                        </li>
                        <li>
                            <strong>Delete your account</strong> — you can
                            request account deletion from your settings page, or
                            by emailing us.
                        </li>
                        <li>
                            <strong>Opt out of marketing</strong> — if you opted
                            in to product updates, you can opt out anytime in
                            settings or by using the unsubscribe link in any
                            email.
                        </li>
                    </ul>
                    <p class="mt-3">
                        If you're in the EU, California, or another jurisdiction
                        with privacy laws, you may have additional rights (like
                        data portability or restricting processing). Email us
                        and we'll help.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Children's privacy
                    </h2>
                    <p class="mt-3">
                        Fibermade is a business tool. You must be at least 18 to
                        use it. We don't knowingly collect data from anyone
                        under 18.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        When we might share data
                    </h2>
                    <p class="mt-3">
                        The only situations where we'd share your data:
                    </p>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            <strong>With your permission</strong> — for example,
                            when you connect your Shopify store.
                        </li>
                        <li>
                            <strong>To run the service</strong> — our payment
                            processor (Stripe) needs billing details to charge
                            your subscription.
                        </li>
                        <li>
                            <strong>If legally required</strong> — we'll comply
                            with valid legal processes like subpoenas or court
                            orders. We'll notify you if we're allowed to.
                        </li>
                        <li>
                            <strong>Business transfer</strong> — if Fibermade is
                            acquired or merges with another company, your data
                            would transfer with the business. We'd notify you
                            before that happens.
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Changes to this policy
                    </h2>
                    <p class="mt-3">
                        If we make meaningful changes to this privacy policy,
                        we'll let you know by email or through the app. We won't
                        quietly reduce your privacy protections.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Contact us
                    </h2>
                    <p class="mt-3">
                        Questions or concerns about your privacy? Email us at
                        <a
                            href="mailto:support@fibermade.com"
                            class="text-primary-600 underline underline-offset-2 hover:text-primary-500"
                            >support@fibermade.com</a
                        >.
                    </p>
                </section>
            </div>
        </div>

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
