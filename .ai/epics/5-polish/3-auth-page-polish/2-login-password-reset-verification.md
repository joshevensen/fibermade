status: done

# Story 5.3: Prompt 2 -- Login & Password Reset Polish

## Context

The login page exists at `platform/resources/js/pages/auth/LoginPage.vue` with email, password, remember-me checkbox, and a forgot-password link. The forgot password page is at `ForgotPasswordPage.vue` and the reset form at `ResetPasswordPage.vue`. There's also a `ConfirmPasswordPage.vue` for sensitive action confirmation. Fortify handles all the backend logic for password reset (sending the email, validating the token, updating the password). The `FortifyServiceProvider` registers custom Inertia views for login, register, password reset, forgot password, and confirm password. The custom `LoginResponse` routes users to `/store/home` or `/creator/dashboard` based on account type. `AuthLayout.vue` already exists at `platform/resources/js/layouts/AuthLayout.vue` and all auth pages use it.

## Goal

Polish the login page, ensure the full password reset flow works end-to-end (request → email → reset form → confirmation), and ensure all auth pages share a consistent visual design and patterns matching the registration page polished in Prompt 1. Apply single-source-of-truth for form initial values across auth pages.

## Non-Goals

- Do not add OAuth/social login
- Do not add two-factor authentication
- Do not modify the login response routing logic
- Do not add rate limiting beyond what Fortify already provides
- Do not modify the registration page (done in Prompt 1)
- **Email verification is out of scope** — deferred to a future epic

## Constraints

- All auth pages should share a consistent layout: centered card, app logo/name, consistent typography and spacing (AuthLayout already provides this)
- **Login page**: Polish design, ensure error messages display inline (e.g., "These credentials do not match our records"), loading state on submit button
- **Forgot password page**: Clean design, clear instructions ("Enter your email and we'll send you a reset link"), success message after submission
- **Reset password page**: Token-based form with email, new password, password confirmation. Success redirects to login. Use `UiFormFieldInput` for email field (readonly) for consistency with other auth pages.
- All Fortify views are already registered in `FortifyServiceProvider` and render Inertia pages
- Auth pages should use a single source of truth for form initial values (one object shared by `useFormSubmission` and `UiForm`), matching RegisterPage pattern

## Acceptance Criteria

- [ ] Login page has polished design consistent with registration page
- [ ] Login shows inline error messages for invalid credentials
- [ ] Login submit button shows loading state while processing
- [ ] "Forgot password?" link navigates to the forgot password page
- [ ] Forgot password page sends reset email on valid email submission
- [ ] Forgot password page shows success message after sending email
- [ ] Password reset page renders correctly from the email link (with token)
- [ ] Password reset updates the password and redirects to login
- [ ] All auth pages use consistent visual styling (shared AuthLayout)
- [ ] All auth pages use single source of truth for form initial values
- [ ] All auth pages are responsive (work on mobile)
- [ ] Feature tests for login (invalid credentials) and forgot password flow

## Tech Analysis

- **Fortify view registration**: All required views are already registered (login, register, resetPassword, requestPasswordResetLink, confirmPassword). No changes needed.
- **AuthLayout**: Already exists at `layouts/AuthLayout.vue`. All auth pages import and use it. No new layout component needed.
- **Login errors**: Fortify returns failed login as session/validation errors. Ensure the form displays `form.errors.email` (or equivalent) inline. May need a general error display area if Fortify uses a different error key.
- **Initial values refactor**: Define one `initialValues` object per auth page and pass it to both `useFormSubmission({ initialValues })` and `<UiForm :initialValues>`. Follow RegisterPage pattern.
- **ResetPasswordPage**: Replace `UiFormField` + `UiInputText` for the email field with `UiFormFieldInput` and `readonly` attribute for consistency.
- **Password reset email**: Fortify sends via `ResetPassword` notification. Default template is acceptable for beta.

## References

- `platform/resources/js/pages/auth/LoginPage.vue` — login page
- `platform/resources/js/pages/auth/ForgotPasswordPage.vue` — forgot password page
- `platform/resources/js/pages/auth/ResetPasswordPage.vue` — reset password page
- `platform/resources/js/pages/auth/ConfirmPasswordPage.vue` — confirm password page
- `platform/resources/js/layouts/AuthLayout.vue` — shared auth layout (exists)
- `platform/app/Providers/FortifyServiceProvider.php` — Fortify view registration
- `platform/tests/Feature/Auth/PasswordResetTest.php` — existing password reset tests

## Files

- Modify `platform/resources/js/pages/auth/LoginPage.vue` — single source of truth for initial values; verify inline error display
- Modify `platform/resources/js/pages/auth/ForgotPasswordPage.vue` — single source of truth for initial values; update description to "Enter your email and we'll send you a reset link"
- Modify `platform/resources/js/pages/auth/ResetPasswordPage.vue` — single source of truth for initial values; use UiFormFieldInput (readonly) for email field
- Modify `platform/resources/js/pages/auth/ConfirmPasswordPage.vue` — single source of truth for initial values
- Create or modify `platform/tests/Feature/Auth/LoginTest.php` — add test for login with invalid credentials (assert error message)
- Create or modify `platform/tests/Feature/Auth/ForgotPasswordTest.php` — add test for forgot password submit and success (or extend PasswordResetTest if it already covers this)
