## ADDED Requirements

### Requirement: Registration form creates Stripe Checkout Session (no account yet)
The system SHALL accept registration form submission and create a Stripe Checkout Session for subscription payment. The system SHALL NOT create an Account or User in the database until payment succeeds.

#### Scenario: Valid registration redirects to Stripe Checkout
- **WHEN** the user submits the registration form with valid name, email, business_name, password, and accepted terms
- **THEN** the system SHALL validate the input
- **AND** SHALL store the registration data in the session
- **AND** SHALL create a Stripe Checkout Session in subscription mode
- **AND** SHALL return the Checkout Session URL to the client
- **AND** the client SHALL redirect the user to Stripe Checkout

#### Scenario: Invalid registration returns validation errors
- **WHEN** the user submits the registration form with invalid or missing required fields
- **THEN** the system SHALL return validation errors
- **AND** SHALL NOT create a Checkout Session
- **AND** SHALL NOT store registration data in session

#### Scenario: Email uniqueness enforced before checkout
- **WHEN** the user submits registration with an email that already exists for a User in the system
- **THEN** the system SHALL return a validation error
- **AND** SHALL NOT create a Checkout Session

### Requirement: Registration data stored in session with metadata for webhook
The system SHALL store in session: name, email, business_name, hashed password, and optional promo code. The same data SHALL be passed to Stripe Checkout Session as metadata so the webhook can create the account.

#### Scenario: Session contains registration payload
- **WHEN** registration form is successfully validated
- **THEN** the system SHALL store name, email, business_name, hashed password, and promo_code (if present) in the session under a single key (e.g. registration)
- **AND** the Stripe Checkout Session SHALL be created with metadata containing these values for use in checkout.session.completed webhook

### Requirement: Account and User created on checkout.session.completed webhook
The system SHALL create the Account (type Creator) and the first User when it receives and processes the Stripe webhook event checkout.session.completed for a completed subscription checkout.

#### Scenario: Webhook creates Creator account and user
- **WHEN** the system receives checkout.session.completed with valid metadata (email, name, business_name, password_hash)
- **THEN** the system SHALL create an Account with type Creator and subscription_status active
- **AND** SHALL create a User linked to that account with the provided name, email, and password (from hash)
- **AND** SHALL associate the Account with the Stripe customer ID from the session
- **AND** SHALL send a welcome email to the user

#### Scenario: Webhook is idempotent
- **WHEN** the system receives checkout.session.completed for a Stripe event that has already been processed (e.g. duplicate delivery)
- **THEN** the system SHALL NOT create a second Account or User
- **AND** SHALL acknowledge the webhook with success to prevent Stripe retries

#### Scenario: Duplicate email in webhook does not create duplicate account
- **WHEN** checkout.session.completed is processed and an Account or User with the same email already exists
- **THEN** the system SHALL NOT create a duplicate
- **AND** SHALL update or link the existing record as appropriate, or log and acknowledge without failing

### Requirement: Success and cancel URLs for Checkout redirect
The system SHALL configure Stripe Checkout Session with success_url and cancel_url that redirect the user back to the application after payment success or cancellation.

#### Scenario: Success redirect after payment
- **WHEN** the user completes payment on Stripe Checkout
- **THEN** Stripe SHALL redirect the user to the configured success_url (e.g. /register/success)
- **AND** the application SHALL show a success message (e.g. account created, check email or log in)

#### Scenario: Cancel redirect when user abandons
- **WHEN** the user cancels or closes Stripe Checkout without paying
- **THEN** Stripe SHALL redirect the user to the configured cancel_url (e.g. /register)
- **AND** no Account or User SHALL exist in the database

### Requirement: Welcome email on account creation
The system SHALL send one welcome email when a Creator account and user are created via the checkout.session.completed webhook.

#### Scenario: Welcome email sent after account creation
- **WHEN** the webhook handler successfully creates the Account and User
- **THEN** the system SHALL send a welcome email to the user's email address
- **AND** the email SHALL be sent from Fibermade (not Stripe) and SHALL include getting-started or login information as defined by the product
