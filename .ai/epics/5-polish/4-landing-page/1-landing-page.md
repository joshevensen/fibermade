status: pending

# Story 5.4: Prompt 1 -- Landing Page Design

## Context

The current landing page at `platform/resources/js/pages/website/HomePage.vue` was written with an older positioning ("production-first software" / "commerce platform") that no longer matches Stage 1 reality. Stage 1 Fibermade is a **Shopify app that makes Shopify work for the fiber community** — adding wholesale and converting generic Shopify concepts into fiber-specific terminology and workflows.

The page currently has 6 content sections built with raw HTML/Tailwind, but we have 11 Tailwind Plus-based components in `platform/resources/js/components/web/` that should be used instead. Only WebHeader, WebHero, WebNewsletter, and WebFooter are currently imported — the rest are unused.

The page is launching before user signups are ready, so the only action is joining the early access email list. The email signup form will be visual-only in this prompt — the backend integration with a newsletter service is handled in prompt 2.

## Goal

Rebuild the landing page with updated positioning (Shopify app for the fiber community) and use the existing Tailwind Plus web components for a polished design. The page should clearly communicate: Shopify is generic → Fibermade makes it fiber-specific → here's what it does → sign up for early access.

## Non-Goals

- Do not build a full marketing website (just one landing page)
- Do not add a blog, about page, or multi-page marketing site
- Do not build any backend for the email signup form (that's prompt 2)
- Do not add animations or complex interactivity
- Do not add a pricing section
- Do not write final marketing copy — use good placeholder copy that captures the right tone and message; it will be refined later

## Page Structure

The page should use these sections in this order, using existing web components:

### 1. Header — `WebHeader`
- Already in use. Keep login/register links for unauthenticated users, dashboard link for authenticated users.

### 2. Hero — `WebHero` (`screenshotRight` variant)
- **Headline**: Contrast angle — "Shopify wasn't built for yarn. Fibermade fixes that." (or similar)
- **Subline**: One sentence about what Fibermade does — adds wholesale ordering and converts Shopify's generic concepts into fiber-specific ones (colorways, bases, etc.)
- **CTA button**: "Join the early list" → scrolls to `#newsletter`
- **Screenshot**: Placeholder image for now (will be replaced with real product screenshots)

### 3. Problem Statement — `WebFeatures` (`threeColumn` variant)
- **Section title**: Something like "The problem" or "Why Shopify falls short for fiber businesses"
- 3-4 pain points that fiber businesses hit with vanilla Shopify:
  - Generic product concepts that don't map to how you think (variants ≠ colorways)
  - No wholesale workflow — cobbling together emails, spreadsheets, and PDFs
  - Inventory that doesn't understand how dyed yarn actually works
  - You've adapted your brain to Shopify's language instead of the other way around

### 4. Features / How We Fix It — `WebFeatures` (`featureList` or `imageRight` variant with placeholder)
- **Section title**: Something like "What Fibermade adds to your Shopify store"
- Key features:
  - Wholesale ordering — stores can browse your line sheet and place orders directly
  - Fiber-specific terminology — colorways, yarn bases, weights instead of generic variants
  - Production-aware inventory — inventory that understands how dyed yarn works
  - (More features as appropriate from the Stage 1 scope)
- Placeholder screenshot(s) alongside features if using `imageRight` variant

### 5. Coming Soon / Roadmap — lighter section
- Sets expectations: "Currently in development"
- Brief mention of what's being built first
- "Early list members get first access" — gives reason to sign up
- Could use `WebCallToAction` or a simple custom section

### 6. Newsletter Signup — `WebNewsletter` (`stacked` or `justified` variant)
- `id="newsletter"` anchor for hero CTA scroll
- Title: "Get early access" or "Follow along"
- The form is **visual only** — the submit button does not do anything yet (prompt 2 wires it to a newsletter service)
- No backend route, no form submission logic

### 7. Footer — `WebFooter` (`centered` variant)
- Keep simple with Fibermade tagline

### Sections to Remove (from current page)
- "What Fibermade believes" (philosophy) — too abstract for a landing page
- "Who this is for / not for" — audience is clear from the rest of the page
- All "production-first" framing — doesn't match Stage 1 reality

## Constraints

### Design
- Use existing Tailwind Plus web components — do not hand-code sections that components already handle
- Use the existing Tailwind CSS setup for styling
- Page must be responsive (mobile-friendly)
- Use placeholder images where screenshots will go (simple gray boxes with text labels are fine)
- Keep the page scannable — short paragraphs, clear hierarchy

### Navigation
- Login/register links for unauthenticated users
- Dashboard link for authenticated users (existing behavior)

## Acceptance Criteria

- [ ] Page uses Tailwind Plus web components (not raw HTML for major sections)
- [ ] Hero with contrast-angle headline, subline, CTA, and placeholder screenshot
- [ ] Problem statement section (3-4 fiber-specific Shopify pain points)
- [ ] Features section (what Fibermade adds to Shopify)
- [ ] Coming soon / roadmap section with early access framing
- [ ] Newsletter signup form present (visual only — no backend)
- [ ] Footer with Fibermade tagline
- [ ] Header with login/register links (unauthenticated) or dashboard link (authenticated)
- [ ] Page is responsive (mobile-friendly)
- [ ] No "production-first" or "commerce platform" framing — messaging reflects Shopify app positioning

---

## Tech Analysis

- **No backend work** — this prompt is purely frontend. The email signup form is visual-only; prompt 2 connects it to a newsletter service.
- **Web components to use**:
  - `WebHeader` — already in use, keep as-is
  - `WebHero` — switch to `screenshotRight` variant, update props
  - `WebFeatures` — use for problem statement (`threeColumn`) and features (`featureList` or `imageRight`)
  - `WebCallToAction` or custom — for coming soon section
  - `WebNewsletter` — already imported, keep as visual placeholder
  - `WebFooter` — already in use, keep as-is
- **Placeholder images**: Use simple placeholder divs with gray background and descriptive text (e.g., "Product screenshot — coming soon") that can be swapped for real screenshots later.

## References

- `platform/resources/js/pages/website/HomePage.vue` — current landing page
- `platform/resources/js/components/web/` — Tailwind Plus web components
- `about/Coming-Soon.md` — editorial copy reference (tone, not positioning)

## Files

- Modify `platform/resources/js/pages/website/HomePage.vue` — rebuild with updated positioning and web components
