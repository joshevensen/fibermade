status: pending

# Story 5.2: Prompt 3 -- Store Invite & Creator Notification Emails

## Context

Prompt 1 set up email infrastructure and a shared layout. Prompt 2 built wholesale order emails. The invite system already exists: `InviteController::store()` creates invites and sends `StoreInviteMail` (already migrated to the shared layout in Prompt 1). The `StoreInviteMail` sends an email to the invited store with a link to accept the invite. However, there's no notification to the creator when a store accepts their invite. Additionally, the invite acceptance flow in `InviteController::acceptStore()` creates the store account and links it to the creator but doesn't notify the creator.

## Goal

Add a notification email to the creator when a store accepts their invite, and review/polish the existing store invite email to ensure it's consistent with the new email design system established in Prompts 1-2.

## Non-Goals

- Do not modify the invite creation or acceptance logic
- Do not add invite reminder/follow-up emails
- Do not add email for invite expiration
- Do not modify the invite model or API

## Constraints

- Create one new Mailable: `StoreInviteAcceptedMail` -- sent to the creator when a store accepts their invite
- The Mailable should implement `ShouldQueue` for async delivery
- Email view extends the shared layout (`emails.layouts.transactional`)
- **Invite accepted notification** (to creator): includes store name, owner name, and a message like "Great news! {store_name} has accepted your invite and is now connected on Fibermade."
- Dispatch in `InviteController::acceptStore()` after the invite is marked as accepted and the store is linked
- The creator's email comes from the invite's `inviter` (polymorphic relationship) -- resolve to the account owner's email
- Review `StoreInviteMail` and its Blade view to ensure:
  - It uses the shared layout correctly (Prompt 1 should have handled this)
  - Subject line, content, and CTA button are polished for production
  - The accept URL is correct and works

## Acceptance Criteria

- [ ] `StoreInviteAcceptedMail` Mailable created with queued delivery
- [ ] Blade view created at `resources/views/emails/invites/invite-accepted.blade.php` extending shared layout
- [ ] Email sent to creator (inviter's account owner) when store accepts invite
- [ ] Email includes store name, owner name, and congratulatory message
- [ ] Email subject: "{store_name} accepted your invite on Fibermade"
- [ ] Dispatched in `InviteController::acceptStore()` after successful acceptance
- [ ] Existing `StoreInviteMail` reviewed and consistent with design system
- [ ] Test verifies email is dispatched on invite acceptance (use `Mail::fake()`)

---

## Tech Analysis

- **Finding the creator email**: The `Invite` model has an `inviter` polymorphic relationship (`inviter_type`, `inviter_id`). For store invites, the inviter is a `Creator`. The Creator belongs to an `Account`, which has users. Get the owner: `$invite->inviter->account->users()->where('role', UserRole::Owner)->first()->email`.
- **Dispatch point**: In `InviteController::acceptStore()`, after `$invite->update(['accepted_at' => now()])` and the store is linked to the creator. Add `Mail::to($creatorEmail)->queue(new StoreInviteAcceptedMail($invite, $store))`.
- **Mailable constructor**: Pass the `Invite` (for inviter context) and the newly created `Store` (for store name/owner). Or pass just the relevant data (store name, owner name, creator name) to keep the Mailable simple.
- **Existing invite email review**: The `StoreInviteMail` was migrated to the shared layout in Prompt 1. Verify the CTA button styling matches the order emails, the subject is clear, and the content reads well. Minor copy tweaks are acceptable.

## References

- `platform/app/Http/Controllers/InviteController.php` -- `acceptStore()` method, `store()` method
- `platform/app/Models/Invite.php` -- invite model with inviter relationship
- `platform/app/Mail/StoreInviteMail.php` -- existing invite Mailable (reference)
- `platform/resources/views/emails/store-invite.blade.php` -- existing invite template
- `platform/resources/views/emails/layouts/transactional.blade.php` -- shared layout

## Files

- Create `platform/app/Mail/StoreInviteAcceptedMail.php`
- Create `platform/resources/views/emails/invites/invite-accepted.blade.php`
- Modify `platform/app/Http/Controllers/InviteController.php` -- dispatch `StoreInviteAcceptedMail` in `acceptStore()`
- Review/modify `platform/resources/views/emails/store-invite.blade.php` -- polish if needed
- Create `platform/tests/Feature/Mail/InviteMailTest.php` -- test invite accepted email dispatch
