status: pending

# Story 5.4: Prompt 2 -- Newsletter Service Integration

## Context

Prompt 1 built the landing page with a visual-only email signup form — the form exists but doesn't submit anywhere. This prompt wires it up to an external newsletter service so emails are actually captured.

There is no local `Subscriber` model — the newsletter service IS the subscriber database. The form submits to a backend route that calls the service's API directly.

## Goal

Integrate a newsletter service so the landing page email signup form actually works:
1. Form submits email to a backend route
2. Backend route calls the newsletter service API to add the subscriber
3. Success/error feedback shown to the user
4. Subscribers are managed entirely in the service's dashboard

## Non-Goals

- Do not build a local Subscriber model or database table — the service is the source of truth
- Do not build email sending from within Fibermade (use the service's UI/campaigns)
- Do not build an admin UI for managing subscribers (use the service's dashboard)
- Do not add unsubscribe handling beyond what the service provides
- Do not add multiple lists or segments — one list is enough for now

## Decision: Which Service?

_To be decided before implementation._ Considerations:

- **MailerLite** — used previously, generous free tier (1,000 subscribers, 12,000 emails/month), good API, simple
- **Mailchimp** — well-known but free tier is limited and API is more complex
- **Buttondown** — simple, developer-friendly, generous free tier
- **Resend** — modern API, but more transactional-focused than newsletter-focused

The service should have:
- A free tier sufficient for early-stage (< 1,000 subscribers)
- An API for adding subscribers programmatically
- A UI for composing and sending campaigns/newsletters
- A Laravel SDK or simple REST API

## Constraints

- Create a `SubscriberController` with a `store` method that calls the newsletter service API
- Add `POST /subscribe` to `routes/web.php` (no auth middleware — public route)
- Use Inertia form submission — POST to route, handle in controller, return success/error
- Handle duplicate signups gracefully (friendly message: "You're already on the list!")
- Show success message after signup ("You're on the list!" or similar)
- Use a queued job for the API call so it doesn't slow down the response
- Handle API failures gracefully — log errors, show a generic error to the user
- Store the API key in `.env`, not in code
- Update the `WebNewsletter` component usage in `HomePage.vue` to wire up the form submission

## Acceptance Criteria

- [ ] Newsletter service chosen and documented
- [ ] Service SDK/API client installed and configured
- [ ] `POST /subscribe` route added (public, no auth)
- [ ] `SubscriberController` calls newsletter service API (via queued job)
- [ ] Email validation (valid format)
- [ ] Friendly handling of duplicate emails ("You're already on the list!")
- [ ] Success message displayed after signup
- [ ] API failures logged, generic error shown to user
- [ ] API key stored in `.env` with example in `.env.example`
- [ ] Landing page email form wired to backend with success/error states
- [ ] Can send a test email to the list from the service's UI

## Tech Analysis

- **Controller**: Create a `SubscriberController` with a `store` method. Validate email, dispatch a job to call the service API, return back with success flash immediately.
- **Job**: Create a `AddSubscriberToNewsletter` job that calls the service API. Handle duplicate detection (most services return a specific error/status for existing subscribers). Catch and log API exceptions.
- **Route**: Add `POST /subscribe` to `routes/web.php` (no auth middleware — public route).
- **Inertia form**: Update `WebNewsletter` usage in `HomePage.vue` to use `useForm({ email: '' })`. Submit via `form.post('/subscribe')`. Show success state on completion, error state on validation failure.
- **Configuration**: Add service API key and list/group ID to `.env` and `config/services.php`.
- **No local model**: Subscribers exist only in the newsletter service. No migration, no Eloquent model.

## References

- `platform/resources/js/pages/website/HomePage.vue` — landing page with email form (from prompt 1)
- `platform/resources/js/components/web/WebNewsletter.vue` — newsletter component
- `platform/routes/web.php` — public routes

## Files

- Create `platform/app/Http/Controllers/SubscriberController.php` — email signup handler
- Create `platform/app/Jobs/AddSubscriberToNewsletter.php` — queued job for API call
- Modify `platform/routes/web.php` — add POST /subscribe route
- Modify `platform/config/services.php` — add newsletter service config
- Modify `platform/.env.example` — add API key placeholder
- Modify `platform/resources/js/pages/website/HomePage.vue` — wire up form submission
