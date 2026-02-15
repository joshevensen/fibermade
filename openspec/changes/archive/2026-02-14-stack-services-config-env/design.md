## Context

The platform Laravel app already has partial setup for some Stack services: `config/services.php` includes Postmark and `config/mail.php` has a postmark mailer; `.env.example` uses Mailtrap for local SMTP. Cashier is installed and uses Stripe. There is no Sentry or MailerLite config or env scaffolding. This change adds consistent config and env entries for every Stack.md service the app uses (Postmark, MailerLite, Sentry, Stripe) so keys can be added later without code changes.

## Goals / Non-Goals

**Goals:**

- One config surface per service: either a dedicated config file or a clear section in `config/services.php`, with all values read from env.
- `.env` and `.env.example` list every required key for those services with empty or placeholder values so the app runs without real keys.
- Align with Laravel and package conventions (Cashier, Sentry Laravel, Postmark driver, etc.) so future key injection is straightforward.

**Non-Goals:**

- Installing new Composer/npm packages (use existing or Laravel defaults).
- Implementing features that use these services (e.g. sending MailerLite campaigns or reporting to Sentry); only scaffolding.
- Changing runtime behavior when keys are missing (e.g. no conditional feature flags).

## Decisions

### 1. Where each service is configured

| Service    | Config location              | Rationale |
|-----------|------------------------------|-----------|
| Postmark  | `config/services.php` (existing) + `config/mail.php` (existing) | Already present; only ensure env keys are documented. |
| MailerLite | `config/services.php` new key | Single API key; fits existing services pattern. |
| Sentry    | `config/services.php` or dedicated `config/sentry.php` if package publishes one | Sentry Laravel publishes config when installed; if not installed, add a minimal `sentry` key in `config/services.php` with `dsn` from env so app code can use `config('services.sentry.dsn')` when we add the SDK later. For scaffolding-only we add to `config/services.php` and document `SENTRY_LARAVEL_DSN`. |
| Stripe    | `config/services.php` new key (optional) | Cashier reads `STRIPE_KEY` and `STRIPE_SECRET` from env directly; adding a `stripe` key in config allows `config('services.stripe.key')` and keeps credentials in one place. Alternatively leave Cashier as env-only; we choose adding a `stripe` section for consistency with other services. |

**Alternative considered:** No Stripe entry in config, only env. Rejected so all Stack services are discoverable in one file.

### 2. Env key naming

- **Postmark:** `POSTMARK_API_KEY` (already in services.php). Add to `.env.example` if missing.
- **MailerLite:** `MAILERLITE_API_KEY` (common convention).
- **Sentry:** `SENTRY_LARAVEL_DSN` (Sentry Laravel convention) or `SENTRY_DSN`; use `SENTRY_LARAVEL_DSN` if/when Sentry Laravel is used, otherwise `SENTRY_DSN` for a generic placeholder.
- **Stripe:** `STRIPE_KEY` (publishable), `STRIPE_SECRET` (secret). Cashier uses these; add to `.env.example`.
- **Mailtrap:** Already in `.env.example` (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`); no extra config file needed (Laravel mail uses these for SMTP). No change.

### 3. .env.example structure

Group new keys under a short comment per service (e.g. `# Postmark`, `# MailerLite`, `# Sentry`, `# Stripe`) so developers know where to fill in keys. Use empty values or placeholders like `your-postmark-api-key`; no real secrets.

### 4. Sentry package and config

Scaffolding only: add `SENTRY_LARAVEL_DSN=` (or `SENTRY_DSN=`) to `.env.example` and a `sentry` entry in `config/services.php` with `'dsn' => env('SENTRY_LARAVEL_DSN')`. Do not install `sentry/sentry-laravel` in this change; when installed later, it will publish its own config and we can point it at the same env var or remove the services.php entry if redundant.

## Risks / Trade-offs

- **Sentry key in services.php without the SDK:** Code that reads `config('services.sentry.dsn')` will work once the SDK is installed and the env var is set; until then it is a no-op. Low risk.
- **Stripe keys in both config and env:** Cashier reads env; we add config for consistency. Slight duplication; mitigated by documenting that Cashier uses env and config is for app-level access if needed.
- **Keys left empty in production:** Out of scope; deployment and key injection are separate. Design only ensures keys are documented and have a single place in config.

## Migration Plan

- No database or runtime migration. Deploy updated config and `.env.example`; existing `.env` can be updated manually with new keys when ready.
- Rollback: revert config and `.env.example`; no data to restore.

## Open Questions

- None for scaffolding. When Sentry Laravel is added, confirm whether to keep `config('services.sentry')` or rely solely on the published Sentry config.
