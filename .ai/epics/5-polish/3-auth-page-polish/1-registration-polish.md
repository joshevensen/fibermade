status: pending

# Story 5.3: Prompt 1 -- Registration Page Polish

## Context

The registration page exists at `platform/resources/js/pages/auth/RegisterPage.vue` with fields for name, email, business_name, password (confirmed), and checkboxes for terms, privacy, and marketing opt-in. The `CreateNewUser` action at `platform/app/Actions/Fortify/CreateNewUser.php` handles registration: it validates input, checks the email whitelist (currently limited to `kristen@badfrogyarnco.com` in `config/auth.php`), creates an Account (type: Creator), Creator record, and User. The `FortifyServiceProvider` renders the register view via Inertia. Registration always creates a Creator account -- there's no account type selection.

For beta launch, the whitelist needs to be expanded or made configurable so 5-10 personally onboarded creators can register. The registration page needs visual polish to match the app's design system and feel production-ready.

## Goal

Polish the registration page for production readiness: update the visual design to be clean and professional, expand the email whitelist approach for beta (allow configuration via environment variable or expand the config array), ensure error messaging is clear and helpful, and add any missing UX touches (loading states, field validation feedback).

## Non-Goals

- Do not add account type selection (all registrations are Creator accounts for beta)
- Do not add OAuth/social login
- Do not modify the post-registration redirect logic
- Do not add onboarding flows after registration (that's a future epic)
- Do not modify the login page -- that's Prompt 2

## Constraints

- The registration page should use the existing Vue component library and Tailwind CSS classes used elsewhere in the app
- Change the whitelist approach: instead of hardcoding emails in `config/auth.php`, use an environment variable `REGISTRATION_WHITELIST` that accepts a comma-separated list of emails. If the variable is empty or not set, allow all registrations (open registration for when beta expands).
- Keep the existing fields: name, email, business_name, password, password_confirmation, terms, privacy, marketing
- Add client-side validation feedback (inline errors below fields, not just a banner at the top)
- Add a loading/submitting state to the submit button to prevent double-clicks
- Ensure the page works well on mobile (responsive layout)
- The page should have a clear heading ("Create your account" or similar) and a link to login for existing users
- Error messages from the server (e.g., "This email address is not authorized to register") should display clearly

## Acceptance Criteria

- [ ] Registration page has polished, professional visual design
- [ ] Consistent with the app's design system (typography, spacing, colors)
- [ ] Whitelist now reads from `REGISTRATION_WHITELIST` env variable (comma-separated emails)
- [ ] Empty/missing `REGISTRATION_WHITELIST` allows all registrations
- [ ] Server validation errors display inline below the relevant field
- [ ] Submit button shows loading state while form is processing
- [ ] Responsive layout works on mobile screens
- [ ] Login link visible for existing users ("Already have an account? Log in")
- [ ] Terms and privacy checkboxes clearly labeled
- [ ] Password confirmation field validates match client-side
- [ ] `CreateNewUser` action updated to read whitelist from env
- [ ] `.env.example` updated with `REGISTRATION_WHITELIST` variable

---

## Tech Analysis

- **Whitelist via env**: Replace the config array in `config/auth.php` with:
  ```php
  'registration_email_whitelist' => env('REGISTRATION_WHITELIST')
      ? array_map('trim', explode(',', env('REGISTRATION_WHITELIST')))
      : [],
  ```
  This reads a comma-separated string from the environment and splits it into an array. Empty string or missing variable results in an empty array, which the `CreateNewUser` action already treats as "allow all".
- **Inertia form handling**: The existing `RegisterPage.vue` likely uses Inertia's `useForm()` composable which provides `form.errors` (server-side errors keyed by field name), `form.processing` (loading state), and `form.post()` for submission. These features should already be available -- the work is mostly in the template to display them properly.
- **Inline errors**: For each field, check `form.errors.{field}` and render a `<p class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>` below the input. This is a common pattern with Inertia + Vue.
- **Loading state**: Use `form.processing` to disable the submit button and show a spinner or "Creating account..." text.
- **Design polish**: Look at how the creator dashboard pages are styled (e.g., `OrderIndexPage.vue`, `DashboardPage.vue`) for typography and spacing patterns. The auth pages should feel like they belong to the same application.

## References

- `platform/resources/js/pages/auth/RegisterPage.vue` -- current registration page
- `platform/app/Actions/Fortify/CreateNewUser.php` -- registration logic with whitelist check
- `platform/config/auth.php` -- current whitelist config (lines 119-134)
- `platform/app/Providers/FortifyServiceProvider.php` -- register view rendering
- `platform/.env.example` -- environment variables

## Files

- Modify `platform/resources/js/pages/auth/RegisterPage.vue` -- visual polish, inline errors, loading state
- Modify `platform/app/Actions/Fortify/CreateNewUser.php` -- update whitelist check if config structure changes
- Modify `platform/config/auth.php` -- change whitelist to read from env variable
- Modify `platform/.env.example` -- add `REGISTRATION_WHITELIST` variable
