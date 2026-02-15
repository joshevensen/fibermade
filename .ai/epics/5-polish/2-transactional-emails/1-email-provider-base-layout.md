status: done

# Story 5.2: Prompt 1 -- Email Provider Setup & Base Layout

## Context

The platform currently uses the `log` mail driver -- emails are written to the Laravel log file instead of being sent. The `.env.example` references Mailtrap for development and Postmark for production. One Mailable exists: `StoreInviteMail` which sends store invite emails. It uses a Blade view at `resources/views/emails/store-invite.blade.php` which extends the existing shared layout at `resources/views/emails/layout.blade.php`. The `StoreInviteMail` implements `ShouldQueue` for queued delivery. The queue driver in `.env.example` is `database`. Laravel's mail config at `config/mail.php` already supports Postmark and SMTP (Mailtrap) out of the box; no extra packages required.

## Goal

Use the mail driver configured in `.env` (Mailtrap locally, Postmark in production), and treat the existing shared layout at `resources/views/emails/layout.blade.php` as the default for all transactional emails (enhancing it with Fibermade branding and tagline as needed). This establishes the email infrastructure that Prompts 2 and 3 build on.

## Non-Goals

- Do not build the order-related emails -- that's Prompts 2 and 3
- Do not set up email verification mailables (Laravel handles this via `MustVerifyEmail`)
- Do not configure DNS/domain verification for Postmark (that's a deployment task)
- Do not add email preview/testing tools beyond Mailtrap

## Constraints

- No new mail packages: use Laravel's built-in SMTP (Mailtrap) and Postmark drivers
- Update `.env.example` so that local defaults use Mailtrap (`MAIL_MAILER=smtp` and existing Mailtrap vars) and production is documented to use Postmark (`MAIL_MAILER=postmark`, `POSTMARK_API_KEY`)
- Use and enhance the existing shared Blade layout at `resources/views/emails/layout.blade.php` as the default for all transactional emails:
  - Keep clean, responsive HTML email structure (table-based, inline CSS)
  - Fibermade branding (logo, brand colors) and footer tagline: "Fibermade — A commerce platform for the fiber community" — hardcode in the layout only (no new config keys)
  - Layout already has header, content slot (`@yield('content')`), and footer; preserve optional "from creator" header behavior used by store-invite
- All Mailables should use `ShouldQueue` and `Queueable` for async delivery
- Set `APP_NAME=Fibermade`, `MAIL_FROM_ADDRESS=hello@fibermade.com`, and `MAIL_FROM_NAME=Fibermade` in `.env.example`

## Acceptance Criteria

- [ ] `.env.example` documents Mailtrap for local (MAIL_MAILER=smtp) and Postmark for production (MAIL_MAILER=postmark, POSTMARK_API_KEY); APP_NAME=Fibermade; MAIL_FROM_ADDRESS and MAIL_FROM_NAME set to hello@fibermade.com and Fibermade
- [ ] Default transactional layout at `resources/views/emails/layout.blade.php` has Fibermade branding and tagline
- [ ] Layout keeps responsive HTML structure with header, content yield, and footer (and optional "from creator" header)
- [ ] Footer uses tagline: "Fibermade — A commerce platform for the fiber community"
- [ ] `store-invite.blade.php` continues to extend the default layout (`emails.layout`)
- [ ] `StoreInviteMail` still works correctly
- [ ] Existing invite email tests pass (InviteControllerTest)

---

## Tech Analysis

- **Mail driver**: Laravel's `config/mail.php` already defines `smtp` (for Mailtrap) and `postmark`. No extra packages. Set `MAIL_MAILER` in `.env` per environment (smtp locally, postmark in prod); Postmark uses `POSTMARK_API_KEY` (or the key name Laravel's Postmark transport expects).
- **Email layout**: The existing `layout.blade.php` is table-based with inline CSS, ~560px max-width, and header/content/footer rows. Enhance it with Fibermade branding and the new tagline, hardcoded in the view (do not add config for name or tagline). Keep the optional "from creator" header variant (when `$creatorForward` and `$creatorName` are set) so store-invite behavior is unchanged.
- **Store invite**: Already extends `emails.layout` and passes `creatorForward` and `creatorName`. No view path change; ensure layout updates do not break the existing store-invite content or variables.
- **Queue driver**: The database queue driver is already referenced in `.env.example`. Ensure `php artisan queue:table` migration exists (it should from Laravel's scaffolding).

## References

- `platform/config/mail.php` -- mail driver configuration
- `platform/.env.example` -- environment variable defaults
- `platform/app/Mail/StoreInviteMail.php` -- existing Mailable (already uses default layout)
- `platform/resources/views/emails/layout.blade.php` -- default transactional layout (enhance, do not replace)
- `platform/resources/views/emails/store-invite.blade.php` -- extends default layout
- `platform/composer.json` -- dependency management

## Files

- Modify `platform/.env.example` -- document Mailtrap (local) and Postmark (production) usage; set APP_NAME=Fibermade, MAIL_FROM_ADDRESS=hello@fibermade.com, MAIL_FROM_NAME=Fibermade
- Modify `platform/resources/views/emails/layout.blade.php` -- add Fibermade branding and tagline; keep existing structure and "from creator" behavior
