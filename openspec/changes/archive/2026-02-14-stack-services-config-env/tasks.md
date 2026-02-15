## 1. Platform — Config

- [x] 1.1 Add MailerLite entry to `config/services.php` with `api_key` from `MAILERLITE_API_KEY`
- [x] 1.2 Add Sentry entry to `config/services.php` with `dsn` from `SENTRY_LARAVEL_DSN`
- [x] 1.3 Add Stripe entry to `config/services.php` with `key` and `secret` from `STRIPE_KEY` and `STRIPE_SECRET`
- [x] 1.4 Ensure Postmark entry in `config/services.php` uses `POSTMARK_API_KEY` (already present; verify only)

## 2. Platform — Env

- [x] 2.1 Add `POSTMARK_API_KEY` to `.env.example` with empty or placeholder value and `# Postmark` comment
- [x] 2.2 Add `MAILERLITE_API_KEY` to `.env.example` with empty or placeholder value and `# MailerLite` comment
- [x] 2.3 Add `SENTRY_LARAVEL_DSN` to `.env.example` with empty or placeholder value and `# Sentry` comment
- [x] 2.4 Add `STRIPE_KEY` and `STRIPE_SECRET` to `.env.example` with empty or placeholder values and `# Stripe` comment
- [x] 2.5 Verify all Stack service keys in `.env.example` are grouped with comments and use empty or placeholder values
