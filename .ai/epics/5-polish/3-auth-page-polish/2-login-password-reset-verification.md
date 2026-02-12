status: pending

# Story 5.3: Prompt 2 -- Login, Password Reset & Email Verification Polish

## Context

The login page exists at `platform/resources/js/pages/auth/LoginPage.vue` with email, password, remember-me checkbox, and a forgot-password link. The forgot password page is at `ForgotPasswordPage.vue` and the reset form at `ResetPasswordPage.vue`. There's also a `ConfirmPasswordPage.vue` for sensitive action confirmation. Fortify handles all the backend logic for password reset (sending the email, validating the token, updating the password). The `FortifyServiceProvider` registers custom Inertia views for login and register but may not have custom views for password reset and email verification -- Fortify's default Blade views may be in use for those flows. The custom `LoginResponse` routes users to `/store/home` or `/creator/dashboard` based on account type. The User model uses Laravel's `MustVerifyEmail` trait (need to verify). Email verification uses Laravel's built-in verification system.

## Goal

Polish the login page, ensure the full password reset flow works end-to-end (request → email → reset form → confirmation), verify the email verification flow works, and ensure all auth pages share a consistent visual design matching the registration page polished in Prompt 1.

## Non-Goals

- Do not add OAuth/social login
- Do not add two-factor authentication
- Do not modify the login response routing logic
- Do not add rate limiting beyond what Fortify already provides
- Do not modify the registration page (done in Prompt 1)

## Constraints

- All auth pages should share a consistent layout: centered card, app logo/name, consistent typography and spacing
- Consider creating a shared auth layout component if one doesn't exist (e.g., `AuthLayout.vue`) to avoid duplicating the wrapper across all auth pages
- **Login page**: Polish design, ensure error messages display inline (e.g., "These credentials do not match our records"), loading state on submit button
- **Forgot password page**: Clean design, clear instructions ("Enter your email and we'll send you a reset link"), success message after submission
- **Reset password page**: Token-based form with email, new password, password confirmation. Success redirects to login.
- **Email verification**: Ensure the verification notice page exists and renders via Inertia (not a Blade view). The notice page should say "We've sent a verification email. Check your inbox." with a resend button.
- Ensure all Fortify views are registered in `FortifyServiceProvider` and render Inertia pages (not default Blade views)
- Test the full flow manually or via feature tests

## Acceptance Criteria

- [ ] Login page has polished design consistent with registration page
- [ ] Login shows inline error messages for invalid credentials
- [ ] Login submit button shows loading state while processing
- [ ] "Forgot password?" link navigates to the forgot password page
- [ ] Forgot password page sends reset email on valid email submission
- [ ] Forgot password page shows success message after sending email
- [ ] Password reset page renders correctly from the email link (with token)
- [ ] Password reset updates the password and redirects to login
- [ ] Email verification notice page renders via Inertia (not Blade)
- [ ] Email verification notice has a "Resend verification email" button
- [ ] Verification email link verifies the user's email
- [ ] Unverified users are redirected to the verification notice page when accessing protected routes (if `MustVerifyEmail` is implemented)
- [ ] All auth pages use consistent visual styling (shared layout)
- [ ] All auth pages are responsive (work on mobile)
- [ ] All Fortify views registered in `FortifyServiceProvider`

---

## Tech Analysis

- **Fortify view registration**: `FortifyServiceProvider::boot()` currently registers views for login and register. It may not register views for: `requestPasswordResetLink`, `resetPassword`, `verifyEmail`, `confirmPassword`. Check and add any missing:
  ```php
  Fortify::requestPasswordResetLinkView(fn () => Inertia::render('auth/ForgotPasswordPage'));
  Fortify::resetPasswordView(fn ($request) => Inertia::render('auth/ResetPasswordPage', ['token' => $request->route('token'), 'email' => $request->email]));
  Fortify::verifyEmailView(fn () => Inertia::render('auth/VerifyEmailPage'));
  Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPasswordPage'));
  ```
- **Email verification**: Check if the `User` model implements `MustVerifyEmail`. If not, email verification won't be enforced. For beta, we likely want email verification enabled. The `verified` middleware on route groups blocks unverified users.
- **Shared auth layout**: Create an `AuthLayout.vue` component that provides the centered card wrapper with logo. Each auth page imports and wraps its content in this layout. This prevents duplication.
- **Verification notice page**: If `VerifyEmailPage.vue` doesn't exist, create it. It should show a message and a resend button that POSTs to `/email/verification-notification` (Fortify's built-in route).
- **Password reset email**: Fortify/Laravel sends the reset email using `ResetPassword` notification. The email content uses Laravel's default notification template. This is acceptable for beta -- customizing it to use the shared email layout from Story 5.2 would be nice but is optional.

## References

- `platform/resources/js/pages/auth/LoginPage.vue` -- login page
- `platform/resources/js/pages/auth/ForgotPasswordPage.vue` -- forgot password page
- `platform/resources/js/pages/auth/ResetPasswordPage.vue` -- reset password page
- `platform/resources/js/pages/auth/ConfirmPasswordPage.vue` -- confirm password page
- `platform/app/Providers/FortifyServiceProvider.php` -- Fortify view registration
- `platform/app/Http/Responses/LoginResponse.php` -- custom login redirect
- `platform/app/Models/User.php` -- check for MustVerifyEmail
- `platform/config/fortify.php` -- Fortify features configuration

## Files

- Modify `platform/resources/js/pages/auth/LoginPage.vue` -- visual polish, inline errors, loading state
- Modify `platform/resources/js/pages/auth/ForgotPasswordPage.vue` -- visual polish, success messaging
- Modify `platform/resources/js/pages/auth/ResetPasswordPage.vue` -- visual polish, form validation
- Modify `platform/resources/js/pages/auth/ConfirmPasswordPage.vue` -- visual polish
- Create `platform/resources/js/pages/auth/VerifyEmailPage.vue` -- email verification notice page (if it doesn't exist)
- Create `platform/resources/js/components/auth/AuthLayout.vue` -- shared auth page layout component
- Modify `platform/app/Providers/FortifyServiceProvider.php` -- register any missing Fortify views
