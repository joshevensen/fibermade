status: done

# Story 5.3: Prompt 1 -- Registration Page Polish

## Context

The registration page exists at `platform/resources/js/pages/auth/RegisterPage.vue` with fields for name, email, business_name, password (confirmed), and checkboxes for terms, privacy, and marketing opt-in. The `CreateNewUser` action at `platform/app/Actions/Fortify/CreateNewUser.php` handles registration: it validates input, checks an email whitelist (currently limited to `kristen@badfrogyarnco.com` in `config/auth.php`), creates an Account (type: Creator), Creator record, and User. The `FortifyServiceProvider` renders the register view via Inertia. Registration always creates a Creator account -- there's no account type selection.

Registration is open to anyone — the email whitelist needs to be removed entirely. The page already uses AuthLayout, UiForm, and the app's UI components; server validation errors display inline and the submit button has a loading state.

## Goal

Make registration open to anyone by removing the email whitelist, update tests to match, and refactor the registration page so form initial values are a single source of truth. The page already meets production-ready UX (inline errors, loading state, login link, design system).

## Non-Goals

- Do not add account type selection (all registrations are Creator accounts for beta)
- Do not add OAuth/social login
- Do not modify the post-registration redirect logic
- Do not add onboarding flows after registration (that's a future epic)
- Do not modify the login page -- that's Prompt 2
- Do not add client-side password confirmation validation (server-side only)
- Do not add an explicit visual polish pass beyond current layout

## Constraints

- The registration page should use the existing Vue component library and Tailwind CSS classes used elsewhere in the app
- Remove the email whitelist from `config/auth.php` and the whitelist check from `CreateNewUser` — registration is open to anyone
- Keep the existing fields: name, email, business_name, password, password_confirmation, terms, privacy, marketing
- Remove tests that assert whitelist behavior; remove all `Config::set('auth.registration_email_whitelist', ...)` from remaining tests

## Scope Decisions (recorded)

- **Whitelist:** Remove entirely (config + CreateNewUser).
- **Tests:** Remove whitelist-related tests entirely (2 Feature + 1 Browser); strip whitelist config overrides from remaining tests.
- **Client-side password match:** Out of scope; server-side validation only.
- **Visual polish:** No explicit pass; current layout is sufficient.
- **Epic doc:** Update to reflect current implementation and narrowed scope.
- **Initial values:** Refactor RegisterPage so one `initialValues` object is the single source of truth for both `useFormSubmission` and `UiForm`.

## Acceptance Criteria

- [ ] Email whitelist removed — anyone can register
- [ ] `CreateNewUser` action updated to remove whitelist check
- [ ] Whitelist config removed from `config/auth.php`
- [ ] Whitelist-related tests removed (Feature: 2 tests; Browser: 1 test); remaining registration tests no longer set whitelist config
- [ ] RegisterPage.vue uses a single source of truth for form initial values (no duplicate `initialValues` for `useFormSubmission` and `UiForm`)
- [ ] Registration page remains consistent with the app's design system (already implemented: AuthLayout, inline server errors, loading state, login link, terms/privacy labels)

## Tech Analysis

- **Already implemented:** RegisterPage.vue uses `useFormSubmission`, passes `form.errors.{field}` as `serverError` to each UiFormField (inline errors), and passes `form.processing` as `loading` to UiButton. AuthLayout provides heading, description, and footer with login link. No template work needed for errors or loading.
- **Remove whitelist:** Delete the `registration_email_whitelist` config block from `config/auth.php`. In `CreateNewUser.php`, remove the `$whitelist` variable and the closure in the `email` rule that checks the whitelist; keep `required`, `string`, `email`, `max:255`, `Rule::unique(User::class)`.
- **Tests:** Delete the two Feature tests that assert whitelist allow/block behavior; delete the one Browser test that asserts whitelist. In all remaining registration tests, remove any `Config::set('auth.registration_email_whitelist', ...)`.
- **Initial values refactor:** Define one `initialValues` object (e.g. a const or ref) in RegisterPage.vue and pass it to both `useFormSubmission({ initialValues: ... })` and `<UiForm :initialValues="...">` so the form has a single source of truth.

## References

- `platform/resources/js/pages/auth/RegisterPage.vue` -- current registration page
- `platform/app/Actions/Fortify/CreateNewUser.php` -- registration logic with whitelist check
- `platform/config/auth.php` -- current whitelist config (lines 119-134)
- `platform/app/Providers/FortifyServiceProvider.php` -- register view rendering
- `platform/tests/Feature/Auth/RegistrationTest.php` -- Feature tests (remove 2 whitelist tests, strip config from rest)
- `platform/tests/Browser/CreatorRegistrationTest.php` -- Browser tests (remove 1 whitelist test, strip config from rest)

## Files

- Modify `platform/resources/js/pages/auth/RegisterPage.vue` -- single source of truth for initial values
- Modify `platform/app/Actions/Fortify/CreateNewUser.php` -- remove whitelist check
- Modify `platform/config/auth.php` -- remove whitelist config
- Modify `platform/tests/Feature/Auth/RegistrationTest.php` -- remove whitelist tests, remove Config::set whitelist from remaining tests
- Modify `platform/tests/Browser/CreatorRegistrationTest.php` -- remove whitelist test, remove Config::set whitelist from remaining tests
