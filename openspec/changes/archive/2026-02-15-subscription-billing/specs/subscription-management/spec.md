## ADDED Requirements

### Requirement: Enforce subscription for Creator accounts only
The system SHALL require an active subscription for Creator accounts to access Creator-specific routes. Store and Buyer accounts SHALL NOT be subject to subscription checks.

#### Scenario: Creator with active subscription accesses dashboard
- **WHEN** an authenticated user's account has type Creator and subscription_status is active, past_due, or cancelled
- **THEN** the system SHALL allow access to Creator routes

#### Scenario: Creator with refunded subscription is redirected
- **WHEN** an authenticated user's account has type Creator and subscription_status is refunded
- **THEN** the system SHALL redirect the user to the subscription-expired or reactivation page
- **AND** SHALL NOT allow access to Creator dashboard and features

#### Scenario: Creator with inactive subscription has read-only access during retention
- **WHEN** an authenticated user's account has type Creator and subscription_status is inactive (within the 90-day retention window)
- **THEN** the system SHALL allow read-only access to Creator routes (view data only)
- **AND** SHALL NOT allow create, update, or delete operations
- **AND** SHALL show a reactivation banner with countdown; only after the retention window SHALL the account be deleted (see 90-day retention requirement)

#### Scenario: Store account bypasses subscription check
- **WHEN** an authenticated user's account has type Store
- **THEN** the system SHALL allow access to Store routes without checking subscription_status
- **AND** subscription middleware SHALL NOT apply

#### Scenario: Buyer account bypasses subscription check
- **WHEN** an authenticated user's account has type Buyer
- **THEN** the system SHALL allow access without checking subscription_status
- **AND** subscription middleware SHALL NOT apply

### Requirement: Track subscription status on Account
The system SHALL store subscription_status on the Account model with exactly five values: active, past_due, cancelled, inactive, refunded.

#### Scenario: New Creator account has active status after checkout
- **WHEN** a Creator account is created via checkout.session.completed webhook
- **THEN** the account's subscription_status SHALL be set to active

#### Scenario: Subscription status is persisted and queryable
- **WHEN** an account has subscription_status set to any of the five values
- **THEN** the value SHALL be persisted in the database
- **AND** SHALL be available via the Account model and Inertia shared props for Creator layouts

### Requirement: Show payment-failed banner for past_due Creators
The system SHALL display a site-wide, non-dismissible banner to authenticated Creator users when their account's subscription_status is past_due, with a call-to-action to update payment method via Stripe Customer Portal.

#### Scenario: Creator with past_due sees banner
- **WHEN** an authenticated Creator user loads any Creator page and account.subscription_status is past_due
- **THEN** the system SHALL render a prominent banner at the top of the page
- **AND** the banner SHALL include a button or link that opens the Stripe Customer Portal

#### Scenario: Creator with active subscription does not see banner
- **WHEN** an authenticated Creator user's account has subscription_status active or cancelled
- **THEN** the system SHALL NOT display the payment-failed banner

#### Scenario: Store users never see payment-failed banner
- **WHEN** an authenticated user's account has type Store or Buyer
- **THEN** the system SHALL NOT display the payment-failed banner regardless of any subscription state

### Requirement: Retain inactive account data for 90 days with email reminders
The system SHALL retain account and related data for 90 days after subscription becomes inactive, and SHALL send reminder emails at day 7, 30, 60, and 80. On day 90 the system SHALL hard-delete the account and related data.

#### Scenario: Inactive Creator receives day-7 reminder
- **WHEN** an account has been inactive for 7 days (since subscription ended)
- **THEN** the system SHALL send an email reminding the user to reactivate within 90 days to keep data

#### Scenario: Inactive Creator receives day-30, 60, 80 reminders
- **WHEN** an account has been inactive for 30, 60, or 80 days respectively
- **THEN** the system SHALL send the corresponding reminder email (60 days left, 30 days left, 10 days left)

#### Scenario: Account and data deleted on day 90
- **WHEN** an account has been inactive for 90 days
- **THEN** the system SHALL hard-delete the account and all related data (colorways, bases, orders, integrations, users)
- **AND** SHALL NOT send further reminder emails

#### Scenario: Inactive Creator has read-only access during retention
- **WHEN** an authenticated Creator user's account has subscription_status inactive and is within the 90-day window
- **THEN** the system SHALL allow read-only access to view existing data
- **AND** SHALL NOT allow creating new items or syncing with Shopify
- **AND** SHALL show a prominent reactivation banner with countdown

### Requirement: Provide helper to determine if account requires subscription
The system SHALL provide a method or attribute on Account (or shared logic) that returns whether the account type requires a subscription (Creator requires it; Store and Buyer do not).

#### Scenario: Creator account requires subscription
- **WHEN** account.type is Creator
- **THEN** the helper SHALL return true (subscription required)

#### Scenario: Store account does not require subscription
- **WHEN** account.type is Store or Buyer
- **THEN** the helper SHALL return false (subscription not required)
