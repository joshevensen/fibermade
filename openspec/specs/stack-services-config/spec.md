## ADDED Requirements

### Requirement: Postmark has config and env keys

The platform SHALL expose Postmark configuration via `config/services.php` and SHALL document the required env key in `.env.example`. The mail driver for Postmark SHALL remain configurable via `config/mail.php` using the same env key.

#### Scenario: Postmark config is present
- **WHEN** the application reads `config('services.postmark.key')`
- **THEN** it SHALL return the value of the `POSTMARK_API_KEY` env variable (or null if unset)

#### Scenario: Postmark env key is documented
- **WHEN** a developer copies `.env.example` to `.env`
- **THEN** `.env.example` SHALL contain an entry for `POSTMARK_API_KEY` (empty or placeholder)

### Requirement: MailerLite has config and env keys

The platform SHALL expose MailerLite configuration via `config/services.php` and SHALL document the required env key in `.env.example`.

#### Scenario: MailerLite config is present
- **WHEN** the application reads `config('services.mailerlite.api_key')` or equivalent
- **THEN** it SHALL return the value of the `MAILERLITE_API_KEY` env variable (or null if unset)

#### Scenario: MailerLite env key is documented
- **WHEN** a developer copies `.env.example` to `.env`
- **THEN** `.env.example` SHALL contain an entry for `MAILERLITE_API_KEY` (empty or placeholder)

### Requirement: Sentry has config and env keys

The platform SHALL expose Sentry configuration via `config/services.php` and SHALL document the required env key in `.env.example`. The DSN SHALL be read from env so that when the Sentry SDK is installed later it can use the same variable.

#### Scenario: Sentry config is present
- **WHEN** the application reads `config('services.sentry.dsn')` or equivalent
- **THEN** it SHALL return the value of the `SENTRY_LARAVEL_DSN` (or `SENTRY_DSN`) env variable (or null if unset)

#### Scenario: Sentry env key is documented
- **WHEN** a developer copies `.env.example` to `.env`
- **THEN** `.env.example` SHALL contain an entry for the Sentry DSN (empty or placeholder)

### Requirement: Stripe has config and env keys

The platform SHALL expose Stripe configuration via `config/services.php` and SHALL document the required env keys in `.env.example`. Cashier SHALL continue to read Stripe keys from env; the config SHALL provide a single place for app code to access the same values.

#### Scenario: Stripe config is present
- **WHEN** the application reads `config('services.stripe.key')` and `config('services.stripe.secret')`
- **THEN** it SHALL return the values of the `STRIPE_KEY` and `STRIPE_SECRET` env variables (or null if unset)

#### Scenario: Stripe env keys are documented
- **WHEN** a developer copies `.env.example` to `.env`
- **THEN** `.env.example` SHALL contain entries for `STRIPE_KEY` and `STRIPE_SECRET` (empty or placeholder)

### Requirement: Env keys are grouped and safe by default

The platform SHALL group Stack service keys in `.env.example` with short comments per service. Values SHALL be empty or non-secret placeholders so the application can run without real keys until they are added.

#### Scenario: No secrets in example env
- **WHEN** `.env.example` is inspected
- **THEN** it SHALL NOT contain real API keys or secrets; only empty values or placeholders (e.g. `your-postmark-api-key`)

#### Scenario: Service keys are discoverable
- **WHEN** a developer opens `.env.example`
- **THEN** keys for Postmark, MailerLite, Sentry, and Stripe SHALL be present and SHALL be grouped under comments (e.g. `# Postmark`, `# Stripe`) or in a clear order
