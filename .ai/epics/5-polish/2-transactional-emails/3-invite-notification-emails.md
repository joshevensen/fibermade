status: done

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

- Create one new Mailable: `StoreInviteAcceptedMail` — sent to the creator when a store accepts their invite
- The Mailable should implement `ShouldQueue` for async delivery
- Email view extends the shared layout (`emails.layout`)
- **Invite accepted notification** (to creator): includes store name, owner name, congratulatory message (e.g. "Great news! {store_name} has accepted your invite and is now connected on Fibermade."), and a CTA link to creator stores page (`route('creator.stores.index')`) — e.g. "View your stores"
- **Creator email resolution**: Primary `Creator.email`; fallback to account owner (`account.users()->where('role', UserRole::Owner)->first()?->email`). Skip sending when both are null.
- **Mailable constructor**: Pass `Invite` and `Store` models with eager loads (`inviter`, `inviter.account`, `store`) — follows wholesale order pattern
- **Dispatch**: Inside `DB::transaction()` in `InviteController::acceptStore()`, after `$invite->update(['accepted_at' => now()])` and the store is linked to the creator
- **Required review**: `StoreInviteMail` and its Blade view — verify layout, subject, content, and CTA are consistent with design system

## Acceptance Criteria

- [ ] `StoreInviteAcceptedMail` Mailable created with queued delivery
- [ ] Blade view created at `resources/views/emails/invite-accepted.blade.php` extending `emails.layout`
- [ ] Email sent to creator when store accepts invite (Creator.email or account owner fallback)
- [ ] Email not sent when creator email and account owner email are both null
- [ ] Email includes store name, owner name, congratulatory message
- [ ] Email subject: "{store_name} accepted your invite on Fibermade"
- [ ] Email includes CTA button linking to creator stores page
- [ ] Dispatched inside transaction in `InviteController::acceptStore()` after invite update and store link
- [ ] Existing `StoreInviteMail` reviewed and consistent with design system
- [ ] Test: email dispatched on invite acceptance (use `Mail::fake()`)
- [ ] Test: email not sent when creator and account owner both have no email

---

## Tech Analysis

- **Finding the creator email**: Primary `$invite->inviter->email` (Creator); fallback `$invite->inviter->account->users()->where('role', UserRole::Owner)->first()?->email`. If both null, skip sending (optionally log).
- **Dispatch point**: Inside `DB::transaction()` in `acceptStore()`, after `$invite->update(['accepted_at' => now()])` and `$creator->stores()->attach($store->id)`. Eager-load `inviter`, `inviter.account`, and `store` before queuing.
- **Mailable constructor**: `public function __construct(public Invite $invite, public Store $store)` — use `SerializesModels` trait.
- **CTA route**: `route('creator.stores.index')` for "View your stores" button.
- **Existing invite email review**: Verify `store-invite.blade.php` extends `emails.layout`, subject is clear, CTA button styling matches order emails.

## References

- `platform/app/Http/Controllers/InviteController.php` — `acceptStore()` method, `store()` method
- `platform/app/Models/Invite.php` — invite model with inviter relationship
- `platform/app/Mail/StoreInviteMail.php` — existing invite Mailable (reference)
- `platform/app/Mail/WholesaleNewOrderNotificationMail.php` — pattern for creator notification with CTA
- `platform/resources/views/emails/store-invite.blade.php` — existing invite template
- `platform/resources/views/emails/layout.blade.php` — shared layout

## Files

- Create `platform/app/Mail/StoreInviteAcceptedMail.php`
- Create `platform/resources/views/emails/invite-accepted.blade.php`
- Modify `platform/app/Http/Controllers/InviteController.php` — dispatch `StoreInviteAcceptedMail` inside transaction in `acceptStore()`
- Review/modify `platform/resources/views/emails/store-invite.blade.php` — ensure design system consistency
- Create `platform/tests/Feature/Mail/InviteMailTest.php` — test invite accepted email dispatch and skip-when-no-email
