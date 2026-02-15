**Sub-project**: platform

## Why

The app’s intended production stack is documented in `about/Stack.md`, but the platform Laravel app does not yet have consistent config and env scaffolding for every listed service. Setting this up now gives a single place for config, documented env keys in `.env.example`, and allows API keys to be added later without touching code.

## What Changes

- Add or complete **config** for each Stack service that the Laravel app uses: Postmark, MailerLite, Sentry, Stripe. (Digital Ocean and Laravel Forge are hosting/deployment; no app-level config.)
- Ensure **`.env` and `.env.example`** include all required keys for those services, with empty or placeholder values so the app runs without keys until they are added.
- No new runtime behavior or breaking changes—config and env only.

## Capabilities

### New Capabilities

- `stack-services-config`: Config files and env keys for every Stack.md service used by the platform app (Postmark, MailerLite, Sentry, Stripe). Each service has a config file (or section) and corresponding entries in `.env` / `.env.example`.

### Modified Capabilities

- (none)

## Impact

- **Config**: `config/services.php` (and any new or updated service config files, e.g. Sentry if using a dedicated config).
- **Env**: `platform/.env`, `platform/.env.example` — new or clarified keys only; no removal of existing keys.
- **Dependencies**: No new Composer/npm packages required for this change; Laravel’s built-in Postmark mailer, Cashier/Stripe, and standard Sentry/MailerLite env patterns are sufficient for scaffolding.
