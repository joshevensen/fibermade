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
    <Head title="Terms of Service – Fibermade" />
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
                Terms of Service
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
                        Fibermade is a paid SaaS tool that adds wholesale
                        capabilities to your Shopify store. You pay a monthly
                        subscription. You own your data. You can cancel anytime.
                        If you cancel within the first 30 days, you get a full
                        refund. We don't sell your information to anyone.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        What Fibermade is
                    </h2>
                    <p class="mt-3">
                        Fibermade ("we", "us", "our") is a software service
                        operated by Fibermade LLC, a Texas limited liability
                        company. We provide a web application that helps fiber
                        businesses manage wholesale ordering, inventory, and
                        store relationships alongside their Shopify store.
                    </p>
                    <p class="mt-3">
                        By creating an account, you agree to these terms. If you
                        don't agree, don't use the service.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Your account
                    </h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            You must provide accurate information when you sign
                            up.
                        </li>
                        <li>
                            You're responsible for keeping your password secure.
                        </li>
                        <li>
                            You're responsible for all activity under your
                            account.
                        </li>
                        <li>
                            You must be at least 18 years old to use Fibermade.
                        </li>
                        <li>
                            One person or business per account. Don't share
                            accounts.
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Pricing and payment
                    </h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            Fibermade costs <strong>$39 per month</strong>,
                            billed monthly through Stripe.
                        </li>
                        <li>
                            There are no tiers, no per-store fees, and no hidden
                            costs.
                        </li>
                        <li>
                            Prices may change, but we'll give you at least 30
                            days' notice before any price increase takes effect.
                        </li>
                        <li>
                            All fees are in US dollars and are non-refundable,
                            except as described below.
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        30-day money-back guarantee
                    </h2>
                    <p class="mt-3">
                        If you cancel within 30 days of your first payment,
                        we'll refund you in full — no questions asked. We want
                        you to have enough time to actually use the wholesale
                        features before deciding.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Cancellation
                    </h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            You can cancel your subscription anytime from your
                            account settings.
                        </li>
                        <li>
                            When you cancel, your account stays active until the
                            end of your current billing period.
                        </li>
                        <li>
                            After your subscription ends, your account becomes
                            read-only. You can still log in and export your
                            data, but you can't create new orders or make
                            changes.
                        </li>
                        <li>
                            We don't delete your data immediately after
                            cancellation. If you resubscribe, your data will
                            still be there.
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Your data belongs to you
                    </h2>
                    <p class="mt-3">
                        Everything you put into Fibermade — your products,
                        orders, customer information, store relationships — is
                        yours. We don't claim any ownership over your content.
                    </p>
                    <p class="mt-3">
                        You grant us a limited license to store and display your
                        content only as needed to provide the service. That
                        license ends when you delete your data or close your
                        account.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        What you can't do
                    </h2>
                    <p class="mt-3">Don't use Fibermade to:</p>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>Break any laws or regulations.</li>
                        <li>Interfere with or disrupt the service.</li>
                        <li>
                            Attempt to access other users' accounts or data.
                        </li>
                        <li>Reverse-engineer, copy, or resell the software.</li>
                        <li>
                            Use automated tools to scrape or overload the
                            service.
                        </li>
                    </ul>
                    <p class="mt-3">
                        We reserve the right to suspend or terminate accounts
                        that violate these rules.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Shopify integration
                    </h2>
                    <p class="mt-3">
                        Fibermade connects to your Shopify store to sync
                        products and inventory. This integration requires you to
                        authorize access through Shopify's standard app
                        permissions. We only access the data needed to provide
                        the service. Your use of Shopify is also subject to
                        Shopify's own terms of service.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Availability and support
                    </h2>
                    <p class="mt-3">
                        We work hard to keep Fibermade running smoothly, but we
                        can't promise 100% uptime. Sometimes things break,
                        updates happen, or maintenance is needed. We'll do our
                        best to give you advance notice of planned downtime.
                    </p>
                    <p class="mt-3">
                        If you need help, reach out at
                        <a
                            href="mailto:support@fibermade.com"
                            class="text-primary-600 underline underline-offset-2 hover:text-primary-500"
                            >support@fibermade.com</a
                        >.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Limitation of liability
                    </h2>
                    <p class="mt-3">
                        Fibermade is provided "as is." We do our best, but we
                        can't guarantee the service will be error-free or
                        uninterrupted. To the extent allowed by law:
                    </p>
                    <ul class="mt-3 list-disc space-y-2 pl-5">
                        <li>
                            We're not liable for any indirect, incidental, or
                            consequential damages (like lost profits, lost data,
                            or business interruption).
                        </li>
                        <li>
                            Our total liability for any claim related to the
                            service is limited to the amount you've paid us in
                            the 12 months before the claim.
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Changes to these terms
                    </h2>
                    <p class="mt-3">
                        We may update these terms from time to time. If we make
                        significant changes, we'll notify you by email or
                        through the app before they take effect. Continued use
                        of Fibermade after changes means you accept the updated
                        terms.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Governing law
                    </h2>
                    <p class="mt-3">
                        These terms are governed by the laws of the State of
                        Texas, without regard to conflict of law principles. Any
                        disputes will be resolved in the state or federal courts
                        located in Texas.
                    </p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-surface-900">
                        Contact us
                    </h2>
                    <p class="mt-3">
                        Questions about these terms? Email us at
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
