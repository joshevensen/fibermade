status: pending

# Story 5.4: Prompt 1 -- Landing Page with Email Signup

## Context

The current landing page exists at `platform/resources/js/pages/website/HomePage.vue` and is already quite developed. It has sections for: hero ("Production-first software for hand-dyed yarn businesses"), problems (inventory mismatches, wholesale chaos, dye day planning, overcommitment anxiety), philosophy (production-first principles), what's coming (3 stages), who it's for (good fit vs. not), and a newsletter/email signup form. The route is `GET /` defined in `routes/web.php`. The page shows login/register links if not authenticated and a dashboard link if authenticated. The `about/Coming-Soon.md` file contains the editorial copy that should inform the page's messaging. There is no backend for the email signup form -- it needs to actually capture and store emails.

## Goal

Polish the landing page for beta launch: refine the visual design and copy, implement the email signup form backend (store emails for the early access waitlist), and ensure the page makes a strong first impression. The copy should draw from `about/Coming-Soon.md` while feeling natural on a web page. The page should be simple, professional, and clearly communicate what Fibermade is and who it's for.

## Non-Goals

- Do not build a full marketing website (just one landing page)
- Do not add a blog, about page, or multi-page marketing site
- Do not integrate with a third-party email marketing tool (store signups locally for now)
- Do not add animations or complex interactivity
- Do not add a pricing section

## Constraints

- The email signup needs a backend: create a `Subscriber` model with `email` (unique), `subscribed_at`, and `source` (e.g., "landing_page") fields
- Create a migration for the `subscribers` table
- Create a controller action (or handle inline in the route) that validates the email and stores it
- Use Inertia form submission for the signup -- POST to a route, handle in a controller, return success/error
- Prevent duplicate signups (unique email constraint, friendly error message)
- Show a success message after signup ("You're on the list!" or similar)
- The page should work without JavaScript for basic content (progressive enhancement for the form)
- Use the existing Tailwind CSS setup for styling
- Keep the existing sections but refine:
  - Hero: strong, clear headline and subheadline
  - Problem statement: concise, relatable
  - What Fibermade does: brief and clear
  - Email signup: prominent placement (hero area and/or bottom of page)
  - Footer: simple with tagline
- Remove or simplify the "Who it's for" section if it feels too long -- the page should be scannable
- Login/register links should remain for authenticated navigation
- Reference `about/Coming-Soon.md` for tone and messaging but adapt for web (shorter paragraphs, more scannable)

## Acceptance Criteria

- [ ] `Subscriber` model created with `email`, `subscribed_at`, `source` fields
- [ ] Migration creates `subscribers` table with unique index on email
- [ ] POST route for email signup (e.g., `POST /subscribe`)
- [ ] Email validation (valid format, unique)
- [ ] Friendly error for duplicate emails ("You're already on the list!")
- [ ] Success message displayed after signup
- [ ] Landing page has polished, professional design
- [ ] Hero section with clear headline and email signup form
- [ ] Problem statement section (concise)
- [ ] What Fibermade does section
- [ ] Email signup form (in hero and/or at bottom)
- [ ] Footer with Fibermade tagline
- [ ] Login/register navigation links for unauthenticated users
- [ ] Dashboard link for authenticated users
- [ ] Page is responsive (mobile-friendly)
- [ ] Copy draws from `about/Coming-Soon.md` tone and messaging

---

## Tech Analysis

- **Subscriber model**: Simple Eloquent model. No relationships needed. The `source` field allows tracking where signups come from (landing page vs. future sources).
  ```php
  Schema::create('subscribers', function (Blueprint $table) {
      $table->id();
      $table->string('email')->unique();
      $table->string('source')->default('landing_page');
      $table->timestamp('subscribed_at');
      $table->timestamps();
  });
  ```
- **Controller**: Create a `SubscriberController` with a `store` method. Validate email, check uniqueness (use validation rule or try/catch on unique constraint), create record, return back with success flash.
- **Route**: Add `POST /subscribe` to `routes/web.php` (no auth middleware -- public route).
- **Inertia form**: Use `useForm({ email: '' })` in the Vue component. Submit via `form.post('/subscribe')`. Check `form.wasSuccessful` for success state, `form.errors.email` for validation errors.
- **Current page structure**: The existing `HomePage.vue` already has most sections. The work is primarily:
  1. Refine copy to be more concise and impactful
  2. Polish the visual design (spacing, typography, colors)
  3. Wire up the email signup form to the new backend
  4. Ensure the signup form shows success/error states
- **Design approach**: Keep it simple. A clean, modern look with plenty of whitespace. The hero should immediately communicate the value proposition. Use the fiber/yarn community language from `Coming-Soon.md`.

## References

- `platform/resources/js/pages/website/HomePage.vue` -- current landing page
- `about/Coming-Soon.md` -- editorial copy and messaging reference
- `platform/routes/web.php` -- public routes
- `platform/app/Models/` -- model directory for new Subscriber model

## Files

- Create `platform/app/Models/Subscriber.php` -- subscriber model
- Create `platform/database/migrations/xxxx_create_subscribers_table.php` -- subscribers table
- Create `platform/app/Http/Controllers/SubscriberController.php` -- email signup handler
- Modify `platform/routes/web.php` -- add POST /subscribe route
- Modify `platform/resources/js/pages/website/HomePage.vue` -- polish design, wire up email signup form
