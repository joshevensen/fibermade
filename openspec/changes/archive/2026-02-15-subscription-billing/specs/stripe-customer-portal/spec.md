## ADDED Requirements

### Requirement: Creators can open Stripe Customer Portal from the app
The system SHALL provide an authenticated route or action that generates a Stripe Customer Portal session URL and redirects the Creator to it, so they can manage payment method, view invoices, and cancel subscription.

#### Scenario: Creator clicks Manage Billing and is redirected to portal
- **WHEN** an authenticated Creator user requests to open the billing portal (e.g. clicks "Manage Billing" on the settings or billing page)
- **THEN** the system SHALL create a Stripe Customer Portal session (or equivalent redirect URL)
- **AND** SHALL redirect the user to the Stripe-hosted Customer Portal
- **AND** the user SHALL be able to update payment method, view invoices, and cancel subscription within Stripe's UI

#### Scenario: Only Creators can access portal redirect
- **WHEN** an authenticated user whose account type is Store or Buyer requests the billing portal URL
- **THEN** the system SHALL deny access (e.g. 403 or redirect to settings)
- **AND** SHALL NOT generate a Stripe Customer Portal session for non-Creator accounts

### Requirement: Billing management UI for Creators
The system SHALL show a billing section on the Creator settings (or dedicated billing) page that displays subscription status and a control to open the Stripe Customer Portal. Store and Buyer accounts SHALL NOT see this section.

#### Scenario: Creator sees billing card on settings
- **WHEN** an authenticated Creator user visits the Creator settings page (e.g. Account tab)
- **THEN** the system SHALL display a billing card or section above the account form
- **AND** the section SHALL show subscription status (e.g. Active, Past due, Cancelled)
- **AND** SHALL include a button or link to open the Stripe Customer Portal (e.g. "Manage Billing")

#### Scenario: Creator sees price and next billing date when active
- **WHEN** the Creator's subscription_status is active (or cancelled but within period)
- **THEN** the billing section SHALL display the subscription price (e.g. $39/month) and next billing date when available from Stripe/Cashier
- **AND** SHALL allow the user to open the Customer Portal to manage or cancel

#### Scenario: Store and Buyer do not see billing section
- **WHEN** an authenticated user's account type is Store or Buyer
- **THEN** the settings page SHALL NOT display the billing card or any subscription management UI
- **AND** SHALL NOT expose a route to create a Customer Portal session for that user

### Requirement: Customer Portal configuration is no-code
The system SHALL use Stripe's no-code Customer Portal. Portal branding, allowed actions (update payment, cancel subscription, view invoices), and business name SHALL be configured in the Stripe Dashboard, not hardcoded in application logic beyond generating the session URL.

#### Scenario: Portal behavior controlled by Stripe Dashboard
- **WHEN** the application creates a Customer Portal session
- **THEN** the look, allowed actions, and cancellation flow SHALL be determined by the Stripe Customer Portal configuration in the Stripe Dashboard
- **AND** the application SHALL only be responsible for generating the session and redirecting the user
