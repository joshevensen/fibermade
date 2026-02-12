status: pending

# Story 5.2: Prompt 1 -- Email Provider Setup & Base Layout

## Context

The platform currently uses the `log` mail driver -- emails are written to the Laravel log file instead of being sent. The `.env.example` references Mailtrap for development. One Mailable exists: `StoreInviteMail` which sends store invite emails. It uses a Blade view at `resources/views/emails/store-invite.blade.php` with inline HTML styling. The `StoreInviteMail` implements `ShouldQueue` for queued delivery. The queue driver in `.env.example` is `database`. Laravel's mail config at `config/mail.php` already supports Postmark, Resend, SES, and SMTP out of the box.

## Goal

Configure Resend as the production email provider, set up Mailtrap for development/testing, create a shared Blade email layout that all transactional emails will use, and migrate the existing `StoreInviteMail` to use the new layout. This establishes the email infrastructure that Prompts 2 and 3 build on.

## Non-Goals

- Do not build the order-related emails -- that's Prompts 2 and 3
- Do not set up email verification mailables (Laravel handles this via `MustVerifyEmail`)
- Do not configure DNS/domain verification for Resend (that's a deployment task)
- Do not add email preview/testing tools beyond Mailtrap

## Constraints

- Install the `resend/resend-laravel` package for Resend integration
- Update `.env.example` with Resend configuration variables (`RESEND_API_KEY`, `MAIL_MAILER=resend` for production)
- Keep `MAIL_MAILER=smtp` with Mailtrap defaults in `.env.example` for development
- Create a shared Blade layout at `resources/views/emails/layouts/transactional.blade.php` with:
  - Clean, responsive HTML email structure
  - Fibermade branding (logo placeholder, brand colors)
  - Header, content slot (`@yield('content')`), footer
  - Footer with "Fibermade -- A commerce platform for the fiber community" tagline
  - Inline CSS (email clients don't reliably support `<style>` blocks)
- Migrate `store-invite.blade.php` to extend the new layout via `@extends('emails.layouts.transactional')`
- All Mailables should use `ShouldQueue` and `Queueable` for async delivery
- Set `MAIL_FROM_ADDRESS` to `hello@fibermade.com` and `MAIL_FROM_NAME` to `Fibermade` in `.env.example`

## Acceptance Criteria

- [ ] `resend/resend-laravel` package installed via Composer
- [ ] `.env.example` updated with Resend config variables and updated from/name defaults
- [ ] Shared email layout exists at `resources/views/emails/layouts/transactional.blade.php`
- [ ] Layout has responsive HTML structure with Fibermade branding
- [ ] Layout has header, content yield, and footer sections
- [ ] Footer includes Fibermade tagline
- [ ] `store-invite.blade.php` updated to extend the shared layout
- [ ] `StoreInviteMail` still works correctly after the migration
- [ ] Existing invite email tests pass (if any)

---

## Tech Analysis

- **Resend package**: `composer require resend/resend-laravel` adds a Resend mail transport. Configuration requires `RESEND_API_KEY` in `.env` and setting `MAIL_MAILER=resend` in production. The package auto-registers its service provider.
- **Email layout**: Transactional emails need inline CSS for compatibility. The layout should use a centered single-column design (600px max-width) which is the standard for email. Use a `<table>` based layout for maximum email client compatibility. Colors: use neutral/clean palette consistent with a professional SaaS.
- **Layout structure**:
  ```
  <!DOCTYPE html>
  <html>
  <body style="...">
    <table> <!-- header: logo/brand -->
      @yield('content')
    </table>
    <table> <!-- footer: tagline, unsubscribe -->
    </table>
  </body>
  </html>
  ```
- **Store invite migration**: The current `store-invite.blade.php` has its own full HTML structure. Replace the outer HTML/body/table wrapper with `@extends('emails.layouts.transactional')` and `@section('content')...@endsection`, keeping the invite-specific content (greeting, metadata, CTA button).
- **Queue driver**: The database queue driver is already referenced in `.env.example`. Ensure `php artisan queue:table` migration exists (it should from Laravel's scaffolding).

## References

- `platform/config/mail.php` -- mail driver configuration
- `platform/.env.example` -- environment variable defaults
- `platform/app/Mail/StoreInviteMail.php` -- existing Mailable to migrate
- `platform/resources/views/emails/store-invite.blade.php` -- existing email template
- `platform/composer.json` -- dependency management

## Files

- Modify `platform/composer.json` -- add `resend/resend-laravel` (via `composer require`)
- Modify `platform/.env.example` -- add Resend config, update from address/name
- Create `platform/resources/views/emails/layouts/transactional.blade.php` -- shared email layout
- Modify `platform/resources/views/emails/store-invite.blade.php` -- extend shared layout
